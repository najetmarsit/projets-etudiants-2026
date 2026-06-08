import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { PortalService } from '../services/portal.service';

/**
 * Restreint une route aux écrans mobile (mobile-first).
 * Objectif : garder certains portails (ex: infirmier) uniquement sur mobile.
 */
export function mobileOnlyGuard(redirectTo?: string): CanActivateFn {
  return () => {
    const router = inject(Router);
    const portal = inject(PortalService);

    // SSR non utilisé ici, mais on sécurise l'accès à `window`.
    const w = typeof window !== 'undefined' ? window : undefined;
    const isMobile = w ? w.matchMedia('(max-width: 767.98px)').matches : true;

    if (isMobile) return true;

    // Redirection explicite si fournie, sinon vers une page dédiée au portail.
    const fallback = redirectTo ?? `/${portal.seg()}-use-mobile`;
    return router.parseUrl(fallback);
  };
}

