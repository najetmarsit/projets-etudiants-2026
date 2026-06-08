import { Component, Input, AfterViewInit, ElementRef, ViewChild, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-stat-card',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="stat-card" [class.clickable]="clickable" [class.loading]="loading" (click)="onClick()">
      <div class="stat-icon" *ngIf="icon">
        <span class="stat-icon-inner">{{ icon }}</span>
      </div>
      <div class="stat-body">
        <div class="stat-label">{{ label }}</div>
        <div class="stat-value" #counterRef>{{ loading ? '...' : displayValue }}</div>
        <div class="stat-footer" *ngIf="footer">
          <span class="stat-change" [class.positive]="trend === 'up'" [class.negative]="trend === 'down'">
            <span *ngIf="trend === 'up'">↑</span>
            <span *ngIf="trend === 'down'">↓</span>
            {{ footer }}
          </span>
        </div>
      </div>
      <div class="stat-glow"></div>
    </div>
  `,
  styles: [`
    .stat-card {
      position: relative;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 20px;
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      box-shadow: var(--shadow), var(--shadow-ring);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                  box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }
    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-lg), var(--shadow-ring);
    }
    .stat-card.clickable { cursor: pointer; }
    .stat-card.loading { opacity: 0.6; pointer-events: none; }
    .stat-glow {
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
      opacity: 0;
      transition: opacity 0.4s;
      pointer-events: none;
    }
    .stat-card:hover .stat-glow { opacity: 0.15; }
    .stat-icon {
      flex-shrink: 0;
      width: 48px;
      height: 48px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--primary-light), transparent);
      font-size: 1.4rem;
    }
    .stat-icon-inner { line-height: 1; }
    .stat-body { flex: 1; min-width: 0; }
    .stat-label {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 4px;
    }
    .stat-value {
      font-size: 1.75rem;
      font-weight: 900;
      letter-spacing: -0.03em;
      color: var(--text);
      line-height: 1.2;
    }
    .stat-footer { margin-top: 8px; }
    .stat-change {
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(148, 163, 184, 0.1);
      color: var(--text-muted);
    }
    .stat-change.positive {
      background: rgba(13, 148, 136, 0.1);
      color: var(--primary-dark);
    }
    .stat-change.negative {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StatCardComponent {
  @Input() label = '';
  @Input() value: number | string = 0;
  @Input() icon = '';
  @Input() footer = '';
  @Input() trend: 'up' | 'down' | 'neutral' = 'neutral';
  @Input() loading = false;
  @Input() clickable = false;
  @Input() format: 'number' | 'currency' | 'none' = 'number';

  get displayValue(): string {
    if (this.format === 'currency') {
      const n = typeof this.value === 'string' ? parseFloat(this.value) : this.value;
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD', minimumFractionDigits: 0 }).format(n);
    }
    if (typeof this.value === 'number' && this.value >= 1000) {
      return new Intl.NumberFormat('fr-FR').format(this.value);
    }
    return String(this.value);
  }

  onClick(): void {}
}
