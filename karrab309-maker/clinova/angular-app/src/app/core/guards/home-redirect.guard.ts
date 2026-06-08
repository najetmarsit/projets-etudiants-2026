import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

/** Redirige vers le bon portail (ou login / mobile patient) selon le rôle. */
export const homeRedirectGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const w = typeof window !== 'undefined' ? window : undefined;
  const isMobile = w ? w.matchMedia('(max-width: 767.98px)').matches : true;
  if (!auth.isAuthenticated()) {
    return router.parseUrl('/login');
  }
  const role = auth.user()?.role;
  if (role === 'Admin') {
    return router.parseUrl('/admin/dashboard');
  }
  if (role === 'Doctor') {
    return router.parseUrl('/doctor/dashboard');
  }
  if (role === 'Secretary') {
    return router.parseUrl('/secretary/dashboard');
  }
  if (role === 'Laboratory') {
    return router.parseUrl('/lab/dashboard');
  }
  if (role === 'Accountant') {
    return router.parseUrl('/accountant/dashboard');
  }
  if (role === 'Nurse') {
    return router.parseUrl(isMobile ? '/nurse/dashboard' : '/nurse-use-mobile');
  }
  if (role === 'Patient') {
    return router.parseUrl('/patient/dashboard');
  }
  return router.parseUrl('/login');
};
