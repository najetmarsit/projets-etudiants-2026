import { Component, inject, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GlobalLoaderService } from '../../../core/services/global-loader.service';

@Component({
  selector: 'app-global-loader',
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (loader.visible()) {
      <div class="global-loader" role="status" aria-live="polite" aria-label="Chargement">
        <div class="global-loader__bar"></div>
        <div class="global-loader__pulse"></div>
      </div>
    }
  `,
  styles: [`
    .global-loader {
      position: fixed;
      inset: 0;
      z-index: 10000;
      pointer-events: none;
    }
    .global-loader__bar {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--violet), var(--primary));
      background-size: 200% 100%;
      animation: loader-bar 1.2s ease-in-out infinite;
    }
    .global-loader__pulse {
      position: absolute;
      top: 12px;
      right: 16px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--primary);
      opacity: 0.85;
      animation: loader-pulse 1s ease-in-out infinite;
    }
    @keyframes loader-bar {
      0% { background-position: 0% 50%; }
      100% { background-position: 200% 50%; }
    }
    @keyframes loader-pulse {
      0%, 100% { transform: scale(1); opacity: 0.85; }
      50% { transform: scale(1.35); opacity: 0.45; }
    }
  `],
})
export class GlobalLoaderComponent {
  loader = inject(GlobalLoaderService);
}
