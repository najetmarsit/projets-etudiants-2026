import 'package:http/http.dart' as http;

import '../services/api_service.dart';

/// Message lisible pour l’utilisateur (évite les préfixes « Exception : » inutiles).
String userFacingError(Object error) {
  if (error is ApiException) return error.message;
  if (error is http.ClientException) {
    return 'Erreur réseau (connexion interrompue). Vérifiez que l’API Laravel est '
        'démarrée (php artisan serve), l’URL dans lib/config/api_config.dart, et sur '
        'Chrome essayez --dart-define=API_WEB_HOST=127.0.0.1 si localhost échoue.';
  }
  final s = error.toString();
  if (s.contains('SocketException') ||
      s.contains('Failed host lookup') ||
      s.contains('Connection refused') ||
      s.contains('Network is unreachable')) {
    return 'Impossible de joindre le serveur. Vérifiez que l’API tourne (port 8000) '
        'et, sur téléphone physique, lancez avec --dart-define=API_LAN_HOST=IP_DE_VOTRE_PC.';
  }
  if (s.contains('HandshakeException') || s.contains('CERTIFICATE_VERIFY_FAILED')) {
    return 'Erreur de certificat SSL. En local, utilisez http:// pour l’API (pas https).';
  }
  if (s.startsWith('Exception: ')) return s.substring('Exception: '.length);
  return s;
}
