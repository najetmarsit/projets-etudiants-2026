# Application Flutter – Module Patient

Petite application Flutter connectée à l’API Medical (Laravel) pour le module Patient.

## Prérequis

- Flutter SDK (3.0+)
- API Medical démarrée (`php artisan serve` depuis la racine du projet)

## Configuration

Modifier l’URL de l’API dans `lib/config/api_config.dart` selon l’environnement :

- **Émulateur Android** : `http://10.0.2.2:8000/api` (déjà configuré)
- **iOS Simulator / Web** : `http://localhost:8000/api`
- **Appareil physique** : `http://IP_DE_TA_MACHINE:8000/api`

## Lancer l’app

```bash
cd flutter_patient_app
flutter pub get
flutter run
```

Choisir une cible (Chrome, Android, iOS) si demandé.

## Fonctionnalités

- **Connexion** : identifiant + mot de passe (comptes créés via l’API ou le seeder, ex. `doctor` / `password123`)
- **Liste des patients** : après connexion (rôle Doctor ou Admin pour voir tous les patients)
- **Détail patient** : fiche avec antécédents, opérations, indicateurs de santé, alertes

## Structure

- `lib/config/api_config.dart` – URL de l’API
- `lib/services/api_service.dart` – appels HTTP (login, patients, détail)
- `lib/models/` – modèles User et Patient
- `lib/screens/` – écrans Login, liste Patients, détail Patient
