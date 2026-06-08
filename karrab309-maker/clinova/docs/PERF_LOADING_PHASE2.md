# Clinova — Phase performance chargement des données (Phase 2)

Mesures et optimisations **sans modification des contrats JWT ni suppression d’endpoints**.

## Résumé des gains attendus

| Zone | Avant | Après |
|------|--------|--------|
| Dashboard Angular admin/médecin | 4–6 requêtes séquentielles + doublon `/dashboard/stats` | `forkJoin` + cache mémoire 90s (stale 30s) |
| Layout + dashboard | 2× `GET /dashboard/stats` | 1× (cache partagé `dashboard:stats`) |
| Flutter patient au démarrage | ~8 requêtes (5 onglets montés) | 1 onglet (lazy) + cache stale |
| API Laravel lectures | Pas de cache HTTP | Cache 45–90s + invalidation par version |
| Liste patients | Tout charger d’un coup | Cursor pagination + « Charger plus » |

## Backend (Laravel)

### `ApiCacheService`
- Cache `auth/me`, notifications, messages, index patients
- Invalidation par **version** (`notif_ver`, `msg_ver`, `patients_global_ver`)
- Headers `Cache-Control: private` + `X-Cache: api`

### Endpoints
| Route | Cache TTL (.env) | Pagination |
|-------|-------------------|------------|
| `GET /auth/me` | `CACHE_AUTH_ME_TTL` (90s) | — |
| `GET /notifications` | `CACHE_NOTIFICATIONS_TTL` (45s) | `page`, `per_page`, `limit` |
| `GET /messages` | `CACHE_MESSAGES_TTL` (45s) | `page`, `per_page` |
| `GET /patients` | `CACHE_PATIENT_LIST_TTL` (60s) | `per_page`, `cursor` |

Réponse patients normalisée :
```json
{
  "success": true,
  "data": [ /* items */ ],
  "meta": { "next_cursor": "...", "per_page": 50, "has_more": true }
}
```

### SQL
- Eager loading conservé (`with` sur user, doctor)
- Messages : colonnes limitées sur sender/receiver

## Angular

### `ApiCacheService` (stale-while-revalidate)
- **Fresh** (< `staleMs`) : retour immédiat, pas de réseau
- **Stale** : retour cache + refresh arrière-plan (`X-Background-Refresh` → pas de loader global)
- **Dedup** : `shareReplay` sur requêtes en vol

Clés : `auth:me`, `dashboard:stats`, `patients:list:*`, `notifications:*`, `messages:*`

### Dashboard
- `forkJoin` pour stats + chart + recent + doctor analytics
- Médecin seul : **plus** d’appel chart-data redondant (analytics médecin uniquement)
- Poll inbox : 30s au lieu de 10s

### Liste patients
- Pagination cursor + bouton « Charger plus »

## Flutter

### Lazy tabs (`LazyIndexedTab`)
- Les onglets ne montent qu’à la **première visite**
- Réduction ~80 % des appels au cold start patient

### Cache stale (`CacheService`)
- `getStale` + refresh background pour `me()` et `getPatients()`
- Parsing JSON listes dans **isolate** (`compute`)

### UI
- `SkeletonList` à la place des `CircularProgressIndicator` (onglets principaux)
- `CachedProfileImage` + cache disque fichiers (`path_provider`)

## Mesurer avant / après

### Script PowerShell
```powershell
.\scripts\benchmark-api.ps1 -Token "VOTRE_JWT"
```

### Manuel (Chrome DevTools)
1. Network → Disable cache **off** pour tester stale
2. Noter **DOMContentLoaded** et nombre de requêtes XHR au login dashboard
3. Répéter après déploiement

### Cibles indicatives (réseau local XAMPP)
| Métrique | Cible post-optim |
|----------|------------------|
| 2e chargement dashboard (cache chaud) | < 200 ms perçu |
| Cold start Flutter (1 onglet) | ≤ 3 requêtes |
| `GET /patients` 2e hit serveur | Cache hit < 50 ms |

## Variables `.env` recommandées

```env
CACHE_API_ENABLED=true
CACHE_AUTH_ME_TTL=90
CACHE_NOTIFICATIONS_TTL=45
CACHE_MESSAGES_TTL=45
CACHE_PATIENT_LIST_TTL=60
CACHE_DASHBOARD_TTL=120
```

Pour Redis en production : `CACHE_DRIVER=redis`

## Compatibilité

- JWT inchangé
- Routes existantes conservées (`limit` notifications toujours supporté)
- Clients anciens : `data` patients reste un tableau à la racine de `data`

## Extensions phase 2b (médecin / infirmier / réception)

| Zone | Changement |
|------|------------|
| Flutter médecin / infirmier | `LazyIndexedTab` — un seul onglet chargé au démarrage |
| Flutter API staff | Cache stale + `compute` pour notifications et alertes |
| Angular réception | `SecretaryDashboardComponent` (`/secretary/dashboard`) |
| Angular réception | `getDoctors()` et `getSecretaryAnalytics()` en cache SWR |
| Liste médecins réception | Polling 30s (au lieu de 10s) |
