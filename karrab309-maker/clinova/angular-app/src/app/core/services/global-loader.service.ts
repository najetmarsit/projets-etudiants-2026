import { Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class GlobalLoaderService {
  private pending = 0;
  readonly visible = signal(false);

  show(): void {
    this.pending++;
    if (this.pending === 1) {
      this.visible.set(true);
    }
  }

  hide(): void {
    this.pending = Math.max(0, this.pending - 1);
    if (this.pending === 0) {
      this.visible.set(false);
    }
  }
}
