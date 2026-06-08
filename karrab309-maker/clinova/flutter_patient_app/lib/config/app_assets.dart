/// Illustrations locales (`assets/images/`) pour bannières et états vides.
abstract final class AppAssets {
  static const String _dir = 'assets/images';

  /// Logo Clinova (cœur + ECG + feuille, fond transparent) — connexion, splash, etc.
  static const String appLogo = '$_dir/clinova-logo-transparent.png';

  /// Variante avec fond noir (fichier historique dans le dépôt).
  static const String appLogoOnBlack = '$_dir/logo_clinova.png';

  /// Bannière héro sur l’écran de connexion (identité visuelle).
  static const String loginHero = appLogo;
  static const String bannerSecondary = '$_dir/image2.png';
  static const String bannerPatient = '$_dir/image3.png';
  static const String illustrationSmall = '$_dir/image4.png';
  static const String bannerDoctor = '$_dir/image5.png';
  static const String bannerNurse = '$_dir/image6.png';
  static const String emptySchedule = '$_dir/image7.png';

  /// Fichier source conservé tel quel dans le projet.
  static const String illustrationCareTeam = '$_dir/imahe8.png';
  static const String bannerWellness = '$_dir/image9.png';
  static const String illustrationDocuments = '$_dir/image10.png';
}
