import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideAnimations } from '@angular/platform-browser/animations';
import { provideTranslateService } from '@ngx-translate/core';
import { provideTranslateHttpLoader } from '@ngx-translate/http-loader';

import { routes } from './app.routes';
import { jwtInterceptor } from './core/interceptors/jwt.interceptor';
import { unauthorizedInterceptor } from './core/interceptors/unauthorized.interceptor';
import { globalLoaderInterceptor } from './core/interceptors/global-loader.interceptor';
import { provideI18nPreload } from './core/i18n/i18n-preload.factory';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),
    provideHttpClient(withInterceptors([jwtInterceptor, globalLoaderInterceptor, unauthorizedInterceptor])),
    provideAnimations(),
    ...provideTranslateHttpLoader({
      prefix: '/assets/i18n/',
      suffix: '.json',
      useHttpBackend: true,
    }),
    provideTranslateService({ fallbackLang: 'en' }),
    provideI18nPreload(),
  ],
};
