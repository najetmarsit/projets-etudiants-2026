import { Component, OnInit, OnDestroy, ChangeDetectionStrategy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ToastService, Toast } from './toast.service';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';

@Component({
  selector: 'app-toast-container',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="toast-container">
      <div *ngFor="let toast of activeToasts" class="toast-item" [class]="'toast-' + toast.type"
        [style.animation-delay]="'0s'">
        <span class="toast-icon">{{ toast.icon }}</span>
        <span class="toast-message">{{ toast.message }}</span>
        <button class="toast-close" (click)="dismiss(toast.id)">✕</button>
        <div class="toast-progress" [style.animation-duration]="(toast.duration || 4000) + 'ms'"></div>
      </div>
    </div>
  `,
  styles: [`
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-width: 400px;
    }
    .toast-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-radius: var(--radius-sm);
      background: var(--surface);
      border: 1px solid var(--border);
      box-shadow: 0 12px 40px rgba(0,0,0,0.12);
      animation: toastIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      position: relative;
    }
    @keyframes toastIn {
      from { opacity: 0; transform: translateX(100%) scale(0.9); }
      to { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastOut {
      from { opacity: 1; transform: translateX(0); }
      to { opacity: 0; transform: translateX(100%); }
    }
    .toast-icon {
      flex-shrink: 0;
      width: 28px; height: 28px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 50%;
      font-size: 0.85rem;
      font-weight: 700;
    }
    .toast-message { flex: 1; font-size: 0.9rem; font-weight: 500; color: var(--text); }
    .toast-close {
      background: none; border: none; color: var(--text-muted);
      cursor: pointer; font-size: 0.8rem; padding: 4px;
      opacity: 0.6; transition: opacity 0.2s;
    }
    .toast-close:hover { opacity: 1; }
    .toast-success .toast-icon { background: rgba(13,148,136,0.12); color: var(--primary-dark); }
    .toast-error .toast-icon { background: rgba(239,68,68,0.12); color: var(--danger); }
    .toast-warning .toast-icon { background: rgba(234,179,8,0.12); color: #a16207; }
    .toast-info .toast-icon { background: rgba(99,102,241,0.12); color: var(--violet); }
    .toast-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 3px;
      background: var(--primary);
      animation: toastProgress linear forwards;
    }
    .toast-error .toast-progress { background: var(--danger); }
    .toast-warning .toast-progress { background: var(--warning); }
    .toast-info .toast-progress { background: var(--violet); }
    @keyframes toastProgress {
      from { width: 100%; }
      to { width: 0%; }
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ToastContainerComponent implements OnInit, OnDestroy {
  private toastService = inject(ToastService);
  private destroy$ = new Subject<void>();

  activeToasts: (Toast & { removing?: boolean })[] = [];

  ngOnInit(): void {
    this.toastService.toasts$.pipe(takeUntil(this.destroy$)).subscribe((toast) => {
      this.activeToasts.push(toast);
      setTimeout(() => this.dismiss(toast.id), toast.duration || 4000);
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  dismiss(id: number): void {
    const idx = this.activeToasts.findIndex((t) => t.id === id);
    if (idx > -1) {
      this.activeToasts.splice(idx, 1);
    }
  }
}
