# Clinova — Mohamed Karrab

## Projet

Plateforme hospitalière **Clinova** : gestion clinique multi-rôles (admin, médecin, infirmier, laboratoire, comptable, secrétaire, patient).

## Structure

| Dossier | Description |
|---------|-------------|
| `clinova/` | Code source complet (Laravel API + Angular + Flutter) |
| `clinova/angular-app/` | Application web staff |
| `clinova/flutter_patient_app/` | Application mobile patient |

## Technologies

- **API** : Laravel (PHP), JWT
- **Web staff** : Angular 19, i18n FR/EN/AR
- **Mobile patient** : Flutter

## Dépôt GitHub (miroir)

https://github.com/karrab309-maker/clinova

## Installation

```bash
cd clinova
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

```bash
cd clinova/angular-app
npm install
npm start
```

```bash
cd clinova/flutter_patient_app
flutter pub get
flutter run
```

Scripts Windows : `clinova/demarrer-api.bat`, `clinova/demarrer.bat`

## Comptes démo

Après `php artisan db:seed`, mot de passe : **password123**

Voir `clinova/docs/CLINOVA_SCENARIOS.md` pour les scénarios par rôle.

## Auteur

- **Étudiant** : Mohamed Karrab
- **Compte GitHub** : karrab309-maker
- **Session** : 2026
