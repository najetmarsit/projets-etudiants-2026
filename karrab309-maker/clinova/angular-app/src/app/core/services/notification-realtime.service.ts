import { Injectable, NgZone, inject } from '@angular/core';
import { Observable, Subject } from 'rxjs';
import { AuthService } from './auth.service';
import { NotificationItem } from '../models/notification.model';

/**
 * Réception temps réel via SSE (EventSource).
 *
 * Important: EventSource ne supporte pas les headers Authorization.
 * Le backend accepte donc `?token=...` pour le stream.
 */
@Injectable({ providedIn: 'root' })
export class NotificationRealtimeService {
  private auth = inject(AuthService);
  private zone = inject(NgZone);

  private es: EventSource | null = null;
  private reconnectTimer: ReturnType<typeof setTimeout> | null = null;
  private backoffMs = 800;

  private subject = new Subject<NotificationItem>();
  notifications$: Observable<NotificationItem> = this.subject.asObservable();

  private storageKeyForLastId(): string {
    const uid = this.auth.user()?.id ?? 'anon';
    return `notif_last_id_${uid}`;
  }

  getLastId(): number {
    const raw = localStorage.getItem(this.storageKeyForLastId());
    const n = raw ? parseInt(raw, 10) : 0;
    return Number.isFinite(n) ? n : 0;
  }

  private setLastId(id: number): void {
    if (!Number.isFinite(id) || id <= 0) return;
    localStorage.setItem(this.storageKeyForLastId(), String(id));
  }

  start(): void {
    if (this.es) return;
    const token = this.auth.getToken();
    if (!token) return;

    const sinceId = this.getLastId();
    const url = `/api/notifications/stream?token=${encodeURIComponent(token)}&since_id=${sinceId}&max_seconds=25`;

    // EventSource callbacks s'exécutent hors zone Angular → on repasse dedans pour rafraîchir l'UI.
    this.zone.runOutsideAngular(() => {
      this.es = new EventSource(url);

      this.es.addEventListener('notification', (ev: MessageEvent) => {
        try {
          const data = JSON.parse(String(ev.data ?? '{}')) as Partial<NotificationItem>;
          const id = typeof data.id === 'number' ? data.id : parseInt(String(data.id ?? 0), 10);
          if (id > 0) this.setLastId(id);
          this.zone.run(() => this.subject.next(data as NotificationItem));
        } catch {
          // ignore
        }
      });

      // Heartbeat / ping: noop (permet juste de garder la connexion vivante)
      this.es.addEventListener('ping', () => {});

      this.es.addEventListener('end', () => {
        // Stream court: on reconnecte
        this.scheduleReconnect();
      });

      this.es.onerror = () => {
        // En cas de 401, on stop et laisser l'interceptor gérer la session.
        this.scheduleReconnect();
      };
    });
  }

  stop(): void {
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }
    if (this.es) {
      this.es.close();
      this.es = null;
    }
    this.backoffMs = 800;
  }

  private scheduleReconnect(): void {
    if (this.reconnectTimer) return;
    this.stopEventSourceOnly();
    const delay = Math.min(this.backoffMs, 8000);
    this.backoffMs = Math.min(this.backoffMs * 1.6, 8000);
    this.reconnectTimer = setTimeout(() => {
      this.reconnectTimer = null;
      this.start();
    }, delay);
  }

  private stopEventSourceOnly(): void {
    if (this.es) {
      this.es.close();
      this.es = null;
    }
  }
}

