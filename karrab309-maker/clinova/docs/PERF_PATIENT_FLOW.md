# Performance — flux patients (Laravel + Flutter)

## Objectifs de cette phase

- Réduire le temps de réponse des listes et du détail patient (SQL + payload JSON).
- Pagination curseur côté API déjà présente : exploitation complète côté Flutter avec **scroll infini**.
- **Recherche serveur** (`q`) avec debounce côté mobile.
- **Stale-while-revalidate** et caches dédiés côté client Flutter (`ApiService` + `CacheService`).
- Parsing JSON lourd des patients via **`compute()`** (isolates).

## Changements Laravel

### `PatientController`

- **Index** : paramètre optionnel `q` (recherche sur prénom, nom, téléphone, CIN normalisé) inclus dans la clé de cache API.
- **Show** : relations volumineuses limitées et triées (évite chargement de milliers de lignes en une requête) :
  - `operations` : 150 dernières + `doctor`
  - `health_indicators` : 200 derniers + `recordedBy`
  - `alerts` : 150
  - `reports` : 120
  - `lab_documents` : 100 + `uploader`

Les endpoints et le format JSON (`success`, `data`, `meta` pour l’index) restent **identiques** pour les clients existants ; seuls le volume de données et les filtres optionnels évoluent.

### Migration `2026_05_28_120000_add_patient_list_search_indexes.php`

Index sur `first_name`, `last_name`, et composite `(assigned_doctor_id, id)` pour accélérer listes médecin et recherche.

```bash
php artisan migrate
```

## Changements Flutter

### `ApiService`

- `getPatientsPage({ cursor, perPage, search, forceRefresh })` → `PatientListResult` (items + `nextCursor` + `hasMore`).
- `getPatients()` : raccourci première page (compatibilité).
- `getPatient(id, { forceRefresh })` : cache SWR + parse en isolate.
- `myPatient({ forceRefresh })` : cache court.
- Caches SWR : `getHealthIndicators`, `getReports`, `getLabDocumentsForPatient`, `getOperations`.
- `invalidatePatientCaches(patientId)` après mutations (ex. constantes vitales, mise à jour médecin).

### Écrans

- **`patients_list_screen`** : `CustomScrollView` + `SliverList` (virtualisation par sliver), debounce 380 ms, infinite scroll, plus de double cache `CacheService` pour la liste.
- **`patient_detail_screen`** : `Future.wait` pour `me` + `getPatient`, puis bilan / notes / constantes en parallèle selon le rôle.
- **`patient_dossier_tab`** : `myPatient` + `getPatient` en parallèle.
- **`suivi_tab`** : `forceRefresh` au pull-to-refresh.

## Mesures avant / après (manuel)

### Backend (exemple avec curl — remplacer TOKEN et HOST)

```powershell
Measure-Command { curl.exe -s -H "Authorization: Bearer TOKEN" "http://127.0.0.1:8000/api/patients?per_page=35" | Out-Null }
Measure-Command { curl.exe -s -H "Authorization: Bearer TOKEN" "http://127.0.0.1:8000/api/patients/1" | Out-Null }
```

Comparer la durée **avant** migration + sans limite sur `show` (branche précédente) et **après** : la liste doit rester stable ; le détail doit baisser nettement si le dossier avait beaucoup d’historique.

### Flutter

1. Lancer l’app sur un appareil ou émulateur milieu de gamme.
2. Ouvrir la liste patients : premier affichage doit pouvoir utiliser le cache au retour sur l’onglet.
3. Faire défiler jusqu’en bas : chargement des pages suivantes sans bloquer le thread UI (parsing en isolate pour la première page).

## Notes Android bas de gamme

- Réduire `per_page` (déjà 35 par défaut côté Flutter) si besoin réseau très lent.
- Éviter `forceRefresh: true` en boucle ; le SWR suffit pour la navigation habituelle.
