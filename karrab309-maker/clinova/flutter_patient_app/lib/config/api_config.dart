import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// API backend Clinova (partagée avec le tableau de bord web).
///
/// - **Web (Chrome)** : `http://localhost:8000/api` (lancer l’API avec `php artisan serve`)
///   Si échec : `--dart-define=API_WEB_HOST=127.0.0.1` ou `--dart-define=API_BASE_URL=http://127.0.0.1:8000/api`
/// - **Émulateur Android** : `http://10.0.2.2:8000/api`
/// - **Téléphone / tablette Android (Wi‑Fi)** : l’API doit écouter sur **toutes les interfaces** :
///   `php artisan serve --host=0.0.0.0 --port=8000`
///   puis lancer l’app avec l’IP de votre PC :
///   `flutter run --dart-define=API_LAN_HOST=192.168.x.x`
///   (sans `http://` ni `:8000`, seulement l’adresse IP ou le hostname.)
/// - **Émulateur Android** : `http://10.0.2.2:8000/api` (déjà utilisé par défaut)
/// - **Override total** : `--dart-define=API_BASE_URL=http://host:8000/api` (**obligatoire en build release** prod)
class ApiConfig {
  static String get baseUrl {
    const envUrl = String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: '',
    );
    if (envUrl.isNotEmpty) return envUrl;

    const lanHost = String.fromEnvironment('API_LAN_HOST', defaultValue: '');
    if (lanHost.isNotEmpty) {
      return 'http://$lanHost:8000/api';
    }

    if (kIsWeb) {
      const webHost = String.fromEnvironment('API_WEB_HOST', defaultValue: 'localhost');
      return 'http://$webHost:8000/api';
    }

    if (defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://localhost:8000/api';
  }

  /// Origine HTTP(S) de l’API sans `/api` (ex. `http://localhost:8000`), pour préfixer les chemins `/storage/...`.
  static String get apiOrigin {
    final u = Uri.parse(baseUrl);
    final port = u.hasPort ? u.port : null;
    if (port != null && port != 0) {
      return '${u.scheme}://${u.host}:$port';
    }
    return '${u.scheme}://${u.host}';
  }

  /// Origine de l'interface web publique (page `/public/dossier/:token`).
  ///
  /// Override : `--dart-define=PUBLIC_APP_ORIGIN=http://host:4200`
  static String get publicAppOrigin {
    const envOrigin = String.fromEnvironment('PUBLIC_APP_ORIGIN', defaultValue: '');
    if (envOrigin.isNotEmpty) return envOrigin;
    return 'http://localhost:4200';
  }
}
