import { Injectable, signal, computed, effect } from '@angular/core';

const STORAGE_KEY = 'medical_dark_mode';

@Injectable({ providedIn: 'root' })
export class ThemeService {
  private dark = signal<boolean>(this.loadInitial());

  isDark = this.dark.asReadonly();

  constructor() {
    effect(() => {
      const d = this.dark();
      if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark-mode', d);
        try {
          localStorage.setItem(STORAGE_KEY, d ? '1' : '0');
        } catch {}
      }
    });
  }

  private loadInitial(): boolean {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === '1') {
        return true;
      }
      if (saved === '0') {
        return false;
      }
      if (typeof window !== 'undefined' && window.matchMedia) {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
      }
    } catch {
      /* ignore */
    }
    return false;
  }

  toggleDark(): void {
    this.dark.update((v) => !v);
  }

  setDark(value: boolean): void {
    this.dark.set(value);
  }
}
