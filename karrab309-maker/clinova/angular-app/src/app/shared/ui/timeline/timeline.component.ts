import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

export interface TimelineEvent {
  date: string;
  title: string;
  description?: string;
  type: 'consultation' | 'operation' | 'report' | 'alert' | 'admission' | 'lab' | 'note';
  user?: string;
  metadata?: Record<string, string>;
}

@Component({
  selector: 'app-timeline',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="timeline">
      <div class="timeline-header" *ngIf="title">
        <h3 class="timeline-title">{{ title }}</h3>
      </div>
      <div class="timeline-body">
        <div class="timeline-empty" *ngIf="!events.length">
          <span class="empty-icon">📋</span>
          <span>{{ emptyText }}</span>
        </div>
        <div class="timeline-item" *ngFor="let event of events; let i = index"
          [style.animation-delay]="i * 0.05 + 's'">
          <div class="timeline-dot" [class]="'dot-' + event.type">
            <span class="dot-icon">{{ getIcon(event.type) }}</span>
          </div>
          <div class="timeline-content">
            <div class="timeline-date">{{ event.date }}</div>
            <div class="timeline-title">{{ event.title }}</div>
            <div class="timeline-desc" *ngIf="event.description">{{ event.description }}</div>
            <div class="timeline-footer" *ngIf="event.user">
              <span class="timeline-user">{{ event.user }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .timeline {
      position: relative;
      padding: 20px;
    }
    .timeline-header { margin-bottom: 20px; }
    .timeline-title {
      margin: 0;
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: -0.02em;
    }
    .timeline-body { position: relative; }
    .timeline-body::before {
      content: '';
      position: absolute;
      left: 20px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: var(--border);
    }
    .timeline-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      padding: 40px 20px;
      color: var(--text-muted);
      font-size: 0.9rem;
    }
    .empty-icon { font-size: 2rem; opacity: 0.5; }
    .timeline-item {
      position: relative;
      display: flex;
      gap: 16px;
      padding-bottom: 24px;
      animation: fadeInUp 0.4s ease-out forwards;
      opacity: 0;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot {
      position: relative;
      z-index: 1;
      flex-shrink: 0;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      border: 2px solid var(--border);
      background: var(--surface);
    }
    .dot-consultation { border-color: var(--primary); background: var(--primary-light); }
    .dot-operation { border-color: var(--violet); background: var(--violet-50, #eef2ff); }
    .dot-report { border-color: var(--accent); background: var(--accent-light); }
    .dot-alert { border-color: var(--danger); background: rgba(239,68,68,0.1); }
    .dot-admission { border-color: #06b6d4; background: rgba(6,182,212,0.1); }
    .dot-lab { border-color: #84cc16; background: rgba(132,204,22,0.1); }
    .dot-note { border-color: #a78bfa; background: rgba(167,139,250,0.1); }
    .dot-icon { line-height: 1; }
    .timeline-content {
      flex: 1;
      padding-top: 6px;
    }
    .timeline-date {
      font-size: 0.75rem;
      color: var(--text-muted);
      font-weight: 600;
      margin-bottom: 2px;
    }
    .timeline-title {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 4px;
    }
    .timeline-desc {
      font-size: 0.8rem;
      color: var(--text-muted);
      line-height: 1.5;
    }
    .timeline-footer { margin-top: 6px; }
    .timeline-user {
      font-size: 0.75rem;
      color: var(--primary);
      font-weight: 600;
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TimelineComponent {
  @Input() events: TimelineEvent[] = [];
  @Input() title = '';
  @Input() emptyText = 'Aucun événement';

  getIcon(type: TimelineEvent['type']): string {
    switch (type) {
      case 'consultation': return '🩺';
      case 'operation': return '🏥';
      case 'report': return '📄';
      case 'alert': return '🔔';
      case 'admission': return '🚪';
      case 'lab': return '🔬';
      case 'note': return '📝';
    }
  }
}
