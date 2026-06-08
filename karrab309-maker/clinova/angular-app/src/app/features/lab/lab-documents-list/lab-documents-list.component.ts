import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import type { LabDocument } from '../../../core/models/patient.model';

@Component({
  selector: 'app-lab-documents-list',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './lab-documents-list.component.html',
  styleUrl: './lab-documents-list.component.scss',
})
export class LabDocumentsListComponent implements OnInit {
  private api = inject(ApiService);
  portal = inject(PortalService);

  docs: LabDocument[] = [];
  loading = true;
  error = '';
  downloadingId: number | null = null;

  ngOnInit(): void {
    this.api.getLabDocuments().subscribe({
      next: (r) => {
        this.docs = r.data ?? [];
        this.loading = false;
      },
      error: (e) => {
        this.error = e.error?.message ?? 'Erreur';
        this.loading = false;
      },
    });
  }

  download(id: number, filename: string): void {
    this.downloadingId = id;
    this.api.downloadLabDocumentBlob(id).subscribe({
      next: (blob) => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || 'analyse.pdf';
        a.click();
        URL.revokeObjectURL(url);
        this.downloadingId = null;
      },
      error: () => (this.downloadingId = null),
    });
  }
}
