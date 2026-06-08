# Application Flutter – Module Patient

Petite application Flutter connectée à l’API Medical (Laravel) pour le module Patient.

## Prérequis

- Flutter SDK (3.0+)
- API Medical démarrée (`php artisan serve` depuis la racine du projet)

## Configuration

Résumé des URL (voir `lib/config/api_config.dart`) :

- **Émulateur Android** : `http://10.0.2.2:8000/api` (par défaut)
- **iOS Simulator** : `http://localhost:8000/api` (par défaut)
- **Web** : `http://localhost:8000/api` (ou `--dart-define=API_WEB_HOST=127.0.0.1`)

### Téléphone ou tablette (réseau)

1. **Laravel doit accepter le réseau local** (sinon seul le PC peut joindre l’API) :
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. **Android (appareil physique)** : l’émulateur utilise `10.0.2.2`, ce qui **ne fonctionne pas** sur un vrai téléphone. Lancez avec l’IP locale du PC (ex. `192.168.1.20`) :
   ```bash
   flutter run -d android --dart-define=API_LAN_HOST=192.168.1.20
   ```
3. **iPhone / iPad** : le projet inclut une dérogation **ATS** (HTTP) pour le développement. En production, passez en **HTTPS** et restreignez `Info.plist`.

En mode **debug**, la console affiche une ligne du type : `[Clinova] API baseUrl = ...` au démarrage pour vérifier la bonne URL.

## Lancer l’app

```bash
cd flutter_patient_app
flutter pub get
flutter run
```

Choisir une cible (Chrome, Android, iOS) si demandé.

### Erreur sous Windows : `DartDevelopmentServiceException` / Chrome qui se ferme

Si `flutter run -d chrome` affiche **« Failed to start Dart Development Service »** ou des erreurs **WipError / AppInspector** dans la console, c’est un problème connu entre l’outil Flutter, le **DDS** (débogage) et Chrome — ce n’est en général **pas** une erreur dans le code de l’app.

**Pistes (dans l’ordre) :**

1. **Désactiver le DDS** (souvent suffisant) :
   ```bash
   flutter run -d chrome --no-dds
   ```
   Sous Windows, vous pouvez double-cliquer **`run_web_chrome.bat`** dans ce dossier (même commande).

2. **Autre navigateur** :
   ```bash
   flutter run -d edge
   ```

3. **Cible bureau Windows** (évite le navigateur) :
   ```bash
   flutter run -d windows
   ```
   *(Nécessite Visual Studio avec charge de travail « Développement Desktop en C++ » si ce n’est pas déjà installé.)*

4. **Android** : `flutter run -d <id_emulateur>` — souvent plus stable que Chrome en local.

5. Mettre à jour Flutter (`flutter upgrade`) si le problème persiste après une mise à jour majeure de Chrome.

### `flutter analyze` quitte avec le code 1

Ce n’est pas forcément une erreur bloquante : le projet peut n’avoir que des **infos** de lint. Pour n’échouer que sur erreurs / avertissements :

```bash
flutter analyze --no-fatal-infos
```

## Fonctionnalités

- **Connexion** : identifiant + mot de passe (comptes créés via l’API ou le seeder, ex. `doctor` / `password123`)
- **Liste des patients** : après connexion (rôle Doctor ou Admin pour voir tous les patients)
- **Détail patient** : fiche avec antécédents, opérations, indicateurs de santé, alertes

## Structure

- `lib/config/api_config.dart` – URL de l’API
- `lib/services/api_service.dart` – appels HTTP (login, patients, détail)
- `lib/models/` – modèles User et Patient
- `lib/screens/` – écrans Login, liste Patients, détail Patient
