import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService, PublicDossierApiResponse } from '../../../core/services/api.service';

/** Champs patient dans la réponse publique (évite les erreurs de typage template). */
export interface PublicDossierPatient {
  name?: string;
  age?: number;
  gender?: string;
  prescribed_treatment?: string | null;
  diagnosis?: string | null;
  admission_at?: string | null;
  discharge_at?: string | null;
}

@Component({
  selector: 'app-public-dossier',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './public-dossier.component.html',
  styleUrl: './public-dossier.component.scss',
})
export class PublicDossierComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private api = inject(ApiService);

  loading = true;
  error = '';
  dossier: PublicDossierApiResponse['data'] | null = null;

  get p(): PublicDossierPatient {
    return (this.dossier?.patient ?? {}) as PublicDossierPatient;
  }

  ngOnInit(): void {
    const token = this.route.snapshot.paramMap.get('token');
    if (!token) {
      this.error = 'Lien invalide.';
      this.loading = false;
      return;
    }
    this.api.getPublicPatientDossier(token).subscribe({
      next: (r) => {
        if (r.success && r.data) {
          this.dossier = r.data;
        } else {
          this.error = r.message ?? 'Dossier introuvable.';
        }
        this.loading = false;
      },
      error: (err) => {
        this.error = err.error?.message ?? 'Impossible de charger le dossier.';
        this.loading = false;
      },
    });
  }
}
