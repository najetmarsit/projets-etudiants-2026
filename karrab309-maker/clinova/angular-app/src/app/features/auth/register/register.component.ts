import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { TranslateModule, TranslateService } from '@ngx-translate/core';
import { AuthService } from '../../../core/services/auth.service';
import { AppTranslateService } from '../../../core/services/translate.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, TranslateModule],
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent {
  private fb = inject(FormBuilder);
  private auth = inject(AuthService);
  private router = inject(Router);
  private ngxTranslate = inject(TranslateService);
  translate = inject(AppTranslateService);

  form = this.fb.nonNullable.group(
    {
      name: ['', [Validators.required, Validators.minLength(2)]],
      username: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', Validators.required],
      role: ['Patient' as 'Admin' | 'Doctor' | 'Patient' | 'Laboratory' | 'Accountant', Validators.required],
    },
    {
      validators: (g) =>
        g.get('password')?.value === g.get('password_confirmation')?.value
          ? null
          : { mismatch: true },
    }
  );
  error = '';
  loading = false;
  roles = [
    { value: 'Admin', label: 'Administrateur' },
    { value: 'Doctor', label: 'Médecin' },
    { value: 'Laboratory', label: 'Laboratoire' },
    { value: 'Patient', label: 'Patient' },
    { value: 'Accountant', label: 'Comptable' },
  ] as const;

  submit(): void {
    if (this.form.invalid || this.loading) return;
    this.error = '';
    this.loading = true;
    this.auth.register(this.form.getRawValue()).subscribe({
      next: (res) => {
        this.loading = false;
        if (res.success && res.user) {
          const role = res.user.role;
          if (role === 'Patient') this.router.navigate(['/patient/dashboard']);
          else if (role === 'Admin') this.router.navigate(['/admin/dashboard']);
          else if (role === 'Doctor') this.router.navigate(['/doctor/dashboard']);
          else if (role === 'Laboratory') this.router.navigate(['/lab/dashboard']);
          else if (role === 'Accountant') this.router.navigate(['/accountant/dashboard']);
          else this.router.navigate(['/login']);
        } else {
          this.error = res.message ?? this.ngxTranslate.instant('FORM.ERROR_GENERIC');
          if (res.errors) {
            const first = Object.values(res.errors)[0];
            if (Array.isArray(first)) this.error = (first as string[])[0];
          }
        }
      },
      error: (err) => {
        this.loading = false;
        const apiError = err?.error;
        if (apiError?.errors) {
          const messages = Object.values(apiError.errors).flat() as string[];
          this.error = messages[0] ?? apiError.message ?? this.ngxTranslate.instant('FORM.ERROR_VALIDATION');
        } else {
          this.error =
            apiError?.message ??
            err?.message ??
            this.ngxTranslate.instant('FORM.ERROR_NETWORK');
        }
      },
    });
  }
}
