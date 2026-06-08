import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { finalize } from 'rxjs';
import { GlobalLoaderService } from '../services/global-loader.service';

/** Affiche un indicateur global pour les requêtes API (hors SSE / assets). */
export const globalLoaderInterceptor: HttpInterceptorFn = (req, next) => {
  const loader = inject(GlobalLoaderService);
  const url = req.url;

  if (
    req.headers.get('X-Background-Refresh') === '1' ||
    (req.method === 'GET' &&
      (url.includes('/notifications/stream') || url.includes('/assets/') || url.includes('/i18n/')))
  ) {
    return next(req);
  }

  loader.show();
  return next(req).pipe(finalize(() => loader.hide()));
};
