import { Component, Input, OnChanges, SimpleChanges, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-line-chart',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="chart-wrapper" [class.loading]="loading">
      <div class="chart-header" *ngIf="title">
        <h3 class="chart-title">{{ title }}</h3>
        <span class="chart-subtitle" *ngIf="subtitle">{{ subtitle }}</span>
      </div>
      <div class="chart-container" *ngIf="!loading && labels.length; else emptyState">
        <svg [attr.viewBox]="'0 0 ' + width + ' ' + height" class="chart-svg" [style.max-height]="maxHeight">
          <defs>
            <linearGradient [attr.id]="'lineGrad-' + uid" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" [attr.stop-color]="lineColor" stop-opacity="0.25"/>
              <stop offset="100%" [attr.stop-color]="lineColor" stop-opacity="0.02"/>
            </linearGradient>
          </defs>
          <!-- Grid lines -->
          <line *ngFor="let gl of gridLines" [attr.x1]="gl.x1" [attr.y1]="gl.y1" [attr.x2]="gl.x2" [attr.y2]="gl.y2"
            stroke="var(--border)" stroke-width="1" stroke-dasharray="4,4"/>
          <text *ngFor="let gl of gridLines" [attr.x]="8" [attr.y]="gl.y1 + 4"
            font-size="9" fill="var(--text-muted)">{{ gl.label }}</text>
          <!-- Area fill -->
          <path [attr.d]="areaPath" [attr.fill]="'url(#lineGrad-' + uid + ')'" opacity="0.6"/>
          <!-- Line -->
          <path [attr.d]="linePath" [attr.stroke]="lineColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"
            class="chart-line-path">
            <animate attributeName="stroke-dashoffset" from="2000" to="0" dur="1.2s" fill="freeze"/>
          </path>
          <!-- Dots -->
          <circle *ngFor="let pt of points" [attr.cx]="pt.x" [attr.cy]="pt.y" r="4"
            [attr.fill]="pt.color" stroke="var(--surface)" stroke-width="2" class="chart-dot">
            <animate attributeName="r" from="0" to="4" dur="0.3s" begin="0.8s" fill="freeze"/>
          </circle>
          <!-- Labels -->
          <text *ngFor="let pt of points" [attr.x]="pt.x" [attr.y]="height - 12"
            text-anchor="middle" font-size="9" fill="var(--text-muted)" font-weight="500">{{ pt.label }}</text>
        </svg>
      </div>
      <ng-template #emptyState>
        <div class="chart-empty">
          <span class="empty-icon">📈</span>
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
    .chart-container { display: flex; justify-content: center; }
    .chart-svg { width: 100%; min-height: 200px; }
    .chart-line-path { stroke-dasharray: 2000; }
    .chart-dot { cursor: pointer; transition: r 0.2s; }
    .chart-dot:hover { r: 6; filter: brightness(1.2); }
    .chart-empty {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 40px; color: var(--text-muted); gap: 8px;
    }
    .empty-icon { font-size: 2rem; opacity: 0.5; }
    .chart-wrapper.loading { opacity: 0.5; pointer-events: none; }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LineChartComponent implements OnChanges {
  @Input() labels: string[] = [];
  @Input() values: number[] = [];
  @Input() lineColor = 'var(--primary)';
  @Input() title = '';
  @Input() subtitle = '';
  @Input() height = 280;
  @Input() width = 600;
  @Input() maxHeight = '260px';
  @Input() emptyText = 'No data available';
  @Input() loading = false;

  points: { x: number; y: number; label: string; color: string }[] = [];
  linePath = '';
  areaPath = '';
  gridLines: { x1: number; y1: number; x2: number; y2: number; label: string }[] = [];
  uid = Math.random().toString(36).substring(2, 8);

  private padding = { top: 20, right: 20, bottom: 40, left: 50 };

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['labels'] || changes['values']) {
      this.render();
    }
  }

  private render(): void {
    if (!this.labels.length || !this.values.length) {
      this.points = [];
      return;
    }

    const n = Math.min(this.labels.length, this.values.length);
    const drawW = this.width - this.padding.left - this.padding.right;
    const drawH = this.height - this.padding.top - this.padding.bottom;
    const maxVal = Math.max(...this.values, 1);
    const minVal = Math.min(...this.values, 0);

    const range = maxVal - minVal || 1;

    this.points = this.values.slice(0, n).map((v, i) => ({
      x: this.padding.left + (i / Math.max(n - 1, 1)) * drawW,
      y: this.padding.top + drawH - ((v - minVal) / range) * drawH,
      label: this.labels[i].length > 4 ? this.labels[i].substring(0, 4) : this.labels[i],
      color: this.lineColor,
    }));

    const lineParts = this.points.map((p, i) =>
      `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`
    );
    this.linePath = lineParts.join(' ');

    const areaStart = `M${this.points[0].x.toFixed(1)},${this.padding.top + drawH}`;
    const areaEnd = `L${this.points[this.points.length - 1].x.toFixed(1)},${this.padding.top + drawH}Z`;
    this.areaPath = `${areaStart}${lineParts.join(' ')}${areaEnd}`;

    const gridCount = 4;
    this.gridLines = Array.from({ length: gridCount + 1 }, (_, i) => {
      const y = this.padding.top + (i / gridCount) * drawH;
      const val = maxVal - (i / gridCount) * range;
      return {
        x1: this.padding.left,
        y1: y,
        x2: this.width - this.padding.right,
        y2: y,
        label: val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val.toFixed(0),
      };
    });
  }
}
