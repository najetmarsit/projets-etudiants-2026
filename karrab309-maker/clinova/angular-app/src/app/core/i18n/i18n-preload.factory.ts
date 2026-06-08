import { APP_INITIALIZER, Provider } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { firstValueFrom } from 'rxjs';
import { resolveSavedOrBrowserLocale } from '../services/translate.service';

/** Charge la langue active avant le premier rendu (évite les clés i18n brutes si le JSON arrive en retard). */
export function preloadTranslationsFactory(translate: TranslateService): () => Promise<unknown> {
  return () => {
    const lang = resolveSavedOrBrowserLocale(() => translate.getBrowserLang() ?? undefined);
    translate.setDefaultLang('en');
    return firstValueFrom(translate.use(lang));
  };
}

export function provideI18nPreload(): Provider {
  return {
    provide: APP_INITIALIZER,
    useFactory: preloadTranslationsFactory,
    deps: [TranslateService],
    multi: true,
  };
}
