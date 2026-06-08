import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-skeleton',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="skeleton" [class]="type + ' ' + animation" [style.width]="width" [style.height]="height" [style.border-radius]="borderRadius">
      &nbsp;
    </div>
  `,
  styles: [`
    .skeleton {
      display: block;
      background: linear-gradient(90deg, var(--border) 25%, rgba(148,163,184,0.15) 50%, var(--border) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s ease-in-out infinite;
      border-radius: 8px;
      min-height: 16px;
    }
    .skeleton.pulse {
      animation: pulse 1.5s ease-in-out infinite;
      background: var(--border);
      background-size: auto;
    }
    .skeleton.text { height: 14px; margin-bottom: 8px; }
    .skeleton.title { height: 24px; width: 60%; margin-bottom: 12px; }
    .skeleton.avatar { width: 48px; height: 48px; border-radius: 50%; }
    .skeleton.card { width: 100%; height: 120px; border-radius: 16px; }
    .skeleton.button { width: 100px; height: 38px; border-radius: 10px; }
    .skeleton.chart { width: 100%; height: 200px; border-radius: 12px; }
    .skeleton.badge { width: 60px; height: 24px; border-radius: 999px; }
    @keyframes shimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SkeletonComponent {
  @Input() type: 'text' | 'title' | 'avatar' | 'card' | 'button' | 'chart' | 'badge' = 'text';
  @Input() animation: 'shimmer' | 'pulse' = 'shimmer';
  @Input() width = '100%';
  @Input() height = '16px';
  @Input() borderRadius = '8px';
}

@Component({
  selector: 'app-skeleton-card',
  standalone: true,
  imports: [CommonModule, SkeletonComponent],
  template: `
    <div class="skeleton-card-item">
      <div class="skeleton-card-header">
        <app-skeleton type="avatar" *ngIf="showAvatar"></app-skeleton>
        <div class="skeleton-card-titles">
          <app-skeleton type="title" width="70%"></app-skeleton>
          <app-skeleton type="text" width="40%"></app-skeleton>
        </div>
      </div>
      <app-skeleton type="text" *ngFor="let _ of [].constructor(lines)"></app-skeleton>
    </div>
  `,
  styles: [`
    .skeleton-card-item {
      padding: 16px;
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
    }
    .skeleton-card-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }
    .skeleton-card-titles {
      flex: 1;
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SkeletonCardComponent {
  @Input() lines = 3;
  @Input() showAvatar = true;
}

@Component({
  selector: 'app-skeleton-table',
  standalone: true,
  imports: [CommonModule, SkeletonComponent],
  template: `
    <div class="skeleton-table">
      <div class="skeleton-table-header">
        <app-skeleton type="text" width="100%" height="32px" borderRadius="8px" *ngFor="let _ of [].constructor(columns)"></app-skeleton>
      </div>
      <div class="skeleton-table-row" *ngFor="let _ of [].constructor(rows)">
        <app-skeleton type="text" width="100%" *ngFor="let _ of [].constructor(columns)"></app-skeleton>
      </div>
    </div>
  `,
  styles: [`
    .skeleton-table { }
    .skeleton-table-header, .skeleton-table-row {
      display: grid;
      grid-template-columns: repeat(var(--columns, 3), 1fr);
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid var(--border);
    }
    .skeleton-table-header {
      --columns: v-bind(columns);
      padding-bottom: 16px;
    }
    .skeleton-table-row {
      --columns: v-bind(columns);
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SkeletonTableComponent {
  @Input() rows = 5;
  @Input() columns = 4;
}
