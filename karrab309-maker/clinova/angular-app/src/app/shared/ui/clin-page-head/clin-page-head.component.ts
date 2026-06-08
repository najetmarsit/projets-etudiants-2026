import { Component, Input } from '@angular/core';

/** En-tête de page mobile / desktop — titre + sous-titre + icône Bootstrap optionnelle. */
@Component({
  selector: 'app-clin-page-head',
  standalone: true,
  imports: [],
  template: `
    <header class="clin-page-head">
      @if (icon) {
        <div class="clin-page-head__icon" aria-hidden="true">
          <i [class]="'bi ' + icon"></i>
        </div>
      }
      <div class="clin-page-head__text">
        <h1 class="clin-page-head__title">{{ title }}</h1>
        @if (subtitle) {
          <p class="clin-page-head__subtitle">{{ subtitle }}</p>
        }
      </div>
      <div class="clin-page-head__actions">
        <ng-content />
      </div>
    </header>
  `,
  styles: [
    `
      .clin-page-head {
        display: flex;
        align-items: flex-start;
        gap: var(--space-4, 16px);
        margin-bottom: var(--space-5, 20px);
        flex-wrap: wrap;
      }
      .clin-page-head__icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--color-info-soft, rgba(37, 99, 235, 0.12));
        color: var(--color-info, #2563eb);
        font-size: 1.35rem;
        flex-shrink: 0;
      }
      .clin-page-head__text {
        flex: 1;
        min-width: 0;
      }
      .clin-page-head__title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--text);
        line-height: 1.2;
      }
      .clin-page-head__subtitle {
        margin: 0.35rem 0 0;
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--text-muted);
        line-height: 1.45;
      }
      .clin-page-head__actions {
        display: flex;
        align-items: center;
        gap: var(--space-2, 8px);
        margin-inline-start: auto;
      }
      html.dark-mode .clin-page-head__icon {
        background: rgba(96, 165, 250, 0.15);
        color: #93c5fd;
      }
    `,
  ],
})
export class ClinPageHeadComponent {
  @Input({ required: true }) title = '';
  @Input() subtitle = '';
  /** Classe Bootstrap Icons complète, ex. `bi-house-heart-fill` */
  @Input() icon = '';
}
