import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../core/services/api.service';
import { ClinPageHeadComponent } from '../../shared/ui/clin-page-head/clin-page-head.component';
import { apiConfig } from '../../core/config/api.config';

@Component({
  selector: 'app-patient-dossier-page',
  standalone: true,
  imports: [CommonModule, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-dossier-page.component.html',
  styleUrl: './patient-dossier-page.component.scss',
})
export class PatientDossierPageComponent implements OnInit {
  private api = inject(ApiService);

  qrDataUrl: string | null = null;
  publicUrl: string | null = null;
  loading = true;
  loadError = false;

  ngOnInit(): void {
    void this.loadQr();
  }

  private async loadQr(): Promise<void> {
    this.loading = true;
    this.loadError = false;
    this.qrDataUrl = null;
    this.publicUrl = null;
    this.api.getMyPatient().subscribe({
      next: async (r) => {
        this.loading = false;
        const token = r.data?.qr_public_token ?? null;
        if (!token) return;
        this.publicUrl = `${apiConfig.publicAppOrigin}/public/dossier/${encodeURIComponent(token)}`;
        try {
          const QRCode = (await import('qrcode')).default;
          this.qrDataUrl = await QRCode.toDataURL(this.publicUrl, { width: 220, margin: 1 });
        } catch {
          this.qrDataUrl = null;
        }
      },
      error: () => {
        this.loading = false;
        this.loadError = true;
      },
    });
  }
}
