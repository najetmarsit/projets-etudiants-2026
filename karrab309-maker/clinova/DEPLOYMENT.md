# Guide de déploiement – Plateforme Medical Hub

## Prérequis

- **Backend** : PHP 8.1+, MySQL, Composer
- **Frontend Angular** : Node.js 18+, npm
- **Mobile Flutter** : Flutter SDK 3.0+
- **MySQL** : Démarré (XAMPP ou autre)

---

## 1. Backend Laravel

```bash
cd c:\xampp\htdocs\medical-api-main

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Copier .env et configurer
copy .env.example .env
# Éditer .env : DB_*, APP_URL, CORS_ORIGINS

# Générer la clé
php artisan key:generate

# Migrations
php artisan migrate --force

# Démarrer le serveur (dev)
php artisan serve
# Production : configurer Apache/Nginx pour pointer vers public/
```

**Variables .env importantes :**
- `APP_URL` : URL publique (ex: https://api.votredomaine.com)
- `CORS_ORIGINS` : Origines autorisées (ex: https://app.votredomaine.com,https://votredomaine.com)
- `DB_*` : Connexion MySQL

---

## 2. Frontend Angular

```bash
cd angular-app

# Installer
npm ci

# Build production
npm run build

# Les fichiers sont dans dist/angular-app/browser/
# Déployer sur un serveur web (Nginx, Apache, Netlify, Vercel...)
```

**Configuration API** : Modifier `src/app/core/config/api.config.ts` avant le build :
```typescript
baseUrl: 'https://api.votredomaine.com/api'
```

---

## 3. Application Mobile Flutter

### Build Android (APK)

```bash
cd flutter_patient_app

# Récupérer les dépendances
flutter pub get

# Générer les localisations
flutter gen-l10n

# Build APK release (pour test / distribution directe)
flutter build apk --release

# APK généré : build/app/outputs/flutter-apk/app-release.apk
```

### Build Android (AAB – Google Play)

```bash
flutter build appbundle --release

# Fichier : build/app/outputs/bundle/release/app-release.aab
# À uploader sur Google Play Console
```

### Build avec URL API personnalisée

```bash
# Pour production avec votre API
flutter build apk --release --dart-define=API_BASE_URL=https://api.votredomaine.com/api

# Ou pour un appareil physique en local
flutter run --dart-define=API_BASE_URL=http://192.168.1.100:8000/api
```

### Build iOS (sur Mac uniquement)

```bash
flutter build ios --release
# Puis ouvrir ios/Runner.xcworkspace dans Xcode pour signer et uploader
```

---

## 4. Vérifications avant déploiement

| Composant | Commande | Résultat attendu |
|-----------|----------|------------------|
| Laravel | `php artisan route:list` | Liste des routes |
| Angular | `npm run build` | Build réussi |
| Flutter | `flutter analyze` | No issues found |
| Flutter Android | `flutter build apk` | APK généré |

---

## 5. CORS

Pour que l’app Angular et l’app Flutter web puissent appeler l’API :

- **Angular** : L’origine doit être dans `CORS_ORIGINS` (ex: `https://app.votredomaine.com`)
- **Flutter Web** : Idem si déployé sur un domaine
- **Flutter Mobile** : Pas de CORS (requêtes natives)

---

## 6. Résumé des commandes

```bash
# Démarrer en local (dev)
# Terminal 1 - API
cd c:\xampp\htdocs\medical-api-main && php artisan serve

# Terminal 2 - Angular
cd angular-app && npm start

# Terminal 3 - Flutter (optionnel)
cd flutter_patient_app && flutter run -d chrome
# ou
flutter run -d android
```
