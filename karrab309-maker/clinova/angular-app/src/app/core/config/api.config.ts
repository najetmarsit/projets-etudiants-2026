export const apiConfig = {
  /**
   * Base URL API.
   * En dev: on passe par le proxy Angular (`proxy.conf.json`) pour éviter CORS.
   */
  baseUrl: '/api',
  /**
   * Origine Laravel (sans /api) pour les URLs de fichiers (storage, etc.).
   * Avec `npm start` (proxy XAMPP) : dossier `public` sous Apache.
   * Avec `npm run start:artisan` : remplacer par `http://127.0.0.1:8000`.
   */
  /** Avec `php artisan serve` (recommandé en dev). */
  backendOrigin: 'http://127.0.0.1:8000',
  /** Base URL du serveur Laravel (sans /api) pour les URLs des fichiers (ex. photos pansement). */
  get storageBaseUrl(): string {
    return this.backendOrigin;
  },
  /** Origine du front Angular (liens QR dossier public). */
  get publicAppOrigin(): string {
    if (typeof window !== 'undefined' && window.location?.origin) {
      return window.location.origin;
    }
    return 'http://localhost:4200';
  },
};
