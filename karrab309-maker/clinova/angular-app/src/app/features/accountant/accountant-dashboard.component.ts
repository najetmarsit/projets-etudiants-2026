import { Component, OnInit, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { TranslateModule, TranslateService } from '@ngx-translate/core';
import { ApiService, CashierDischargeRow, FinancialOverviewData } from '../../core/services/api.service';
import { BarChartComponent } from '../../shared/charts/bar-chart/bar-chart.component';
import { PieChartComponent } from '../../shared/charts/pie-chart/pie-chart.component';

@Component({
  selector: 'app-accountant-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, FormsModule, BarChartComponent, PieChartComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './accountant-dashboard.component.html',
  styleUrl: './accountant-dashboard.component.scss',
})
export class AccountantDashboardComponent implements OnInit {
  private api = inject(ApiService);
  private translate = inject(TranslateService);
  private cdr = inject(ChangeDetectorRef);

  admissionLabels: string[] = ['Entrées', 'Sorties'];
  admissionValues: number[] = [0, 0];
  cashLabels: string[] = [];
  cashValues: number[] = [];

  statsFrom = '';
  statsTo = '';
  stats: { entrants: number; sortants: number } | null = null;
  statsLoading = false;
  statsError: string | null = null;

  queueRows: CashierDischargeRow[] = [];
  queueLoading = false;
  queueError: string | null = null;

  financial: FinancialOverviewData | null = null;
  financialLoading = false;
  financialError: string | null = null;

  ngOnInit(): void {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    this.statsFrom = this.toInputDate(start);
    this.statsTo = this.toInputDate(now);
    this.loadStats();
    this.loadQueue();
  }

  private toInputDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  loadStats(): void {
    this.statsLoading = true;
    this.statsError = null;
    const from = this.statsFrom ? `${this.statsFrom}T00:00:00` : undefined;
    const to = this.statsTo ? `${this.statsTo}T23:59:59` : undefined;
    this.api.getAdmissionsStats(from, to).subscribe({
      next: (r) => {
        this.statsLoading = false;
        if (r.success && r.data) {
          this.stats = { entrants: r.data.entrants, sortants: r.data.sortants };
          this.admissionValues = [r.data.entrants, r.data.sortants];
        } else {
          this.statsError = this.translate.instant('Erreur de chargement');
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.statsLoading = false;
        this.statsError = this.translate.instant('Erreur réseau. Vérifiez la connexion et l’API.');
        this.cdr.markForCheck();
      },
    });
    this.loadFinancial();
  }

  loadFinancial(): void {
    this.financialLoading = true;
    this.financialError = null;
    const from = this.statsFrom || undefined;
    const to = this.statsTo || undefined;
    this.api.getFinancialOverview(from, to).subscribe({
      next: (r) => {
        this.financialLoading = false;
        if (r.success && r.data) {
          this.financial = r.data;
          const f = r.data;
          this.cashLabels = ['Encaissements', 'Achats stock', 'Consommation'];
          this.cashValues = [
            f.cash_in_from_patients ?? 0,
            f.cash_out_inventory_purchases ?? 0,
            f.inventory_consumption_value ?? 0,
          ];
        } else {
          this.financialError = this.translate.instant('Erreur de chargement');
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.financialLoading = false;
        this.financialError = this.translate.instant('Erreur réseau. Vérifiez la connexion et l’API.');
        this.cdr.markForCheck();
      },
    });
  }

  loadQueue(): void {
    this.queueLoading = true;
    this.queueError = null;
    this.api.getCashierDischargePending(40).subscribe({
      next: (r) => {
        this.queueLoading = false;
        if (r.success && r.data) {
          this.queueRows = r.data;
        } else {
          this.queueError = this.translate.instant('Erreur de chargement');
        }
      },
      error: () => {
        this.queueLoading = false;
        this.queueError = this.translate.instant('Erreur réseau. Vérifiez la connexion et l’API.');
      },
    });
  }

  formatMoney(n: number): string {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
  }
}
