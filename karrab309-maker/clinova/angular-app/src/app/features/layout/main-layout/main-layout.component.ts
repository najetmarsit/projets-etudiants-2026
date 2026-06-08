import { Component, inject, signal, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { TranslateModule, TranslatePipe } from '@ngx-translate/core';
import { AuthService } from '../../../core/services/auth.service';
import { ThemeService } from '../../../core/services/theme.service';
import { ApiService } from '../../../core/services/api.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { PortalService } from '../../../core/services/portal.service';
import { NotificationRealtimeService } from '../../../core/services/notification-realtime.service';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive, FormsModule, TranslateModule],
  templateUrl: './main-layout.component.html',
  styleUrl: './main-layout.component.scss',
})
export class MainLayoutComponent implements OnInit {
  private auth = inject(AuthService);
  private router = inject(Router);
  theme = inject(ThemeService);
  translate = inject(AppTranslateService);
  private api = inject(ApiService);
  portal = inject(PortalService);
  private realtime = inject(NotificationRealtimeService);

  user = this.auth.user;
  sidebarOpen = signal(false);
  profileMenuOpen = signal(false);
  alertsCount = signal(0);
  searchQuery = '';

  portalLabel(): string {
    switch (this.portal.seg()) {
      case 'admin':
        return 'Administration';
      case 'doctor':
        return 'Médecin';
      case 'secretary':
        return 'Réception';
      case 'lab':
        return 'Laboratoire';
      case 'accountant':
        return 'Comptabilité';
      case 'nurse':
        return 'Infirmier';
      default:
        return '';
    }
  }

  staffMobilePad(): boolean {
    const s = this.portal.seg();
    return s === 'doctor' || s === 'nurse';
  }

  ngOnInit(): void {
    this.auth.refreshUser().subscribe({ error: () => {} });
    const seg = this.portal.seg();
    if (seg === 'admin' || seg === 'doctor' || seg === 'nurse') {
      this.api.getDashboardStats().subscribe({
        next: (r) => r.success && r.data && this.alertsCount.set(r.data.alerts),
        error: () => {},
      });
    }

    // Réception temps réel des notifications (inclut Réception pour disponibilité médecins).
    if (seg === 'secretary') {
      this.api.getSecretaryAnalytics().subscribe({
        next: (r) => {
          if (r.success && r.data) {
            this.alertsCount.set(r.data.pending_specialist_appointments);
          }
        },
        error: () => {},
      });
    }

    if (seg === 'admin' || seg === 'doctor' || seg === 'secretary' || seg === 'nurse') {
      this.realtime.start();
      this.realtime.notifications$.subscribe((n) => {
        // Badge simple: incrémenter sur nouvelles notifications non lues.
        if (!n.read_at) {
          this.alertsCount.update((v) => Math.min(99, (v ?? 0) + 1));
        }
      });
    }
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    const t = event.target as HTMLElement;
    if (!t.closest('.dropdown-profile')) this.profileMenuOpen.set(false);
  }

  toggleSidebar(): void {
    this.sidebarOpen.update((v) => !v);
  }

  toggleProfileMenu(): void {
    this.profileMenuOpen.update((v) => !v);
  }

  closeProfileMenu(): void {
    this.profileMenuOpen.set(false);
  }

  onSearch(): void {
    const q = this.searchQuery?.trim();
    if (q) this.router.navigate(['/', this.portal.seg(), 'patients'], { queryParams: { search: q } });
  }

  logout(): void {
    this.auth.logout().subscribe(() => this.router.navigate(['/login']));
  }
}
