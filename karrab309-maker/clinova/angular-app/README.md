# Frontend Angular – Suivi Patient

Application web Angular 18 connectée à l’API Medical (Laravel).

## Prérequis

- Node.js 18+
- API backend démarrée : `php artisan serve` à la racine du projet medical-api

## Installation

```bash
cd angular-app
npm install
```

## Configuration

- URL de l’API : modifier `src/app/core/config/api.config.ts` si besoin (par défaut `http://localhost:8000/api`).
- CORS : le backend Laravel doit autoriser l’origine du frontend (voir `config/cors.php`).

## Lancement

```bash
npm start
```

Ouvre http://localhost:4200. Comptes de test (si seed effectué) : `doctor` / `password123`, `admin` / `password123`.

## Structure

- **core** : config API, modèles, services (auth, api), guards (auth, rôle), interceptors (JWT, 401).
- **features/auth** : login, register.
- **features/layout** : layout principal (sidebar + header).
- **features/dashboard** : tableau de bord (stats, alertes récentes).
- **features/patients** : liste, détail, formulaire (création / édition).
- **features/operations** : liste des opérations.
- **features/health-indicators** : liste des indicateurs de santé.
- **features/alerts** : liste des alertes, prise en compte.
- **features/messages** : liste des messages.
- **features/users** : page Utilisateurs (Admin), lien vers inscription.

## Build production

```bash
npm run build
```

Les fichiers sont générés dans `dist/angular-app/`. Pour déployer, pointer le serveur web vers ce dossier ou intégrer dans Laravel (copier dans `public/app` et une route Laravel qui sert `index.html` pour les routes Angular).
