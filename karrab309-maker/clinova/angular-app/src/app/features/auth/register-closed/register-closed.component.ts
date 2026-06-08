import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';

@Component({
  selector: 'app-register-closed',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule],
  template: `
    <div class="auth-page min-vh-100 d-flex align-items-center justify-content-center p-4">
      <div class="card shadow-sm border-0" style="max-width: 420px">
        <div class="card-body p-4 text-center">
          <h1 class="h5 fw-bold mb-3">{{ 'FORM.REGISTER_CLOSED_TITLE' | translate }}</h1>
          <p class="text-muted small mb-4">{{ 'FORM.REGISTER_CLOSED_TEXT' | translate }}</p>
          <a routerLink="/login" class="btn btn-primary">{{ 'FORM.SIGN_IN' | translate }}</a>
        </div>
      </div>
    </div>
  `,
})
export class RegisterClosedComponent {}
