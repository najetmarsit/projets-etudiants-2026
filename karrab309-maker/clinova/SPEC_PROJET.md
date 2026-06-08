# Spécification projet – Application médicale Suivi Patient

Document de référence aligné sur votre cahier des charges : **Dashboard Web (médecin/admin)**, **Application Mobile (patient)**, **API REST Laravel** centralisée avec JWT.

---

## 1. État des lieux par rapport à votre spec

### 1.1 Partie Web (Dashboard médecin) – Angular

| Exigence | État actuel | À faire |
|----------|-------------|--------|
| **Navbar** : Tableau de bord, Patients, Alertes, Rapports, Profil médecin | Sidebar avec Tableau de bord, Patients, Opérations, Indicateurs santé, Alertes, Messages, Utilisateurs (Admin). Profil = nom + rôle + Déconnexion. | Ajouter **Rapports** dans la nav ; renommer/regrouper si besoin (ex. « Rapports » dédié). |
| **Page Suivi Patient** : Card patient (nom, âge, type d’opération) | Page détail patient avec infos, opérations, indicateurs, alertes. Pas de card dédiée « Suivi Patient » avec type d’opération mis en avant. | Créer une **vue Suivi Patient** dédiée : card patient (nom, âge, type d’opération), indicateurs en temps réel (douleur, température, pansement). |
| **Indicateurs en temps réel** : douleur (faible/modérée/sévère), température (°C), pansement (normal/à surveiller) | Indicateurs listés en tableau (health-indicators) et dans détail patient. Pas de libellés « faible/modérée/sévère » ni « normal/à surveiller » ni affichage type cartes. | Afficher les **derniers indicateurs** en cartes avec libellés (douleur : faible/modérée/sévère ; pansement : normal/à surveiller) et seuils cohérents avec les alertes. |
| **Graphique d’évolution postopératoire** (température + douleur) | Non implémenté. | Ajouter un **graphique** (ex. Chart.js ou ng2-charts) : évolution température et douleur dans le temps pour le patient sélectionné. |
| **Onglets** : Données préopératoires, Suivi postopératoire, Messages, Analyses | Détail patient = sections en liste (opérations, indicateurs, alertes). Pas d’onglets. | Introduire **onglets** sur la page Suivi Patient : Données préopératoires, Suivi postopératoire, Messages, Analyses. |
| **Boutons** : Envoyer message, Générer rapport, Ajouter patient | Liste patients : « Ajouter patient ». Pas de « Envoyer message » / « Générer rapport » depuis la fiche patient. | Ajouter **Envoyer message** et **Générer rapport** sur la page Suivi Patient ; garder **Ajouter patient** (déjà présent). |
| **Alertes automatiques** (ex. température > 38°C, alerte rouge) | Backend : AlertService (ex. temp > 38.5, douleur > 7, pansement « Infected »). Front : liste alertes, acknowledge. | Aligner seuil **température > 38°C** si vous le souhaitez ; afficher **alerte rouge** (bannière/carte) sur la page Suivi Patient quand alerte active. |
| **Design** : cartes claires, bleu/vert, responsive | Thème bleu, cartes, layout responsive. | Affiner couleurs (bleu/vert médical), cohérence des cartes et espacements. |

### 1.2 Partie Mobile (Flutter – patient)

| Exigence | État actuel | À faire |
|----------|-------------|--------|
| **Suivi post-op** : température et douleur en temps réel | App Flutter : login, liste patients, détail patient (données chargées une fois). | Page **Suivi post-op** dédiée patient : affichage **temps réel** (rafraîchissement ou WebSocket) température + douleur. |
| **Téléverser photo du pansement** (caméra) | Non. | **Upload image** pansement : endpoint API + écran Flutter (caméra/galerie) et envoi au backend. |
| **Liste des analyses** + envoi des résultats au médecin | Non. | Modèle **Analyses** (ou lien avec health_indicators/reports) ; API liste + envoi ; écran Flutter liste + envoi. |
| **Messagerie** avec le médecin | API messages existante. | Écran **messagerie** Flutter (liste, envoi, réception) branché sur `/api/messages`. |
| **Notifications push** pour les alertes | Non. | Mise en place **push** (Firebase FCM ou équivalent) + backend ou job pour déclencher l’envoi lors de la création d’alerte. |
| **Design** moderne, cohérent avec le Web | App basique (login, liste, détail). | Charte couleurs (bleu/vert), écrans dédiés, navigation claire. |

### 1.3 Backend – API REST Laravel

| Exigence | État actuel | À faire |
|----------|-------------|--------|
| **Authentification JWT** (médecin + patient) | Login/register, JWT, rôles Admin/Doctor/Patient. | Considérer restriction : register **patient** depuis mobile uniquement si besoin. |
| **CRUD** : Patients, Analyses, Messages, Alertes | Patients, Operations, HealthIndicators, Messages, Alertes en place. Reports (modèle) sans API exposée. | Définir **Analyses** (table/API CRUD ou réutiliser reports/health_indicators) ; exposer **Rapports** (génération + liste par patient). |
| **Endpoint upload images pansement** | Aucun. | **POST** (ex. `/api/patients/{id}/dressing-photos` ou `/api/health-indicators` avec pièce jointe) ; stockage (storage) + lien en BDD. |
| **Calcul et notifications automatiques des alertes** (ex. temp > 38°C) | AlertService sur création indicateur (seuils : temp 38.5/36, douleur > 7, dressing « Infected »). | Ajuster seuil à **38°C** si souhaité ; brancher **notification** (email, push, etc.) à la création d’alerte. |
| **Base centralisée, JSON** | MySQL/PostgreSQL, réponses JSON. | Rien à changer si déjà en place. |

### 1.4 Rôles et accès

| Rôle | Accès attendu | État |
|------|----------------|-----|
| **Médecin** | Dashboard Web complet (navbar, suivi patient, alertes, rapports, profil). | Web Angular : rôle Doctor ; à compléter par les écrans/actions ci‑dessus. |
| **Admin** | Même chose + gestion utilisateurs/patients. | Rôle Admin, page Utilisateurs ; à garder. |
| **Patient** | Uniquement application Mobile (suivi, photo, analyses, messagerie, alertes push). | Patient peut aujourd’hui se connecter au Web aussi ; on peut restreindre côté front (redirect si Patient sur Web). |

---

## 2. Plan de développement structuré

### Phase 1 – Backend (API)

1. **Seuils d’alertes** : aligner température sur 38°C dans `AlertService` / `HealthIndicator` si besoin.
2. **Upload images pansement** : migration (table ou colonne), contrôleur, route `POST`, stockage Laravel, lien patient/indicateur.
3. **Rapports** : activer `ReportController` (routes API), génération (PDF ou JSON) depuis les données patient/indicateurs/alertes.
4. **Analyses** : décider si « Analyses » = rapports, ou nouvelle entité ; exposer CRUD et lien patient/médecin.

### Phase 2 – Dashboard Web (Angular)

1. **Navbar** : ajouter entrée « Rapports », ordre : Tableau de bord, Patients, Alertes, Rapports, Profil.
2. **Page Suivi Patient** dédiée (route ex. `/patients/:id/suivi`) :
   - Card patient : nom, âge, type d’opération (dernière opération).
   - Cartes indicateurs : douleur (faible/modérée/sévère), température (°C), pansement (normal/à surveiller).
   - Bannière alerte rouge si alerte active (ex. température > 38°C).
3. **Onglets** : Données préopératoires, Suivi postopératoire, Messages, Analyses (contenus selon API).
4. **Graphique** : évolution postopératoire (température + douleur) avec données `health-indicators` par patient.
5. **Boutons** : Envoyer message (lien messagerie ou modal), Générer rapport (appel API rapports).
6. **Design** : couleurs bleu/vert, cartes et espacements homogènes.

### Phase 3 – Application Mobile (Flutter)

1. **Suivi post-op** : écran avec dernière température et douleur, refresh périodique ou pull-to-refresh.
2. **Upload photo pansement** : caméra/galerie, appel API upload, affichage dans la liste/suivi.
3. **Analyses** : écran liste + envoi des résultats (API).
4. **Messagerie** : écran conversation avec le médecin (API messages).
5. **Notifications push** : FCM + backend (envoi sur création alerte).
6. **Design** : aligné avec la charte Web (bleu/vert).

### Phase 4 – Sécurité et polish

1. Restriction d’accès Web pour le rôle Patient (redirect vers message « Utilisez l’application mobile »).
2. Tests API (upload, rapports, alertes).
3. Revue responsive et accessibilité.

---

## 3. Fichiers clés existants

- **API** : `routes/api.php`, `app/Http/Controllers/*`, `app/Services/AlertService.php`, `app/Models/HealthIndicator.php` (méthode `shouldTriggerAlert`).
- **Web** : `angular-app/` (layout dans `features/layout`, patients dans `features/patients`, dashboard, alertes, messages).
- **Mobile** : `flutter_patient_app/` (auth, liste/détail patients).
- **Doc API** : `API.md`.

---

## 4. Prochaines étapes recommandées

1. Valider ce document (seuils 38°C, contenu « Analyses » vs Rapports).
2. Implémenter **Phase 1** (backend : upload, rapports, analyses, seuil 38°C).
3. Puis **Phase 2** (page Suivi Patient complète + graphique + onglets + boutons).
4. Ensuite **Phase 3** (Flutter : suivi temps réel, photo, messagerie, push).

Si vous indiquez par quoi vous voulez commencer (ex. « page Suivi Patient avec graphique » ou « upload photo pansement »), on peut détailler les modifications fichier par fichier pour cette partie.
