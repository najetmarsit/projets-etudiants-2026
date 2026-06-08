import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';

@Injectable({ providedIn: 'root' })
export class PortalService {
  private router = inject(Router);

  /** Segment d’URL du portail staff (`MainLayout`). Hors `/patient/*`. */
  seg(): 'admin' | 'doctor' | 'secretary' | 'lab' | 'accountant' | 'nurse' {
    const path = this.router.url.split('?')[0];
    const m = path.match(/^\/(admin|doctor|secretary|lab|accountant|nurse)(\/|$)/);
    return (m?.[1] as 'admin' | 'doctor' | 'secretary' | 'lab' | 'accountant' | 'nurse') ?? 'doctor';
  }

  prefix(): string {
    return `/${this.seg()}`;
  }
}
