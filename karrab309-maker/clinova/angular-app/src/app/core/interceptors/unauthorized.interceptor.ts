import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../services/auth.service';

export const unauthorizedInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const auth = inject(AuthService);
  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      if (err.status === 401) {
        // Évite les redirections "en double" en mobile :
        // - si l'utilisateur s'est déjà déconnecté (token déjà nettoyé)
        // - si la requête concernait déjà /auth/logout
        // Important : ne pas invalider le token côté serveur sur 401 (sinon déconnexion en cascade web/mobile).
        const alreadyLoggedOut = !auth.getToken();
        const isLogoutRequest = /\/auth\/logout(?:\?|$)/.test(req.url);
        const alreadyOnLogin = router.url?.startsWith('/login');

        if (!alreadyLoggedOut && !isLogoutRequest) {
          auth.clearLocalSession();
        }

        if (!alreadyOnLogin && !alreadyLoggedOut && !isLogoutRequest) {
          router.navigate(['/login']);
        }
      }
      return throwError(() => err);
    })
  );
};
