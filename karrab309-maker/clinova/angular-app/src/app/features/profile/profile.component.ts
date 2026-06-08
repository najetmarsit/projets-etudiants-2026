import { Component, OnInit, inject, ChangeDetectionStrategy, ChangeDetectorRef, ViewChild, ElementRef } from '@angular/core';
import { apiConfig } from '../../core/config/api.config';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { ToastService } from '../../shared/ui/toast/toast.service';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule],
  template: `
    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
          <div class="card card-modern">
            <div class="card-header-modern">
              <h2 class="card-title-modern">
                <i class="bi bi-person-circle me-2"></i>Mon Profil
              </h2>
            </div>
            <div class="card-body p-4">
              <!-- Photo de profil -->
              <div class="text-center mb-4">
                <div class="profile-avatar-wrapper" (click)="fileInput.click()">
                  @if (uploading) {
                    <div class="profile-avatar skeleton-pulse">
                      <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                  } @else {
                    <img
                      [src]="photoPreview || photoUrl || defaultAvatar"
                      (error)="photoPreview = ''; photoUrl = ''"
                      alt="Photo de profil"
                      class="profile-avatar"
                    />
                    <div class="profile-avatar-overlay">
                      <i class="bi bi-camera-fill"></i>
                      <span>Changer</span>
                    </div>
                  }
                </div>
                <h4 class="mt-3 mb-1">{{ user?.name }}</h4>
                <p class="text-muted mb-0">{{ user?.email }}</p>
                <span class="pill pill--new mt-2 d-inline-block">{{ user?.role }}</span>
              </div>

              <input
                #fileInput
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="d-none"
                (change)="onFileSelected($event)"
              />

              @if (photoPreview && photoPreview !== photoUrl) {
                <div class="crop-preview mb-3">
                  <p class="small text-muted mb-2">Aperçu de la nouvelle photo</p>
                  <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-primary btn-sm" (click)="uploadPhoto()" [disabled]="uploading">
                      <i class="bi bi-check-lg me-1"></i>Confirmer
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" (click)="cancelPreview()">
                      <i class="bi bi-x-lg me-1"></i>Annuler
                    </button>
                  </div>
                </div>
              }

              @if (photoUrl || user?.profile_photo_path) {
                <div class="text-center mb-3">
                  <button class="btn btn-outline-danger btn-sm" (click)="deletePhoto()" [disabled]="uploading">
                    <i class="bi bi-trash me-1"></i>Supprimer la photo
                  </button>
                </div>
              }

              <hr class="my-4" />

              <!-- Informations -->
              <div class="profile-info">
                <div class="info-row">
                  <span class="info-label">Identifiant</span>
                  <span class="info-value">{{ user?.username }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Email</span>
                  <span class="info-value">{{ user?.email }}</span>
                </div>
                <div class="info-row" *ngIf="user?.specialty">
                  <span class="info-label">Spécialité</span>
                  <span class="info-value">{{ user?.specialty }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Langue</span>
                  <span class="info-value">
                    <select class="form-select form-select-sm" [ngModel]="currentLocale" (ngModelChange)="changeLocale($event)">
                      <option value="fr">Français</option>
                      <option value="en">English</option>
                      <option value="ar">العربية</option>
                    </select>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .profile-avatar-wrapper {
      position: relative;
      width: 120px;
      height: 120px;
      margin: 0 auto;
      cursor: pointer;
      border-radius: 50%;
      overflow: hidden;
      transition: transform 0.3s;
    }
    .profile-avatar-wrapper:hover { transform: scale(1.05); }
    .profile-avatar-wrapper:hover .profile-avatar-overlay { opacity: 1; }
    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--primary-light);
      box-shadow: 0 4px 20px var(--primary-glow);
    }
    .profile-avatar.skeleton-pulse {
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--border);
      border-color: transparent;
      animation: pulse 1.5s ease-in-out infinite;
    }
    .profile-avatar-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.5);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 0.75rem;
      font-weight: 600;
      gap: 4px;
      opacity: 0;
      transition: opacity 0.3s;
      border-radius: 50%;
    }
    .profile-avatar-overlay i { font-size: 1.2rem; }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .crop-preview {
      background: var(--background);
      border-radius: var(--radius-sm);
      padding: 16px;
      text-align: center;
    }
    .profile-info { display: flex; flex-direction: column; gap: 12px; }
    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
    }
    .info-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
    .info-value { font-size: 0.9rem; font-weight: 500; color: var(--text); }
    .form-select-sm {
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      padding: 4px 28px 4px 12px;
      font-size: 0.85rem;
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProfileComponent implements OnInit {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private toast = inject(ToastService);
  private cdr = inject(ChangeDetectorRef);

  user = this.auth.user();
  photoUrl = '';
  photoPreview = '';
  selectedFile: File | null = null;
  uploading = false;
  currentLocale = 'fr';
  defaultAvatar = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120"><rect fill="#e2e8f0" width="120" height="120"/><text x="60" y="60" text-anchor="middle" dy=".35em" fill="#94a3b8" font-size="40" font-family="sans-serif">👤</text></svg>');

  ngOnInit(): void {
    this.currentLocale = this.user?.locale || 'fr';
    if (this.user?.profile_photo_path) {
      this.photoUrl = this.getPhotoUrl();
    }
  }

  getPhotoUrl(): string {
    const u = this.auth.user() ?? this.user;
    if (u?.profile_photo_url) {
      return u.profile_photo_url;
    }
    if (!u?.profile_photo_path) {
      return '';
    }
    return `${apiConfig.storageBaseUrl}/storage/${u.profile_photo_path}`;
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    const maxSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
      this.toast.error('Format non supporté. Utilisez JPG, PNG ou WEBP.');
      return;
    }

    if (file.size > maxSize) {
      this.toast.error('L\'image ne doit pas dépasser 5 Mo.');
      return;
    }

    this.selectedFile = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      this.photoPreview = e.target?.result as string;
      this.cdr.markForCheck();
    };
    reader.readAsDataURL(file);
  }

  uploadPhoto(): void {
    if (!this.selectedFile) return;
    this.uploading = true;
    this.api.uploadProfilePhoto(this.selectedFile).subscribe({
      next: (res) => {
        this.uploading = false;
        if (res.success) {
          this.photoUrl = res.profile_photo_url || this.getPhotoUrl();
          this.photoPreview = '';
          this.selectedFile = null;
          this.toast.success('Photo de profil mise à jour.');
          this.auth.refreshUser();
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.uploading = false;
        this.toast.error('Erreur lors de l\'upload.');
        this.cdr.markForCheck();
      },
    });
  }

  deletePhoto(): void {
    this.uploading = true;
    this.api.deleteProfilePhoto().subscribe({
      next: (res) => {
        this.uploading = false;
        if (res.success) {
          this.photoUrl = '';
          this.photoPreview = '';
          this.toast.success('Photo supprimée.');
          this.auth.refreshUser();
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.uploading = false;
        this.toast.error('Erreur lors de la suppression.');
        this.cdr.markForCheck();
      },
    });
  }

  cancelPreview(): void {
    this.photoPreview = '';
    this.selectedFile = null;
  }

  changeLocale(locale: string): void {
    this.api.updateLocale(locale as 'en' | 'fr' | 'ar').subscribe({
      next: () => {
        this.currentLocale = locale;
        this.toast.success('Langue mise à jour.');
      },
      error: () => this.toast.error('Erreur lors du changement de langue.'),
    });
  }
}
