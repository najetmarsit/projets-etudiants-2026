import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import { NotificationItem } from '../../../core/models/notification.model';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationRealtimeService } from '../../../core/services/notification-realtime.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { ClinPageHeadComponent } from '../../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-notification-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, ClinPageHeadComponent],
  templateUrl: './notification-list.component.html',
  styleUrl: './notification-list.component.scss',
})
export class NotificationListComponent implements OnInit {
  private api = inject(ApiService);
  private realtime = inject(NotificationRealtimeService);
  private translate = inject(AppTranslateService);
  readonly auth = inject(AuthService);
  readonly portal = inject(PortalService);

  items: NotificationItem[] = [];
  loading = true;
  error = '';

  ngOnInit(): void {
    this.load();
    this.realtime.start();
    this.realtime.notifications$.subscribe((n) => {
      const idx = this.items.findIndex((x) => x.id === n.id);
      if (idx >= 0) {
        const copy = [...this.items];
        copy[idx] = { ...copy[idx], ...n };
        this.items = copy;
      } else {
        this.items = [n, ...this.items].slice(0, 200);
      }
    });
  }

  load(): void {
    this.loading = true;
    this.error = '';
    const role = this.auth.user()?.role;
    const audience =
      role === 'Admin'
        ? 'admin'
        : role === 'Doctor'
          ? 'doctor'
          : role === 'Nurse'
            ? 'nurse'
            : role === 'Laboratory'
              ? 'laboratory'
              : role === 'Accountant'
                ? 'accountant'
                : undefined;

    this.api.getNotifications({ limit: 100, audience }).subscribe({
      next: (r) => {
        this.items = r.data ?? [];
        this.loading = false;
      },
      error: (err) => {
        this.error = this.translate.apiErrorMessage(err.error?.message) || this.translate.instant('STAFF.NOTIF_LOAD_ERROR');
        this.loading = false;
      },
    });
  }

  markRead(n: NotificationItem): void {
    if (n.read_at) return;
    this.api.markNotificationRead(n.id).subscribe({ next: () => this.load() });
  }

  acknowledge(n: NotificationItem): void {
    if (n.acknowledged_at) return;
    this.api.acknowledgeNotification(n.id).subscribe({ next: () => this.load() });
  }
}
