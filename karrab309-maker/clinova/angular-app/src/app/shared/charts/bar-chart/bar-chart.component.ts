import { Component, Input, OnChanges, SimpleChanges, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-bar-chart',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="chart-wrapper" [class.loading]="loading">
      <div class="chart-header" *ngIf="title">
        <h3 class="chart-title">{{ title }}</h3>
        <span class="chart-subtitle" *ngIf="subtitle">{{ subtitle }}</span>
      </div>
      <div class="chart-container" *ngIf="!loading && labels.length; else emptyState">
        <svg [attr.viewBox]="'0 0 ' + (chartWidth + 80) + ' 280'" class="chart-svg" [style.max-height]="maxHeight">
          <defs>
            <linearGradient v-bind:id="'barGrad-' + uid" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" [attr.stop-color]="colors[0]" stop-opacity="0.85"/>
              <stop offset="100%" [attr.stop-color]="colors[0]" stop-opacity="0.3"/>
            </linearGradient>
            <filter id="shadow-{{uid}}">
              <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.15"/>
            </filter>
          </defs>
          <g *ngFor="let bar of bars; let i = index">
            <rect
              [attr.x]="bar.x"
              [attr.y]="bar.y"
              [attr.width]="bar.width"
              [attr.height]="bar.height"
              [attr.fill]="bar.color"
              [attr.filter]="'url(#shadow-' + uid + ')'"
              [attr.rx]="4"
              class="chart-bar"
            >
              <animate
                attributeName="height"
                [attr.from]="0"
                [attr.to]="bar.height"
                dur="0.6s"
                begin="0s"
                fill="freeze"
              />
              <animate
                attributeName="y"
                [attr.from]="240"
                [attr.to]="bar.y"
                dur="0.6s"
                begin="0s"
                fill="freeze"
              />
            </rect>
            <text
              [attr.x]="bar.x + bar.width / 2"
              y="258"
              text-anchor="middle"
              class="chart-label"
              font-size="10"
            >{{ bar.label }}</text>
            <text
              [attr.x]="bar.x + bar.width / 2"
              [attr.y]="bar.y - 8"
              text-anchor="middle"
              class="chart-value"
              font-size="11"
              font-weight="600"
              [attr.fill]="colors[0]"
            >{{ bar.value }}</text>
          </g>
        </svg>
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
    .chart-container {
      display: flex;
      justify-content: center;
    }
    .chart-svg {
      width: 100%;
      min-height: 200px;
    }
    .chart-bar {
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .chart-bar:hover { opacity: 0.8; }
    .chart-label {
      fill: var(--text-muted);
      font-weight: 500;
    }
    .chart-value { font-family: var(--font-sans); }
    .chart-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px;
      color: var(--text-muted);
      gap: 8px;
    }
    .empty-icon { font-size: 2rem; opacity: 0.5; }
    .chart-wrapper.loading { opacity: 0.5; pointer-events: none; }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class BarChartComponent implements OnChanges {
  @Input() labels: string[] = [];
  @Input() values: number[] = [];
  @Input() colors: string[] = ['var(--primary)'];
  @Input() title = '';
  @Input() subtitle = '';
  @Input() maxHeight = '260px';
  @Input() emptyText = 'No data available';
  @Input() loading = false;

  bars: { x: number; y: number; width: number; height: number; label: string; value: number | string; color: string }[] = [];
  chartWidth = 0;
  uid = Math.random().toString(36).substring(2, 8);

  private margin = { top: 20, right: 20, bottom: 40, left: 20 };

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['labels'] || changes['values']) {
      this.renderBars();
    }
  }

  private renderBars(): void {
    if (!this.labels.length || !this.values.length) {
      this.bars = [];
      return;
    }

    const n = Math.min(this.labels.length, this.values.length);
    const svgWidth = 600;
    const chartHeight = 240;
    const drawWidth = svgWidth - this.margin.left - this.margin.right;
    const maxVal = Math.max(...this.values, 1);

    const barGap = 8;
    const totalGaps = (n - 1) * barGap;
    const barWidth = Math.min((drawWidth - totalGaps) / n, 40);
    const actualTotalWidth = n * barWidth + totalGaps;

    this.chartWidth = this.margin.left + actualTotalWidth + this.margin.right;

    this.bars = this.values.slice(0, n).map((v, i) => {
      const barHeight = (v / maxVal) * (chartHeight - this.margin.top - this.margin.bottom - 20);
      const x = this.margin.left + i * (barWidth + barGap) + (drawWidth - actualTotalWidth) / 2;
      const y = chartHeight - this.margin.bottom - barHeight;
      return {
        x,
        y,
        width: barWidth,
        height: barHeight,
        label: this.labels[i].length > 5 ? this.labels[i].substring(0, 5) + '..' : this.labels[i],
        value: v,
        color: this.colors[i % this.colors.length],
      };
    });
  }
}
