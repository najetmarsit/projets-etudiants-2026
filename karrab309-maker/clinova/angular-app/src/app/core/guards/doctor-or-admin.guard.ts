import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Garde : accès réservé aux rôles Médecin et Admin (Dashboard Web).
 * Les patients sont redirigés vers le portail /patient/dashboard.
 */
export const doctorOrAdminGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  if (!auth.isAuthenticated()) {
    router.navigate(['/login']);
    return false;
  }
  const user = auth.user();
  if (user && (user.role === 'Doctor' || user.role === 'Admin')) return true;
  router.navigate(['/patient/dashboard']);
  return false;
};
