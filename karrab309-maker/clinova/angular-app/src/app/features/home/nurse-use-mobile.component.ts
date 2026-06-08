import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ThemeService } from '../../core/services/theme.service';

@Component({
  selector: 'app-nurse-use-mobile',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule],
  template: `
    <div class="container py-5">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-4 d-inline-flex align-items-center justify-content-center"
                 style="width:54px;height:54px;background:rgba(14,116,144,.12);color:#0e7490;">
              <i class="bi bi-clipboard2-pulse-fill" style="font-size:1.55rem" aria-hidden="true"></i>
            </div>
            <div class="min-w-0">
              <h1 class="h4 fw-bold mb-1">{{ 'Espace infirmier' | translate }}</h1>
              <p class="text-muted mb-0">{{ 'Disponible uniquement sur mobile.' | translate }}</p>
            </div>
          </div>

          <p class="mb-4 text-body-secondary">
            {{ 'Pour une meilleure expérience (tâches, constantes, alertes), merci d’utiliser un téléphone.' | translate }}
          </p>

          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary rounded-pill px-4" routerLink="/login">
              <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>{{ 'Retour à la connexion' | translate }}
            </a>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class NurseUseMobileComponent {
  // Pour garder la cohérence visuelle avec le thème global (classe html.dark-mode déjà gérée ailleurs).
  readonly theme = inject(ThemeService);
}

