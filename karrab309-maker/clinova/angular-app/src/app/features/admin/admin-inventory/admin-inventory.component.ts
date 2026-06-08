import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService, InventoryMovement, InventoryMovementBody } from '../../../core/services/api.service';

@Component({
  selector: 'app-admin-inventory',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule],
  templateUrl: './admin-inventory.component.html',
  styleUrl: './admin-inventory.component.scss',
})
export class AdminInventoryComponent implements OnInit {
  private api = inject(ApiService);

  rows: InventoryMovement[] = [];
  loading = false;
  error: string | null = null;
  saving = false;

  editingId: number | null = null;
  form: InventoryMovementBody = {
    movement_date: '',
    direction: 'in',
    category: 'material',
    label: '',
    quantity: null,
    unit: '',
    total_value: 0,
    currency: 'TND',
    notes: '',
  };

  ngOnInit(): void {
    const d = new Date();
    this.form.movement_date = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = null;
    this.api.getInventoryMovements().subscribe({
      next: (r) => {
        this.loading = false;
        if (r.success && r.data) {
          this.rows = r.data;
        } else {
          this.error = 'Erreur';
        }
      },
      error: () => {
        this.loading = false;
        this.error = 'Erreur réseau';
      },
    });
  }

  resetForm(): void {
    this.editingId = null;
    const d = new Date();
    this.form = {
      movement_date: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`,
      direction: 'in',
      category: 'material',
      label: '',
      quantity: null,
      unit: '',
      total_value: 0,
      currency: 'TND',
      notes: '',
    };
  }

  edit(m: InventoryMovement): void {
    this.editingId = m.id;
    this.form = {
      movement_date: (m.movement_date as string).slice(0, 10),
      direction: m.direction,
      category: m.category,
      label: m.label,
      quantity: m.quantity != null ? Number(m.quantity) : null,
      unit: m.unit ?? '',
      total_value: Number(m.total_value),
      currency: m.currency,
      notes: m.notes ?? '',
    };
  }

  submit(): void {
    if (!this.form.label?.trim()) return;
    this.saving = true;
    const body = { ...this.form };
    const req =
      this.editingId != null
        ? this.api.patchInventoryMovement(this.editingId, body)
        : this.api.postInventoryMovement(body as InventoryMovementBody);
    req.subscribe({
      next: () => {
        this.saving = false;
        this.resetForm();
        this.load();
      },
      error: () => {
        this.saving = false;
      },
    });
  }

  remove(m: InventoryMovement): void {
    if (!confirm('Supprimer ce mouvement ?')) return;
    this.api.deleteInventoryMovement(m.id).subscribe({
      next: () => this.load(),
    });
  }

  formatMoney(n: number | string): string {
    const v = typeof n === 'string' ? parseFloat(n) : n;
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
  }

  recorderName(m: InventoryMovement): string {
    return m.recorded_by_user?.name ?? '—';
  }
}
