# Architecture du projet – Suivi Patient

Le projet est composé de **trois parties totalement séparées**, connectées **uniquement via l’API REST Laravel**.

---

## 1. Dashboard Web (Médecin / Admin)

- **Destinataires** : Médecins et administrateurs uniquement. Aucune logique patient mobile.
- **Accès** : Les patients connectés sur le Web sont redirigés vers une page les invitant à utiliser l’application mobile.
- **Contenu** :
  - **Navbar** : Tableau de bord – Patients – Alertes – Rapports – Profil médecin (ou Profil pour Admin).
  - **Page Suivi Patient** : Le médecin peut **remplir et mettre à jour toutes les informations médicales** du patient : card (nom, âge, type d’opération), **observations / commentaires du médecin** (champ texte éditable, enregistré via l’API ; visible immédiatement par le patient dans l’app mobile), indicateurs en temps réel, **photos pansement envoyées par le patient**, graphique d’évolution (température + douleur), onglets (Données préopératoires, Suivi postopératoire, Messages, Analyses), boutons (Envoyer message, **Générer rapport**, Ajouter patient). **Ajouter des analyses** = créer des rapports (POST /api/reports) ; le patient les voit dans son onglet Analyses après actualisation.
  - **Alertes** : Affichage des alertes (ex. température > 38°C = alerte rouge). Logique gérée par l’API.
  - **Design** : Médical moderne (bleu/vert), clair, responsive.

---

## 2. Application Mobile Flutter (Patient uniquement) – Espace personnel

Chaque patient dispose **obligatoirement d’un espace personnel** (app mobile) pour suivre l’évolution de son état de santé. Aucune logique métier n’est dans l’app : tout passe par l’API.

- **Destinataires** : Patients uniquement.
- **Fonctionnalités (toutes synchronisées avec le Dashboard Web via l’API)** :
  - **État de santé en temps réel** : consultation des indicateurs (température, douleur, pansement), saisie de la température et de la douleur (POST /api/health-indicators). Le médecin voit les données sur la page Suivi Patient.
  - **Observations du médecin** : lecture des commentaires détaillés rédigés par le médecin (champ `doctor_observations` du patient, fourni par GET /api/patients/:id). Dès que le médecin enregistre, le patient peut actualiser (tirer pour rafraîchir) et les voir.
  - **Rapports rédigés par le médecin** : consultation des rapports et analyses (GET /api/reports). Bouton « Actualiser » et tirer pour rafraîchir : **dès que le médecin ajoute un rapport ou une analyse, le patient le voit immédiatement** après actualisation.
  - **Évolution** : suivi température, douleur, analyses, observations dans un même espace (onglet Suivi + onglet Analyses).
  - **Messages** : envoi au médecin et réception des réponses (GET/POST /api/messages).
  - **Photos de la plaie postopératoire** : téléversement via caméra (POST /api/patients/{id}/dressing-photo). Le médecin voit les images sur la page Suivi Patient.
- **Synchronisation** : **Système totalement lié** : le médecin saisit et met à jour (observations, rapports, analyses) ; le patient consulte et suit son état. Toute donnée passe par l’API Laravel ; rôles et permissions sécurisés (doctor/patient).
- **Interface** : Titre « Mon espace santé », onglets Suivi / Photos / Analyses / Messages ; simple, intuitive, cohérente avec le Web (bleu/vert).
- **Notifications push** : à brancher (FCM) pour alertes médicales.

---

## 3. Backend – API REST Laravel (cœur du système)

- **Rôle** : Seul point de communication entre Web et Mobile.
- **Contenu** :
  - Authentification JWT (médecin et patient). **Séparation claire des rôles** (`Doctor`, `Patient`, `Admin`) et **permissions sécurisées** (middleware `auth:api`, vérification du rôle dans chaque contrôleur).
  - CRUD : Patients (avec champ **doctor_observations** : seul le médecin/admin peut le modifier ; le patient peut le lire), Rapports/Analyses, Messages, Alertes, Indicateurs de santé.
  - Endpoint upload image pansement.
  - **Logique automatique d’alertes** (température > 38°C, douleur > 7, pansement infecté) : entièrement dans l’API (HealthIndicator + AlertService).
- **Base de données** : Centralisée (MySQL/PostgreSQL).
- **Réponses** : JSON uniquement. **Connexion obligatoire** : Web et Mobile ne communiquent que via cette API.

---

## Contraintes

- Aucune logique métier (règles médicales, alertes) dans le frontend ; tout est géré par l’API.
- **Séparation stricte des rôles et des permissions** : le médecin met à jour les infos médicales et rédige rapports/observations ; le patient consulte et suit son état. La connexion entre les deux **passe obligatoirement par l’API Laravel**.
- Schéma : **Mobile (patient) → API → Base de données** et **Web (médecin) → API → Base de données**.
