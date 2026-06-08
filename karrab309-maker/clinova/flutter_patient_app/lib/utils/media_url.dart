import '../config/api_config.dart';

/// Rend une URL publique (fichiers `/storage/...`) utilisable par [NetworkImage] et le Web.
///
/// Laravel renvoie souvent un chemin relatif (`/storage/profile_photos/...`) : le navigateur
/// le résout alors contre l’origine Flutter (`localhost:xxxxx`), pas l’API — image cassée.
String? resolveApiPublicUrl(String? url) {
  if (url == null) return null;
  final t = url.trim();
  if (t.isEmpty) return null;
  final lower = t.toLowerCase();
  if (lower.startsWith('http://') || lower.startsWith('https://')) return t;
  if (t.startsWith('//')) {
    final scheme = Uri.parse(ApiConfig.baseUrl).scheme;
    return '$scheme:$t';
  }
  final origin = ApiConfig.apiOrigin;
  if (t.startsWith('/')) return '$origin$t';
  return '$origin/$t';
}
