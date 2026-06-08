# État du projet – Application médicale Suivi Patient

Ce document répond à : **quels langages**, **quelles fonctionnalités**, et **tout est-il connecté** (backend / frontend / API).

---

## 1. Langages et technologies

| Partie | Langage / stack | Dossier |
|--------|------------------|--------|
| **Backend (API)** | **PHP** – Laravel 10, JWT (tymon/jwt-auth), MySQL/PostgreSQL | Racine du projet (`app/`, `routes/`, `config/`) |
| **Frontend Web (Dashboard médecin)** | **TypeScript** – Angular 18, HTTP client, routing, guards | `angular-app/` |
| **Frontend Mobile (Patient)** | **Dart** – Flutter, package `http` | `flutter_patient_app/` |

- **Base de données** : centralisée (MySQL ou PostgreSQL configuré dans `.env`).
- **Communication** : JSON entre tous les clients et l’API REST Laravel.

---

## 2. Connexion entre les parties (tout est branché sur la même API)

Oui, **tout est connecté** : le backend sert d’API unique pour le Web et le Mobile.

| Élément | Détail |
|--------|--------|
| **URL de l’API** | `http://localhost:8000/api` (Laravel `php artisan serve`). |
| **Angular** | `angular-app/src/app/core/config/api.config.ts` → `baseUrl: 'http://localhost:8000/api'`. Toutes les requêtes passent par `ApiService` + interceptor JWT (`Authorization: Bearer <token>`). |
| **Flutter** | `flutter_patient_app/lib/config/api_config.dart` → `baseUrl: 'http://10.0.2.2:8000/api'` (émulateur Android). Même API, même format JSON, même JWT. |
| **Authentification** | JWT : login/register côté API ; Angular et Flutter envoient le token sur chaque requête protégée. |
| **Base de données** | Une seule BDD Laravel ; Web et Mobile lisent/écrivent les mêmes données (patients, indicateurs, alertes, messages, etc.). |

En résumé : **Backend (Laravel) = source de vérité ; Angular et Flutter sont deux clients déjà connectés à cette API.**

---

## 3. Fonctionnalités actuellement disponibles

### 3.1 Backend (API Laravel)

| Domaine | Fonctionnalité | Endpoints principaux |
|---------|----------------|----------------------|
| **Auth** | Inscription, connexion, déconnexion, refresh token, profil (me) | `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `POST /auth/refresh`, `GET /auth/me` |
| **Patients** | CRUD, détail avec opérations/indicateurs/alertes/rapports | `GET/POST /patients`, `GET/PUT/DELETE /patients/{id}` |
| **Opérations** | CRUD (lié médecin/patient) | `GET/POST /operations`, `GET/PUT/DELETE /operations/{id}` |
| **Indicateurs de santé** | CRUD (douleur, température, pansement, date) | `GET/POST /health-indicators`, `GET/PUT/DELETE /health-indicators/{id}` |
| **Messages** | CRUD, marquer comme lu | `GET/POST /messages`, `PATCH /messages/{id}/read` |
| **Alertes** | CRUD, marquer comme prise en compte | `GET/POST /alerts`, `PATCH /alerts/{id}/acknowledge` |
| **Alertes automatiques** | Création d’alerte lors de la saisie d’un indicateur (ex. temp > 38.5, douleur > 7) | Via `AlertService` dans `HealthIndicatorController` |
| **Rapports** | Modèle et relation au patient ; **pas d’API exposée** (pas de route dédiée) | — |
| **Upload images** | **Non implémenté** | — |

Rôles utilisés : **Admin**, **Doctor**, **Patient** (permissions gérées dans les contrôleurs).

---

### 3.2 Frontend Web (Angular – Dashboard médecin/admin)

| Écran / fonctionnalité | Disponible | Connecté à l’API |
|------------------------|------------|-------------------|
| **Login / Inscription** | Oui | Oui (auth) |
| **Navbar** | Tableau de bord, Patients, Alertes, Rapports, Profil, (Admin : Utilisateurs) | — |
| **Tableau de bord** | Stats (patients, opérations, alertes, messages) + alertes récentes | Oui |
| **Liste patients** | Liste, lien Suivi, Ajouter patient, Supprimer (Admin) | Oui |
| **Détail patient** | Fiche + opérations, indicateurs, alertes ; bouton Suivi patient | Oui |
| **Page Suivi Patient** | Card patient (nom, âge, type opération), indicateurs (douleur/temp/pansement), alerte rouge si temp > 38°C, onglets (Préop, Postop, Messages, Analyses), graphique évolution, boutons Envoyer message / Générer rapport | Oui (données) ; Générer rapport = alerte (API rapports à ajouter) |
| **Opérations** | Liste | Oui |
| **Indicateurs santé** | Liste | Oui |
| **Alertes** | Liste + marquer comme lu | Oui |
| **Messages** | Liste des messages | Oui |
| **Rapports** | Page placeholder (lien vers patients) | Non (pas d’API rapports) |
| **Profil** | Affichage utilisateur connecté | Oui (auth) |
| **Utilisateurs (Admin)** | Page + lien Inscription | Oui (auth) |

Toutes les données affichées (patients, opérations, indicateurs, alertes, messages) viennent bien de l’API Laravel.

---

### 3.3 Frontend Mobile (Flutter – Patient)

| Écran / fonctionnalité | Disponible | Connecté à l’API |
|------------------------|------------|-------------------|
| **Login** | Oui | Oui |
| **Liste patients** | Oui (selon rôle : patient voit son propre enregistrement) | Oui |
| **Détail patient** | Fiche avec indicateurs, opérations, alertes | Oui |
| **Suivi post-op temps réel** | Non (données chargées une fois) | — |
| **Upload photo pansement** | Non | — |
| **Analyses / envoi résultats** | Non | — |
| **Messagerie** | Non (écran dédié) | — |
| **Notifications push** | Non | — |

Le mobile est donc **partiellement** développé et **déjà connecté** à la même API (auth + patients + détail).

---

## 4. Récapitulatif : à quelle étape est le projet ?

- **Backend** : API REST Laravel opérationnelle (auth JWT, CRUD patients, opérations, indicateurs, messages, alertes, alertes auto). Il manque : **endpoint(s) rapports**, **upload images pansement**, et éventuellement **analyses** selon le modèle choisi.
- **Frontend Web** : Dashboard Angular **complet et connecté** (navbar, tableau de bord, patients, suivi patient avec indicateurs/graphique/onglets/alertes, opérations, indicateurs, alertes, messages, profil, rapports en placeholder). À prévoir : brancher une vraie **génération de rapports** quand l’API existera.
- **Frontend Mobile** : App Flutter **connectée** (login, liste/détail patients). À faire : **suivi post-op temps réel**, **upload photo pansement**, **analyses**, **messagerie**, **notifications push**.

**Connexion** : Backend ↔ Angular et Backend ↔ Flutter sont en place (même API, JWT, même BDD). Rien n’est « encore séparé » au sens où les deux frontends parlent déjà à Laravel.

---

## 5. Ce qui reste à faire (avant de continuer le développement)

À traiter en priorité selon la spec :

1. **Backend**  
   - API **Rapports** (génération + liste par patient).  
   - **Upload images** pansement (route + stockage).  
   - Définir et exposer **Analyses** (CRUD ou lien avec rapports/indicateurs).  
   - Optionnel : seuil alerte température à **38°C** (aujourd’hui 38.5°C dans `AlertService`).

2. **Web (Angular)**  
   - Brancher le bouton **Générer rapport** sur l’API rapports quand elle existe.  
   - Enrichir l’onglet **Analyses** de la page Suivi Patient quand l’API analyses sera définie.

3. **Mobile (Flutter)**  
   - Écran **Suivi post-op** (température + douleur, rafraîchissement).  
   - **Upload photo** pansement (caméra/galerie → API).  
   - **Analyses** (liste + envoi).  
   - **Messagerie** (liste, envoi, réception).  
   - **Notifications push** (FCM + déclenchement côté backend sur alerte).

4. **Sécurité / rôles**  
   - Optionnel : restreindre l’accès au **Dashboard Web** pour le rôle Patient (rediriger vers un message « Utilisez l’application mobile »).

Vous savez ainsi exactement où en est le projet et ce qu’il reste à faire avant de poursuivre.
