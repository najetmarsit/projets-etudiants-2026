import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService, PaymentRecord, PostPaymentBody } from '../../../core/services/api.service';

@Component({
  selector: 'app-admin-payments',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule],
  templateUrl: './admin-payments.component.html',
  styleUrl: './admin-payments.component.scss',
})
export class AdminPaymentsComponent implements OnInit {
  private api = inject(ApiService);

  rows: PaymentRecord[] = [];
  loading = false;
  error: string | null = null;
  saving = false;
  saveError: string | null = null;
  saveOk: string | null = null;
  balanceHint: string | null = null;
  loadingBalance = false;

  form: PostPaymentBody & { patient_id: number; amount: number; total_amount: number; paid_at: string } = {
    patient_id: 0,
    amount: 0,
    total_amount: 0,
    currency: 'TND',
    paid_at: '',
    status: 'paid',
  };

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = null;
    this.api.getPayments().subscribe({
      next: (r) => {
        this.loading = false;
        if (r.success && r.data) {
          this.rows = r.data;
        } else {
          this.error = 'Erreur chargement';
        }
      },
      error: () => {
        this.loading = false;
        this.error = 'Erreur réseau';
      },
    });
  }

  /** Remplit le « total facturé » à partir du dossier patient (montant dû). */
  loadPatientBalance(): void {
    this.balanceHint = null;
    if (!this.form.patient_id || this.form.patient_id < 1) {
      this.balanceHint = 'Indiquez un ID patient valide.';
      return;
    }
    this.loadingBalance = true;
    this.api.getPaymentBalance(this.form.patient_id).subscribe({
      next: (r) => {
        this.loadingBalance = false;
        if (r.success && r.data) {
          this.form.total_amount = r.data.total_due;
          this.balanceHint = `Dû: ${this.formatMoney(r.data.total_due)} — Déjà payé: ${this.formatMoney(r.data.total_paid)} — Reste: ${this.formatMoney(r.data.remaining)}`;
        } else {
          this.balanceHint = 'Solde introuvable.';
        }
      },
      error: () => {
        this.loadingBalance = false;
        this.balanceHint = 'Erreur lors du chargement du solde.';
      },
    });
  }

  submit(): void {
    this.saveError = null;
    this.saveOk = null;
    if (!this.form.patient_id || this.form.patient_id < 1) {
      this.saveError = 'ID patient requis';
      return;
    }
    if (this.form.amount <= 0) {
      this.saveError = 'Le montant versé doit être supérieur à 0';
      return;
    }
    if (this.form.total_amount < 0) {
      this.saveError = 'Montant total facturé invalide';
      return;
    }
    if (this.form.status === 'paid' && this.form.paid_at) {
      const d = new Date(this.form.paid_at);
      if (Number.isNaN(d.getTime())) {
        this.saveError = 'Date de paiement invalide';
        return;
      }
    }

    const body: PostPaymentBody = {
      patient_id: this.form.patient_id,
      amount: this.form.amount,
      currency: this.form.currency || 'TND',
      status: this.form.status ?? 'paid',
    };
    if (this.form.total_amount > 0) {
      body.total_amount = this.form.total_amount;
    }
    if (this.form.paid_at && this.form.status === 'paid') {
      body.paid_at = this.toIsoOrLocal(this.form.paid_at);
    }

    this.saving = true;
    this.api.postPayment(body).subscribe({
      next: (r) => {
        this.saving = false;
        if (r.success) {
          this.saveOk = 'Paiement enregistré';
          this.form.amount = 0;
          this.form.total_amount = 0;
          this.form.paid_at = '';
          this.form.status = 'paid';
          this.balanceHint = null;
          this.load();
        } else {
          this.saveError = r.message ?? 'Échec';
        }
      },
      error: (e) => {
        this.saving = false;
        const err = e?.error;
        if (err?.errors) {
          const first = Object.values(err.errors)[0];
          this.saveError = Array.isArray(first) ? (first as string[])[0] : err.message ?? 'Erreur';
        } else {
          this.saveError = err?.message ?? 'Erreur';
        }
      },
    });
  }

  /** datetime-local → chaîne acceptée par Laravel (Carbon). */
  private toIsoOrLocal(v: string): string {
    const d = new Date(v);
    if (!Number.isNaN(d.getTime())) {
      return d.toISOString();
    }
    return v;
  }

  formatMoney(n: number | string): string {
    const val = typeof n === 'string' ? parseFloat(n) : n;
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
  }
}
