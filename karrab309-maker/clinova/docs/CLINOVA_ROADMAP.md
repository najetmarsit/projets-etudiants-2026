# Clinova — feuille de route d’amélioration (non destructive)

Ce document structure les livrables demandés en **phases compatibles** avec l’existant (JWT, APIs, rendez-vous, dossiers).

## Phase 1 — Fondations (en cours / livrée)

| Domaine | Action | Fichiers |
|--------|--------|----------|
| Laravel perf | `CacheService` branché sur `config/optimization.php` | `app/Services/CacheService.php` |
| Laravel sécurité | Rate limit login + API depuis config | `RouteServiceProvider`, `routes/api.php` |
| Laravel analytics | `GET /api/dashboard/analytics` (données réelles, cache) | `DashboardController` |
| Laravel photos | Compression JPEG profil (GD) | `ProfileImageOptimizer`, `AuthController` |
| Angular | Menu Analytics admin, API analytics, profil OnPush | `main-layout`, `admin-analytics`, `profile` |
| Angular UX | Thème : respect `prefers-color-scheme` si pas de préférence sauvegardée | `theme.service.ts` |
| Flutter | Skeleton loaders + purge cache au logout | `profile_tab`, `patients_list_screen`, `api_service` |

## Phase 2 — Dashboards par rôle (livré)

- **Admin** : `/admin/analytics` + `GET /api/dashboard/analytics`.
- **Médecin** : `GET /api/dashboard/analytics/doctor` + KPI sur dashboard.
- **Laboratoire** : `GET /api/dashboard/analytics/lab` + graphiques `lab-dashboard`.
- **Comptable** : graphiques admissions + flux sur `accountant-dashboard`.
- **Réceptionniste** : `GET /api/dashboard/analytics/secretary` + dashboard dédié.

Voir `docs/CLINOVA_OPTIMIZATIONS.md` pour le détail technique.

## Phase 3 — UX/UI premium

- Design tokens médicaux (bleu / vert / gris) — déjà partiellement dans `styles.scss`.
- Glassmorphism léger, skeletons généralisés, toasts unifiés.
- Mode sombre : Angular OK ; activer toggle Flutter (`ThemeMode`).

## Phase 4 — Fonctionnalités complémentaires

- Timeline patient, export PDF, QR dossier (partiellement présents côté API — à exposer proprement).
- Notifications temps réel (SSE déjà là) + emails queue.
- Recherche globale debounced, exports Excel/PDF via jobs.

## Phase 5 — Infra performance

- `php artisan config:cache route:cache view:cache` en production.
- Redis (`CACHE_DRIVER=redis`) si disponible.
- Queues (`QUEUE_CONNECTION=database`) pour exports lourds.
- PWA Angular (service worker) — optionnel.

## Contraintes respectées

- Aucune modification des signatures d’API existantes supprimées.
- Ajouts modulaires (nouveaux endpoints, services, composants).
- Logique métier inchangée ; cache et sécurité en couche transverse.

## Commandes utiles (production)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```
