import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  AbstractControl,
  FormBuilder,
  FormsModule,
  ReactiveFormsModule,
  ValidationErrors,
  ValidatorFn,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { PortalService } from '../../../core/services/portal.service';
import { Patient, Operation } from '../../../core/models/patient.model';
import { User } from '../../../core/models/user.model';

/**
 * Fiche administrative patient : coordonnées + compte mobile (réservé à l’admin).
 * Les données médicales sont saisies par le médecin depuis le suivi patient.
 */
@Component({
  selector: 'app-patient-form',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule, RouterLink],
  templateUrl: './patient-form.component.html',
  styleUrl: './patient-form.component.scss',
})
export class PatientFormComponent implements OnInit {
  private fb = inject(FormBuilder);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private api = inject(ApiService);
  private translate = inject(AppTranslateService);
  auth = inject(AuthService);
  portal = inject(PortalService);

  isEdit = false;
  id: number | null = null;
  firstOperation: Operation | null = null;

  form = this.fb.nonNullable.group({
    user_id: [0, [Validators.required, Validators.min(1)]],
    national_id: ['', [Validators.required, Validators.pattern(/^[A-Za-z0-9]{6,20}$/)]],
    first_name: [''],
    last_name: [''],
    birth_date: ['', [Validators.required]],
    admission_at: [''],
    appointment_at: [''],
    gender: ['Male', Validators.required],
    phone: [''],
    address: [''],
    chamber_number: [''],
    current_illness: ['', [Validators.maxLength(4000)]],
  });

  operationForm = this.fb.nonNullable.group({
    doctor_id: [0],
    operation_type: [''],
    operation_date: [''],
    operation_notes: [''],
  });

  error = '';
  loading = false;
  users: { id: number; name: string; username: string }[] = [];
  /** Évite d’afficher « aucun compte » avant la fin du chargement API. */
  usersLoaded = false;
  doctors: User[] = [];
  selectedDoctorId: number | null = null;
  creationMode: 'existing' | 'new' = 'existing';

  /** Compte patient mobile : admin ou secrétaire (même règles de mot de passe côté API). */
  canCreateMobileAccount(): boolean {
    return this.auth.isAdmin() || this.auth.hasRole('Secretary');
  }

  /** Rendez-vous / opération : (admin / médecin). La Réception ne doit plus voir ce bloc. */
  canScheduleOperationOnPatientForm(): boolean {
    return this.auth.isAdmin() || this.auth.isDoctor();
  }

  newUserForm = this.fb.nonNullable.group(
    {
      name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [patientFormPasswordRules]],
      password_confirmation: [''],
    },
    { validators: [patientFormPasswordsMatch] },
  );

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam && idParam !== 'new') {
      this.isEdit = true;
      this.id = +idParam;
      this.api.getPatient(this.id).subscribe({
        next: (r) => {
          const p = r.data;
          this.form.patchValue({
            user_id: p.user_id,
            national_id: p.national_id ?? '',
            first_name: p.first_name ?? '',
            last_name: p.last_name ?? '',
            birth_date: (p as unknown as { birth_date?: string | null }).birth_date
              ? String((p as unknown as { birth_date?: string | null }).birth_date).slice(0, 10)
              : '',
            admission_at: (p as unknown as { admission_at?: string | null }).admission_at
              ? String((p as unknown as { admission_at?: string | null }).admission_at).slice(0, 10)
              : '',
            appointment_at: p.appointment_at ? String(p.appointment_at).slice(0, 10) : '',
            gender: p.gender,
            phone: p.phone ?? '',
            address: p.address ?? '',
            chamber_number: p.chamber_number ?? '',
            current_illness: p.current_illness ?? '',
          });
          if (this.canScheduleOperationOnPatientForm()) {
            const ops = p.operations ?? [];
            this.firstOperation = ops.length > 0 ? ops[0] : null;
            if (this.firstOperation) {
              const d = this.firstOperation.operation_date;
              const dateStr = d ? (d.includes('T') ? d.slice(0, 10) : d.slice(0, 10)) : '';
              this.operationForm.patchValue({
                doctor_id: this.firstOperation.doctor_id ?? 0,
                operation_type: this.firstOperation.operation_type ?? '',
                operation_date: dateStr,
                operation_notes: this.firstOperation.notes ?? '',
              });
            }
          } else {
            this.firstOperation = null;
            this.operationForm.reset({ doctor_id: 0, operation_type: '', operation_date: '', operation_notes: '' });
          }
          this.selectedDoctorId = (p as unknown as { assigned_doctor_id?: number | null }).assigned_doctor_id ?? null;
          const nin = this.form.get('national_id');
          nin?.clearValidators();
          nin?.setValidators([nationalIdOptionalForEdit()]);
          nin?.updateValueAndValidity({ emitEvent: false });
        },
        error: () => (this.error = 'Patient non trouvé'),
      });

      // admin/réception: charger liste médecins (assignation + opération)
      if (this.auth.isAdmin() || this.auth.hasRole('Secretary')) {
        this.api.getDoctors().subscribe({
          next: (dr) => (this.doctors = dr.data ?? []),
          error: () => (this.doctors = []),
        });
      }
    } else {
      this.usersLoaded = false;
      this.api.getUsersForAssignment().subscribe({
        next: (r) => {
          this.users = r.data ?? [];
          this.usersLoaded = true;
          // Secrétariat: on privilégie la création auto du compte mobile (API génère username/mdp si non saisis).
          if (this.auth.hasRole('Secretary') && this.canCreateMobileAccount()) {
            this.setCreationMode('new');
          } else if (this.users.length === 0 && this.canCreateMobileAccount()) {
            this.setCreationMode('new');
          } else {
            this.setCreationMode('existing');
          }
        },
        error: () => {
          this.users = [];
          this.usersLoaded = true;
          if (this.canCreateMobileAccount()) {
            this.setCreationMode('new');
          } else {
            this.setCreationMode('existing');
          }
        },
      });
      this.auth.refreshUser().subscribe();
    }
  }

  saveAssignment(): void {
    if (!this.auth.isAdmin() || !this.id || this.loading) return;
    this.loading = true;
    this.error = '';
    this.api.adminAssignPatientDoctor(this.id, this.selectedDoctorId ?? null).subscribe({
      next: () => {
        this.loading = false;
      },
      error: (err) => {
        this.error = this.formatApiError(err);
        this.loading = false;
      },
    });
  }

  setCreationMode(mode: 'existing' | 'new'): void {
    this.creationMode = mode;
    const uid = this.form.get('user_id');
    if (mode === 'existing') {
      uid?.setValidators([Validators.required, Validators.min(1)]);
    } else {
      uid?.clearValidators();
      uid?.setValue(0);
    }
    uid?.updateValueAndValidity({ emitEvent: false });
  }

  getPatientPayload(): Partial<Patient> {
    const f = this.form.getRawValue();
    const nid = f.national_id?.replace(/[^A-Za-z0-9]/g, '').toUpperCase() ?? '';
    return {
      user_id: f.user_id,
      ...(nid ? { national_id: nid } : {}),
      first_name: f.first_name || undefined,
      last_name: f.last_name || undefined,
      birth_date: f.birth_date || undefined,
      admission_at: f.admission_at || undefined,
      appointment_at: f.appointment_at || undefined,
      gender: f.gender,
      phone: f.phone || undefined,
      address: f.address || undefined,
      chamber_number: f.chamber_number?.trim() || undefined,
      current_illness: f.current_illness?.trim() || undefined,
    };
  }

  submit(): void {
    const ninCtrl = this.form.get('national_id');
    if (ninCtrl) {
      const stripped = String(ninCtrl.value ?? '')
        .replace(/[^A-Za-z0-9]/g, '')
        .toUpperCase();
      ninCtrl.setValue(stripped, { emitEvent: false });
    }
    if (this.form.invalid || this.loading) return;
    this.error = '';
    this.loading = true;
    const body = this.getPatientPayload();
    const op = this.operationForm.getRawValue();
    const hasOperation = !!(op.operation_type?.trim() && op.operation_date);
    const seg = this.portal.seg();

    if (this.isEdit && this.id) {
      this.api.updatePatient(this.id, body).subscribe({
        next: () => {
          if (this.canScheduleOperationOnPatientForm() && hasOperation && this.id) {
            this.upsertOperation(this.id, op, seg);
          } else {
            this.router.navigate(['/', seg, 'patients', this.id]);
          }
        },
        error: (err) => {
          this.error = this.formatApiError(err);
          this.loading = false;
        },
      });
    } else {
      if (this.creationMode === 'new') {
        const nu = this.newUserForm.getRawValue();
        if (!nu.name?.trim()) {
          const f = this.form.getRawValue();
          const combined = [f.first_name, f.last_name].filter(Boolean).join(' ').trim();
          if (combined) {
            this.newUserForm.patchValue({ name: combined });
          }
        }
        this.newUserForm.markAllAsTouched();
        if (this.newUserForm.invalid) {
          this.error = 'Vérifiez les champs du compte mobile (e-mail, mot de passe).';
          this.loading = false;
          return;
        }
      }

      const payload = this.buildCreatePayload(body);
      this.api.createPatient(payload).subscribe({
        next: (r) => {
          const patientId = r.data.id;
          if (this.canScheduleOperationOnPatientForm() && hasOperation && patientId) {
            this.upsertOperation(patientId, op, seg);
          } else {
            this.router.navigate(['/', seg, 'patients', patientId]);
          }
        },
        error: (err) => {
          this.error = this.formatApiError(err);
          this.loading = false;
        },
      });
    }
  }

  private buildCreatePayload(base: Partial<Patient>): Record<string, unknown> {
    const out: Record<string, unknown> = { ...base };
    if (this.creationMode === 'new') {
      delete out['user_id'];
      const nu = this.newUserForm.getRawValue();
      const password = nu.password ?? '';
      const passwordConfirmation = nu.password_confirmation ?? '';

      out['new_user'] = {
        name: nu.name.trim(),
        email: nu.email.trim(),
        ...(password ? { password } : {}),
        ...(passwordConfirmation ? { password_confirmation: passwordConfirmation } : {}),
      };
    }
    return out;
  }

  private formatApiError(err: { error?: { message?: string; errors?: Record<string, string[]> } }): string {
    const msg = this.translate.apiErrorMessage(err.error?.message);
    const errors = err.error?.errors;
    if (errors && typeof errors === 'object') {
      const lines = Object.entries(errors).flatMap(([field, msgs]) =>
        (Array.isArray(msgs) ? msgs : [String(msgs)]).map((m) => `${field}: ${m}`)
      );
      return lines.length ? lines.join('\n') : (msg ?? 'Erreur de validation');
    }
    return msg ?? 'Erreur';
  }

  private upsertOperation(
    patientId: number,
    op: { doctor_id: number; operation_type: string; operation_date: string; operation_notes: string },
    seg: 'admin' | 'doctor' | 'nurse' | 'secretary' | 'lab' | 'accountant' | 'patient'
  ): void {
    const doctorId =
      this.auth.isDoctor() ? (this.auth.user()?.id ?? 0) : Number(op.doctor_id ?? 0);
    if (!doctorId || doctorId < 1) {
      this.error = 'Veuillez sélectionner un médecin pour l’opération.';
      this.loading = false;
      return;
    }
    const createPayload = {
      patient_id: patientId,
      doctor_id: doctorId,
      operation_type: op.operation_type.trim(),
      operation_date: op.operation_date,
      notes: op.operation_notes || undefined,
    };
    const updatePayload = {
      doctor_id: doctorId,
      operation_type: op.operation_type.trim(),
      operation_date: op.operation_date,
      notes: op.operation_notes || undefined,
    };
    if (this.firstOperation?.id) {
      this.api.updateOperation(this.firstOperation.id, updatePayload).subscribe({
        next: () => this.router.navigate(['/', seg, 'patients', patientId]),
        error: (err) => {
          this.error = err.error?.message ?? 'Erreur opération';
          this.loading = false;
        },
      });
    } else {
      this.api.createOperation(createPayload).subscribe({
        next: () => this.router.navigate(['/', seg, 'patients', patientId]),
        error: (err) => {
          this.error = err.error?.message ?? 'Erreur opération';
          this.loading = false;
        },
      });
    }
  }
}

function patientFormPasswordRules(control: AbstractControl): ValidationErrors | null {
  const v = String(control.value ?? '');
  if (!v) return null;
  if (v.length < 8) {
    return { passwordRules: true };
  }
  if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(v)) {
    return { passwordRules: true };
  }
  return null;
}

const patientFormPasswordsMatch: ValidatorFn = (group: AbstractControl): ValidationErrors | null => {
  const p = group.get('password')?.value;
  const c = group.get('password_confirmation')?.value;
  if ((p || c) && p !== c) {
    return { passwordMismatch: true };
  }
  return null;
};

/** En édition, CIN facultatif pour les dossiers anciens ; s’il est saisi il doit être valide. */
function nationalIdOptionalForEdit(): ValidatorFn {
  return (ctrl: AbstractControl): ValidationErrors | null => {
    const v = (ctrl.value ?? '').toString().replace(/[^A-Za-z0-9]/g, '');
    if (!v) return null;
    if (v.length >= 6 && v.length <= 20) return null;
    return { nationalIdPattern: true };
  };
}
