# API de l'application Medical API

Base URL : **`/api`** (ex. `http://localhost:8000/api`)

### Dépannage : « The route api/dashboard/… could not be found »

Si le front (ex. **Espace laboratoire**) affiche une erreur indiquant qu’une route `api/...` est introuvable alors qu’elle existe dans `routes/api.php`, le serveur utilise souvent une **ancienne cache de routes** (`php artisan route:cache`).

À exécuter à la racine du projet Laravel :

```bash
php artisan route:clear
```

Ou, pour tout vider (routes, config, vues) :

```bash
php artisan optimize:clear
```

Puis redémarrer l’API. Les scripts `demarrer-api.bat` et `demarrer.bat` lancent désormais `route:clear` avant `php artisan serve`.

---

## État des tests et frontend

- **Tests API** : Des tests automatisés ont été ajoutés pour l’authentification et les patients :
  - `tests/Feature/AuthApiTest.php` (register, login, me)
  - `tests/Feature/PatientApiTest.php` (index, show, 401/404)
  - Commande : `php artisan test tests/Feature/AuthApiTest.php tests/Feature/PatientApiTest.php`
  - Les tests utilisent `RefreshDatabase` ; une base de test (MySQL ou SQLite) doit être configurée en environnement `testing`.
- **Frontend actuel** : L’application n’est **pas** en Angular. Le frontend fourni avec le projet est **Laravel + Vite** (vues Blade + JS dans `resources/views` et `resources/js`). Les maquettes dont vous avez parlé peuvent être en Flutter ou Angular par ailleurs ; le dépôt actuel est Laravel + Vite.
- **Frontend Angular (web)** : L’application web est dans **`angular-app/`** (Angular 18) : authentification, tableau de bord, patients, opérations, indicateurs de santé, alertes, messages, utilisateurs (Admin). Voir `angular-app/README.md` pour l’installation et le lancement.
- **Application Flutter (module Patient)** : Une petite app Flutter connectée aux API existantes se trouve dans le dossier **`flutter_patient_app/`** (connexion, liste des patients, détail patient). À développer pleinement après stabilisation du web. Voir `flutter_patient_app/README.md` pour l’exécution.

---

Authentification : **JWT** (Bearer token). Toutes les routes protégées nécessitent l’en-tête :
`Authorization: Bearer <token>`.

---

## 1. Authentification (`/api/auth`)

| Méthode | Endpoint | Accès | Description |
|--------|----------|--------|-------------|
| **POST** | `/api/auth/register` | Public | Inscription (name, username, email, password, password_confirmation, role) |
| **POST** | `/api/auth/login` | Public | Connexion (username, password) → retourne `token` + `user` |
| **POST** | `/api/auth/logout` | Protégé | Déconnexion (invalide le token) |
| **POST** | `/api/auth/refresh` | Protégé | Rafraîchir le token JWT |
| **GET**  | `/api/auth/me` | Protégé | Profil de l’utilisateur connecté |

**Rôles utilisés** : `Admin`, `Doctor`, `Patient`.

---

## 2. Patients (`/api/patients`)

| Méthode | Endpoint | Accès | Description |
|--------|----------|--------|-------------|
| **GET**    | `/api/patients` | Protégé | Liste des patients (Admin/Doctor : tous ; Patient : soi-même) |
| **POST**   | `/api/patients` | Protégé (Admin, Doctor) | Créer un patient (user_id, age, gender, medical_history) |
| **GET**    | `/api/patients/{id}` | Protégé | Détail patient + operations, healthIndicators, alerts, reports |
| **PUT/PATCH** | `/api/patients/{id}` | Protégé | Modifier (age, gender, medical_history) |
| **DELETE** | `/api/patients/{id}` | Protégé (Admin) | Supprimer un patient |

---

## 3. Opérations (`/api/operations`)

| Méthode | Endpoint | Accès | Description |
|--------|----------|--------|-------------|
| **GET**    | `/api/operations` | Protégé | Liste des opérations (Admin : tout ; Doctor : les siennes ; Patient : les siennes) |
| **POST**   | `/api/operations` | Protégé (Doctor) | Créer une opération (patient_id, type, date, etc.) |
| **GET**    | `/api/operations/{id}` | Protégé | Détail d’une opération |
| **PUT/PATCH** | `/api/operations/{id}` | Protégé | Modifier une opération |
| **DELETE** | `/api/operations/{id}` | Protégé | Supprimer une opération |

---

## 4. Indicateurs de santé (`/api/health-indicators`)

| Méthode | Endpoint | Accès | Description |
|--------|----------|--------|-------------|
| **GET**    | `/api/health-indicators` | Protégé | Liste (option : `?patient_id={id}`). Admin/Doctor : tous ; Patient : les siens |
| **POST**   | `/api/health-indicators` | Protégé | Créer un indicateur (patient_id, type, value, recorded_at, etc.) |
| **GET**    | `/api/health-indicators/{id}` | Protégé | Détail d’un indicateur |
| **PUT/PATCH** | `/api/health-indicators/{id}` | Protégé | Modifier un indicateur |
| **DELETE** | `/api/health-indicators/{id}` | Protégé | Supprimer un indicateur |

Les indicateurs peuvent déclencher des **alertes** (ex. température élevée) via `AlertService`.

---

## 5. Messages (`/api/messages`)

| Méthode | Endpoint | Accès | Description |
|--------|----------|--------|-------------|
| **GET**    | `/api/messages` | Protégé | Liste des messages envoyés/reçus par l’utilisateur |
| **POST**   | `/api/messages` | Protégé | Envoyer un message (receiver_id, content) |
| **GET**    | `/api/messages/{id}` | Protégé | Détail d’un message |
| **PUT/PATCH** | `/api/messages/{id}` | Protégé | Modifier un message |
| **DELETE** | `/api/messages/{id}` | Protégé | Supprimer un message |
| **PATCH**  | `/api/messages/{id}/read` | Protégé | Marquer un message comme lu |

---

## 6. Alertes (`/api/alerts`)

| Méthode | Endpoint | Accès | Description |
|--------|----------|--------|-------------|
| **GET**    | `/api/alerts` | Protégé | Liste des alertes (option : `?patient_id={id}`, `?status=...`) |
| **POST**   | `/api/alerts` | Protégé | Créer une alerte |
| **GET**    | `/api/alerts/{id}` | Protégé | Détail d’une alerte |
| **PUT/PATCH** | `/api/alerts/{id}` | Protégé | Modifier une alerte |
| **DELETE** | `/api/alerts/{id}` | Protégé | Supprimer une alerte |
| **PATCH**  | `/api/alerts/{id}/acknowledge` | Protégé | Marquer une alerte comme prise en compte |

---

## 7. Rapports

Il n’existe **pas d’API dédiée** aux rapports. Les rapports sont liés aux patients et sont retournés dans **`GET /api/patients/{id}`** (relation `reports`).  
Le `ReportController` existe mais n’est pas enregistré dans `routes/api.php` ; une ressource `/api/reports` pourrait être ajoutée plus tard si besoin.

---

## Récapitulatif des endpoints

```
Auth
  POST   /api/auth/register
  POST   /api/auth/login
  POST   /api/auth/logout      (protégé)
  POST   /api/auth/refresh     (protégé)
  GET    /api/auth/me         (protégé)

Patients
  GET    /api/patients
  POST   /api/patients
  GET    /api/patients/{id}
  PUT    /api/patients/{id}
  PATCH  /api/patients/{id}
  DELETE /api/patients/{id}

Operations
  GET    /api/operations
  POST   /api/operations
  GET    /api/operations/{id}
  PUT    /api/operations/{id}
  PATCH  /api/operations/{id}
  DELETE /api/operations/{id}

Health indicators
  GET    /api/health-indicators
  POST   /api/health-indicators
  GET    /api/health-indicators/{id}
  PUT    /api/health-indicators/{id}
  PATCH  /api/health-indicators/{id}
  DELETE /api/health-indicators/{id}

Messages
  GET    /api/messages
  POST   /api/messages
  GET    /api/messages/{id}
  PUT    /api/messages/{id}
  PATCH  /api/messages/{id}
  DELETE /api/messages/{id}
  PATCH  /api/messages/{id}/read

Alerts
  GET    /api/alerts
  POST   /api/alerts
  GET    /api/alerts/{id}
  PUT    /api/alerts/{id}
  PATCH  /api/alerts/{id}
  DELETE /api/alerts/{id}
  PATCH  /api/alerts/{id}/acknowledge
```

---

## Correspondance avec l’interface Flutter (Suivi Patient)

| Fonctionnalité UI | API à utiliser |
|-------------------|----------------|
| Connexion (Dr. Dupont) | `POST /api/auth/login` |
| Liste / fiche patient (Jean Martin) | `GET /api/patients`, `GET /api/patients/{id}` |
| Indicateurs (Douleur, Température, Pansement) | `GET /api/health-indicators?patient_id={id}` |
| Graphique évolution postopératoire | `GET /api/health-indicators?patient_id={id}` (données + tri par date) |
| Envoyer message | `POST /api/messages` |
| Générer rapport | Données patient + indicateurs ; pas d’endpoint rapport dédié (ou à ajouter) |
| Alerte « Température élevée » | `GET /api/alerts?patient_id={id}` ou créée automatiquement par `AlertService` |
| Données préopératoires / opération | `GET /api/operations?patient_id={id}` ou via `GET /api/patients/{id}` |

Si tu veux, on peut détailler les corps de requête (JSON) pour chaque endpoint ou ajouter une ressource `/api/reports` pour la génération de rapports.
