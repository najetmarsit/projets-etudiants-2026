import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { AppTranslateService } from '../../../core/services/translate.service';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, TranslateModule],
  templateUrl: './user-list.component.html',
  styleUrl: './user-list.component.scss',
})
export class UserListComponent implements OnInit {
  auth = inject(AuthService);
  private api = inject(ApiService);
  private fb = inject(FormBuilder);
  private tr = inject(AppTranslateService);

  form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    username: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    password: [''],
    role: ['Patient' as string, Validators.required],
    specialty: [''],
    send_credentials: [false],
    phone: [''],
  });

  ngOnInit(): void {
    this.applySpecialtyValidators(this.form.getRawValue().role);
    this.form.get('role')?.valueChanges.subscribe((role) => this.applySpecialtyValidators(role));
  }

  private applySpecialtyValidators(role: string): void {
    const spec = this.form.get('specialty');
    if (role === 'Doctor') {
      spec?.setValidators([
        Validators.required,
        Validators.minLength(2),
        Validators.maxLength(191),
      ]);
    } else {
      spec?.clearValidators();
      spec?.setValue('', { emitEvent: false });
    }
    spec?.updateValueAndValidity({ emitEvent: false });
  }

  /** Valeurs alignées sur `AdminUserController` Laravel : in:Admin,Doctor,Nurse,Secretary,Patient,Laboratory,Accountant */
  roles = [
    { value: 'Admin', labelKey: 'Administrator' },
    { value: 'Doctor', labelKey: 'Doctor' },
    { value: 'Nurse', labelKey: 'Nurse' },
    { value: 'Secretary', labelKey: 'Secrétaire' },
    { value: 'Laboratory', labelKey: 'Laboratory' },
    { value: 'Accountant', labelKey: 'Comptable' },
  ] as const;

  loading = false;
  message = '';
  error = '';
  generatedPassword: string | null = null;

  submit(): void {
    if (!this.auth.isAdmin()) {
      this.error = this.tr.display(
        'Accès refusé. Seul l’administrateur peut créer des comptes.',
        'Accès refusé. Seul l’administrateur peut créer des comptes.'
      );
      return;
    }
    if (this.form.invalid || this.loading) return;
    this.message = '';
    this.error = '';
    this.generatedPassword = null;
    this.loading = true;
    const v = this.form.getRawValue();
    const body: {
      name: string;
      username: string;
      email: string;
      password?: string;
      role: string;
      specialty?: string;
      send_credentials?: boolean;
      phone?: string;
    } = {
      name: v.name,
      username: v.username,
      email: v.email,
      role: v.role,
      send_credentials: v.send_credentials,
      phone: v.phone || undefined,
    };
    if (v.password?.trim()) {
      body.password = v.password.trim();
    }
    if (v.role === 'Doctor') {
      body.specialty = v.specialty.trim();
    }
    this.api.adminCreateUser(body).subscribe({
      next: (r) => {
        this.loading = false;
        if (r.success) {
          this.message = r.message ?? 'Utilisateur créé.';
          this.generatedPassword = r.generated_password ?? null;
          this.form.reset({
            name: '',
            username: '',
            email: '',
            password: '',
            role: 'Patient',
            specialty: '',
            send_credentials: false,
            phone: '',
          });
          this.applySpecialtyValidators('Patient');
        } else {
          this.error = r.message ?? 'Erreur';
        }
      },
      error: (err) => {
        this.loading = false;
        const e = err?.error;
        this.error = e?.message ?? 'Erreur réseau';
        if (e?.errors) {
          const first = Object.values(e.errors)[0];
          if (Array.isArray(first)) this.error = (first as string[])[0];
        }
      },
    });
  }
}
