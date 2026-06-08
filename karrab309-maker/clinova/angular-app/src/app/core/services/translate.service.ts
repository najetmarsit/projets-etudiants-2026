import { Injectable, inject } from '@angular/core';
import { TranslateService as NgxTranslateService } from '@ngx-translate/core';
import { ApiService } from './api.service';

export type AppLocale = 'en' | 'fr' | 'ar';

const SUPPORTED_LOCALES: readonly AppLocale[] = ['en', 'fr', 'ar'];

/** Règle identique au préchargement i18n (localStorage puis navigateur, sinon en). */
export function resolveSavedOrBrowserLocale(getBrowserLang: () => string | undefined): AppLocale {
  const saved = localStorage.getItem('medical_locale') as AppLocale | null;
  const browserLang = getBrowserLang()?.slice(0, 2) ?? '';
  if (saved && SUPPORTED_LOCALES.includes(saved)) {
    return saved;
  }
  if (browserLang && SUPPORTED_LOCALES.includes(browserLang as AppLocale)) {
    return browserLang as AppLocale;
  }
  return 'en';
}

@Injectable({ providedIn: 'root' })
export class AppTranslateService {
  private translate = inject(NgxTranslateService);
  private api = inject(ApiService);

  readonly supportedLocales: { code: AppLocale; label: string; dir: 'ltr' | 'rtl' }[] = [
    { code: 'en', label: 'English', dir: 'ltr' },
    { code: 'fr', label: 'Français', dir: 'ltr' },
    { code: 'ar', label: 'العربية', dir: 'rtl' },
  ];

  constructor() {
    const defaultLang = resolveSavedOrBrowserLocale(() => this.translate.getBrowserLang() ?? undefined);
    this.translate.setDefaultLang('en');
    this.translate.use(defaultLang);
    this.applyDir(defaultLang);
  }

  use(locale: AppLocale): void {
    this.translate.use(locale);
    localStorage.setItem('medical_locale', locale);
    this.applyDir(locale);
    if (localStorage.getItem('medical_token')) {
      this.api.updateLocale(locale).subscribe();
    }
  }

  current(): AppLocale {
    return (this.translate.currentLang || this.translate.defaultLang) as AppLocale;
  }

  isRtl(): boolean {
    return this.current() === 'ar';
  }

  private applyDir(locale: AppLocale): void {
    const config = this.supportedLocales.find((l) => l.code === locale);
    document.documentElement.lang = locale;
    document.documentElement.dir = config?.dir ?? 'ltr';
    document.body.classList.toggle('rtl', config?.dir === 'rtl');
  }

  instant(key: string, params?: Record<string, string | number>): string {
    return this.translate.instant(key, params);
  }

  /**
   * ngx-translate charge les JSON en asynchrone : avant le chargement, `instant` renvoie la clé brute
   * (ex. FORM.ERROR_INVALID_CREDENTIALS affichée à l’écran). On retombe sur un libellé lisible.
   */
  private instantOr(key: string, fallback: string): string {
    const t = this.translate.instant(key);
    return t && t !== key ? t : fallback;
  }

  /** Comme `instant`, mais évite d’afficher la clé avant chargement des i18n. */
  display(key: string, fallback: string): string {
    return this.instantOr(key, fallback);
  }

  /** Message d'erreur API convivial (ex: "Unauthenticated" → traduction) */
  apiErrorMessage(raw?: string | null): string {
    const s = (raw ?? '').trim().toLowerCase();
    if (s === 'unauthenticated' || s === 'unauthenticated.' || s === 'unauthorized') {
      return this.instantOr('FORM.ERROR_UNAUTHENTICATED', 'Session expirée. Veuillez vous reconnecter.');
    }
    if (s === 'invalid credentials' || s === 'identifiant ou mot de passe incorrect') {
      return this.instantOr('FORM.ERROR_INVALID_CREDENTIALS', 'Identifiant ou mot de passe incorrect');
    }
    const trimmed = raw?.trim();
    if (trimmed) {
      return trimmed;
    }
    return this.instantOr('FORM.ERROR_GENERIC', 'Erreur');
  }
}
