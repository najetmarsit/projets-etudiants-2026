import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

export function roleGuard(roles: string[]): CanActivateFn {
  return () => {
    const auth = inject(AuthService);
    const router = inject(Router);
    const w = typeof window !== 'undefined' ? window : undefined;
    const isMobile = w ? w.matchMedia('(max-width: 767.98px)').matches : true;
    if (!auth.isAuthenticated()) {
      router.navigate(['/login']);
      return false;
    }
    const user = auth.user();
    const role = (user?.role ?? '').toString();
    const ok = roles.some((r) => r.toLowerCase() === role.toLowerCase());
    if (user && ok) return true;
    const r = user?.role;
    if (r === 'Admin') router.navigate(['/admin/dashboard']);
    else if (r === 'Doctor') router.navigate(['/doctor/dashboard']);
    else if (r === 'Secretary') router.navigate(['/secretary/dashboard']);
    else if (r === 'Laboratory') router.navigate(['/lab/dashboard']);
    else if (r === 'Accountant') router.navigate(['/accountant/dashboard']);
    else if (r === 'Nurse') router.navigate([isMobile ? '/nurse/dashboard' : '/nurse-use-mobile']);
    else if (r === 'Patient') router.navigate(['/patient/dashboard']);
    else router.navigate(['/login']);
    return false;
  };
}
