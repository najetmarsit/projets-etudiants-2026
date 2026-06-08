import { Component, Input, OnChanges, SimpleChanges, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-pie-chart',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="chart-wrapper" [class.loading]="loading">
      <div class="chart-header" *ngIf="title">
        <h3 class="chart-title">{{ title }}</h3>
        <span class="chart-subtitle" *ngIf="subtitle">{{ subtitle }}</span>
      </div>
      <div class="chart-body" *ngIf="!loading && slices.length; else emptyState">
        <svg [attr.viewBox]="'0 0 ' + size + ' ' + size" class="chart-svg" [style.max-width]="size + 'px'">
          <g *ngFor="let slice of slices; let i = index">
            <path [attr.d]="slice.path" [attr.fill]="slice.color" stroke="var(--surface)" stroke-width="2"
              class="pie-slice" (mouseenter)="hoveredIndex = i" (mouseleave)="hoveredIndex = -1"
              [class.active]="hoveredIndex === i">
              <animate attributeName="opacity" from="0" to="1" dur="0.4s" [attr.begin]="i * 0.1 + 's'" fill="freeze"/>
            </path>
          </g>
          <circle [attr.cx]="size / 2" [attr.cy]="size / 2" r="35" fill="var(--surface)" opacity="0.95"/>
          <text [attr.x]="size / 2" [attr.y]="size / 2 - 6" text-anchor="middle"
            font-size="18" font-weight="800" [attr.fill]="'var(--text)'">{{ total }}</text>
          <text [attr.x]="size / 2" [attr.y]="size / 2 + 12" text-anchor="middle"
            font-size="10" fill="var(--text-muted)" font-weight="500">Total</text>
        </svg>
        <div class="chart-legend">
          <div class="legend-item" *ngFor="let slice of slices; let i = index"
            (mouseenter)="hoveredIndex = i" (mouseleave)="hoveredIndex = -1"
            [class.active]="hoveredIndex === i">
            <span class="legend-dot" [style.background]="slice.color"></span>
            <span class="legend-label">{{ slice.label }}</span>
            <span class="legend-value">{{ slice.percentage }}%</span>
          </div>
        </div>
      </div>
      <ng-template #emptyState>
        <div class="chart-empty">
          <span class="empty-icon">📊</span>
          <span>{{ emptyText }}</span>
        </div>
      </ng-template>
    </div>
  `,
  styles: [`
    .chart-wrapper {
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 20px;
      transition: var(--transition);
    }
    .chart-wrapper:hover { box-shadow: var(--shadow-lg); }
    .chart-header { margin-bottom: 16px; }
    .chart-title {
      margin: 0;
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: -0.02em;
    }
    .chart-subtitle {
      font-size: 0.8rem;
      color: var(--text-muted);
      font-weight: 500;
    }
    .chart-body {
      display: flex;
      align-items: center;
      gap: 24px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .chart-svg { width: 100%; }
    .pie-slice { cursor: pointer; transition: transform 0.2s, opacity 0.2s; }
    .pie-slice.active { opacity: 0.8; transform: scale(1.02); }
    .chart-legend { display: flex; flex-direction: column; gap: 8px; min-width: 120px; }
    .legend-item {
      display: flex; align-items: center; gap: 8px; cursor: pointer;
      padding: 4px 8px; border-radius: 8px; transition: background 0.2s;
    }
    .legend-item.active { background: rgba(148,163,184,0.1); }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .legend-label { flex: 1; font-size: 0.85rem; font-weight: 500; color: var(--text); }
    .legend-value { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); }
    .chart-empty {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 40px; color: var(--text-muted); gap: 8px;
    }
    .empty-icon { font-size: 2rem; opacity: 0.5; }
    .chart-wrapper.loading { opacity: 0.5; pointer-events: none; }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PieChartComponent implements OnChanges {
  @Input() labels: string[] = [];
  @Input() values: number[] = [];
  @Input() colors: string[] = ['var(--primary)', 'var(--violet)', 'var(--accent)', 'var(--danger)', 'var(--warning)', '#06b6d4', '#84cc16', '#a78bfa'];
  @Input() title = '';
  @Input() subtitle = '';
  @Input() size = 200;
  @Input() emptyText = 'No data available';
  @Input() loading = false;

  slices: { path: string; color: string; label: string; percentage: number }[] = [];
  total = 0;
  hoveredIndex = -1;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['labels'] || changes['values']) {
      this.render();
    }
  }

  private render(): void {
    if (!this.labels.length || !this.values.length) {
      this.slices = [];
      return;
    }

    const n = Math.min(this.labels.length, this.values.length);
    this.total = this.values.slice(0, n).reduce((a, b) => a + b, 0);
    if (this.total === 0) {
      this.slices = [];
      return;
    }

    const cx = this.size / 2;
    const cy = this.size / 2;
    const r = this.size / 2 - 10;
    let currentAngle = -Math.PI / 2;

    this.slices = this.values.slice(0, n).map((v, i) => {
      const sliceAngle = (v / this.total) * 2 * Math.PI;
      const startAngle = currentAngle;
      const endAngle = currentAngle + sliceAngle;
      currentAngle = endAngle;

      const x1 = cx + r * Math.cos(startAngle);
      const y1 = cy + r * Math.sin(startAngle);
      const x2 = cx + r * Math.cos(endAngle);
      const y2 = cy + r * Math.sin(endAngle);

      const largeArc = sliceAngle > Math.PI ? 1 : 0;
      const path = `M${cx},${cy} L${x1.toFixed(1)},${y1.toFixed(1)} A${r},${r} 0 ${largeArc},1 ${x2.toFixed(1)},${y2.toFixed(1)} Z`;

      return {
        path,
        color: this.colors[i % this.colors.length],
        label: this.labels[i],
        percentage: Math.round((v / this.total) * 100),
      };
    });
  }
}
