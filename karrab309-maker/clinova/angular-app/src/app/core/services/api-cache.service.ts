import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, of, tap, catchError, shareReplay, finalize } from 'rxjs';

export interface CacheOptions {
  /** Durée pendant laquelle les données sont considérées fraîches (ms). */
  staleMs?: number;
  /** Durée max en mémoire avant purge (ms). */
  ttlMs?: number;
  /** Rafraîchir en arrière-plan si stale. */
  backgroundRefresh?: boolean;
}

interface Entry<T> {
  data: T;
  fetchedAt: number;
}

const DEFAULT_STALE = 30_000;
const DEFAULT_TTL = 5 * 60_000;
const BG_HEADER = 'X-Background-Refresh';

@Injectable({ providedIn: 'root' })
export class ApiCacheService {
  private store = new Map<string, Entry<unknown>>();
  private inflight = new Map<string, Observable<unknown>>();

  get<T>(key: string, fetcher: () => Observable<T>, opts: CacheOptions = {}): Observable<T> {
    const staleMs = opts.staleMs ?? DEFAULT_STALE;
    const ttlMs = opts.ttlMs ?? DEFAULT_TTL;
    const bg = opts.backgroundRefresh !== false;
    const now = Date.now();
    const hit = this.store.get(key) as Entry<T> | undefined;

    if (hit && now - hit.fetchedAt < ttlMs) {
      const isFresh = now - hit.fetchedAt < staleMs;
      if (isFresh) {
        return of(hit.data);
      }
      if (bg) {
        this.refreshInBackground(key, fetcher, ttlMs);
        return of(hit.data);
      }
    }

    return this.fetchDeduped(key, fetcher, ttlMs);
  }

  invalidate(key: string): void {
    this.store.delete(key);
    this.inflight.delete(key);
  }

  invalidatePrefix(prefix: string): void {
    for (const k of this.store.keys()) {
      if (k.startsWith(prefix)) {
        this.store.delete(k);
        this.inflight.delete(k);
      }
    }
  }

  clear(): void {
    this.store.clear();
    this.inflight.clear();
  }

  backgroundHeaders(): HttpHeaders {
    return new HttpHeaders().set(BG_HEADER, '1');
  }

  isBackgroundRequest(headers: HttpHeaders | null | undefined): boolean {
    return headers?.get(BG_HEADER) === '1';
  }

  private refreshInBackground<T>(key: string, fetcher: () => Observable<T>, ttlMs: number): void {
    if (this.inflight.has(key)) {
      return;
    }
    const req$ = fetcher().pipe(
      tap((data) => this.store.set(key, { data, fetchedAt: Date.now() })),
      catchError(() => of(null)),
      finalize(() => this.inflight.delete(key)),
      shareReplay(1)
    );
    this.inflight.set(key, req$ as Observable<unknown>);
    req$.subscribe();
  }

  private fetchDeduped<T>(key: string, fetcher: () => Observable<T>, ttlMs: number): Observable<T> {
    const existing = this.inflight.get(key) as Observable<T> | undefined;
    if (existing) {
      return existing;
    }

    const req$ = fetcher().pipe(
      tap((data) => this.store.set(key, { data, fetchedAt: Date.now() })),
      catchError((err) => {
        this.inflight.delete(key);
        throw err;
      }),
      finalize(() => this.inflight.delete(key)),
      shareReplay(1)
    );
    this.inflight.set(key, req$ as Observable<unknown>);
    return req$;
  }
}
