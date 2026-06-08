import { Component, DestroyRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize } from 'rxjs/operators';
import { AuthService } from '../../../core/services/auth.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { ThemeService } from '../../../core/services/theme.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, TranslateModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private auth = inject(AuthService);
  private router = inject(Router);
  private destroyRef = inject(DestroyRef);
  translate = inject(AppTranslateService);
  theme = inject(ThemeService);

  form = this.fb.nonNullable.group({
    username: ['', Validators.required],
    password: ['', Validators.required],
  });
  error = '';
  loading = false;

  constructor() {
    // Évite que le message d’erreur reste affiché après correction des champs.
    this.form.valueChanges.pipe(takeUntilDestroyed(this.destroyRef)).subscribe(() => {
      if (this.error) this.error = '';
    });
  }

  submit(): void {
    if (this.loading) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.error = this.translate.display(
        'FORM.LOGIN_FILL_REQUIRED',
        'Veuillez saisir votre identifiant et votre mot de passe.'
      );
      return;
    }
    this.error = '';
    this.loading = true;
    this.auth
      .login(this.form.getRawValue())
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (res) => {
          if (res.success) {
            const role = res.user?.role;
            if (role === 'Admin') this.router.navigate(['/admin/dashboard']);
            else if (role === 'Doctor') this.router.navigate(['/doctor/dashboard']);
            else if (role === 'Secretary') this.router.navigate(['/secretary/dashboard']);
            else if (role === 'Laboratory') this.router.navigate(['/lab/dashboard']);
            else if (role === 'Accountant') this.router.navigate(['/accountant/dashboard']);
            else if (role === 'Nurse') {
              const isMobile = typeof window !== 'undefined' ? window.matchMedia('(max-width: 767.98px)').matches : true;
              this.router.navigate([isMobile ? '/nurse/dashboard' : '/nurse-use-mobile']);
            }
            else if (role === 'Patient') this.router.navigate(['/patient/dashboard']);
            else {
              this.auth.clearLocalSession();
              this.error = 'Rôle non autorisé sur la version web. Utilisez l’application mobile.';
              this.router.navigate(['/login']);
            }
          } else {
            this.error =
              this.translate.apiErrorMessage(res.message) ?? this.translate.display('FORM.ERROR_LOGIN', 'Erreur de connexion');
          }
        },
        error: (err) => {
          const apiMsg = err?.error?.message ?? err?.message;
          this.error =
            this.translate.apiErrorMessage(apiMsg) ??
            this.translate.display('FORM.ERROR_NETWORK', "Erreur réseau. Vérifiez que l'API est démarrée (php artisan serve).");
        },
      });
  }
}
