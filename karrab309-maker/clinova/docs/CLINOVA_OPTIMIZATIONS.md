# Clinova — guide d’optimisation et livrables

Document de référence pour les améliorations **non destructives** (auth JWT, APIs existantes, rendez-vous, dossiers inchangés).

---

## 1. Performance backend (Laravel)

| Optimisation | Implémentation | Pourquoi |
|--------------|----------------|----------|
| Cache dashboard | `CacheService` + `config/optimization.php` | Réduit les agrégats SQL répétés (stats, analytics) |
| Eager loading | `PatientController::index()` avec `with([...])` | Évite les requêtes N+1 sur user / médecin |
| Rate limiting | `RouteServiceProvider` (`api`, `login`) | Protection brute-force + abus API |
| Compression / ETag | `PerformanceMiddleware` sur groupe `api` | Headers sécurité + 304 si contenu inchangé |
| Photos profil | `ProfileImageOptimizer` (GD → JPEG 1024px) | Moins de bande passante, stockage uniforme |
| Audit | `AuditMiddleware` | Trace POST/PUT/PATCH/DELETE sensibles |

**Commandes production** (voir aussi `scripts/optimize-clinova.ps1`) :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

**Redis** (optionnel) : `.env` → `CACHE_DRIVER=redis`

---

## 2. Performance frontend (Angular)

| Optimisation | Fichiers |
|--------------|----------|
| Lazy loading routes | `app.routes.ts` (`loadComponent`) |
| OnPush | `dashboard`, `admin-analytics`, `profile`, … |
| Skeletons | `shared/ui/skeleton/` |
| Graphiques SVG légers | `shared/charts/` (pas de Chart.js lourd) |
| Debounce / lazy images | `shared/directives/` |
| Loader global HTTP | `global-loader.interceptor.ts`, `GlobalLoaderService` |
| Thème clair/sombre | `theme.service.ts`, `_design-tokens.scss` |

**Build production** :

```bash
cd angular-app && npm run build
```

---

## 3. Dashboards analytics par rôle

| Rôle | Route API | UI Angular |
|------|-----------|------------|
| Admin | `GET /api/dashboard/analytics` | `/admin/analytics` |
| Médecin | `GET /api/dashboard/analytics/doctor` | `/doctor/dashboard` (+ KPI perso) |
| Laboratoire | `GET /api/dashboard/analytics/lab` | `/lab/dashboard` |
| Comptable | `financial-overview` + admissions | `/accountant/dashboard` (+ graphiques) |
| Réceptionniste | `GET /api/dashboard/analytics/secretary` | `/secretary/dashboard` |

Réponse type (médecin) :

```json
{
  "success": true,
  "data": {
    "assigned_patients": 12,
    "active_patients": 8,
    "pending_alerts": 2,
    "consultations_30d": { "labels": ["..."], "values": [1, 0, 2] }
  }
}
```

---

## 4. Photos de profil

| Fonctionnalité | Endpoint | Rôle |
|----------------|----------|------|
| Upload optimisé | `POST /api/auth/profile-photo` | Tous (JWT) |
| Crop (même pipeline) | `POST /api/auth/profile-photo/crop` | Tous |
| Suppression | `DELETE /api/auth/profile-photo` | Tous |
| Affichage | `GET /api/users/{id}/photo` | Autorisé selon rôle |

Formats : **JPG, PNG, WEBP** — max **5 Mo** — compression automatique via `ProfileImageOptimizer`.

UI : `angular-app/src/app/features/profile/profile.component.ts` (preview, toast, avatar par défaut).

---

## 5. Sécurité

- Validation stricte uploads (`image`, `mimes`, dimensions max 2048px)
- CSRF sur routes `web` ; API stateless JWT
- `AuditMiddleware` sur mutations sensibles
- Throttle login : 5 tentatives / minute (configurable)
- Headers : `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`

---

## 6. Flutter (patient)

| Élément | Fichier |
|---------|---------|
| Cache API | `lib/services/cache_service.dart` |
| Images | `lib/services/image_cache_service.dart` |
| Skeletons | `lib/widgets/skeleton_widgets.dart` |
| Thème | `lib/theme/app_theme.dart` |

---

## 7. Structure modulaire recommandée

```
app/
  Http/Controllers/     # Contrôleurs fins
  Services/             # Cache, images, notifications
  Http/Middleware/      # Perf, audit
angular-app/src/app/
  core/                 # API, auth, interceptors
  shared/charts|ui/     # Composants réutilisables
  features/{role}/      # Écrans par portail
docs/
  CLINOVA_ROADMAP.md    # Phases futures
  CLINOVA_OPTIMIZATIONS.md  # Ce fichier
scripts/
  optimize-clinova.ps1  # Cache Laravel Windows
```

---

## 8. Ce qui reste (phases suivantes, sans casser l’existant)

- PWA / service worker Angular
- Jobs queue pour exports PDF/Excel lourds
- Table `activity_logs` dédiée (au-delà des logs fichier)
- Crop canvas avancé côté Angular (lib légère type `cropperjs`)
- Notifications push Flutter (FCM)

---

## 9. Vérification rapide

```bash
# API
php artisan route:list --path=dashboard

# Angular
cd angular-app && npm run build
```

Toute régression : désactiver le cache dashboard via `.env` → `CACHE_DASHBOARD_ENABLED=false`.
