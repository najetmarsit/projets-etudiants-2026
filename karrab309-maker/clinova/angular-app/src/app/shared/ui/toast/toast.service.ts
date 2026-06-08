import { Injectable, inject, ComponentRef, ApplicationRef, createComponent, EnvironmentInjector } from '@angular/core';
import { Subject } from 'rxjs';

export interface Toast {
  id: number;
  message: string;
  type: 'success' | 'error' | 'warning' | 'info';
  duration?: number;
  icon?: string;
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  private toasts = new Subject<Toast>();
  toasts$ = this.toasts.asObservable();
  private counter = 0;

  show(message: string, type: Toast['type'] = 'info', duration = 4000, icon?: string): void {
    const toast: Toast = {
      id: ++this.counter,
      message,
      type,
      duration,
      icon: icon || this.defaultIcon(type),
    };
    this.toasts.next(toast);
  }

  success(message: string, duration = 4000): void {
    this.show(message, 'success', duration);
  }

  error(message: string, duration = 5000): void {
    this.show(message, 'error', duration);
  }

  warning(message: string, duration = 4000): void {
    this.show(message, 'warning', duration);
  }

  info(message: string, duration = 3000): void {
    this.show(message, 'info', duration);
  }

  private defaultIcon(type: Toast['type']): string {
    switch (type) {
      case 'success': return '✓';
      case 'error': return '✕';
      case 'warning': return '⚠';
      case 'info': return 'ℹ';
    }
  }
}
