# Schéma JSON — Clinova AI UI Engine

## `POST /api/ai-ui/generate`

**Corps (tous optionnels — défauts côté serveur)**

| Champ | Type | Valeurs |
|-------|------|----------|
| `screen` | string | `dashboard`, `patient_profile`, `patient_timeline`, `lab_results`, `appointments`, `messages`, `alerts`, `notifications`, `emergency` |
| `role` | string | `Patient`, `Doctor`, `Nurse`, `Admin`, `Secretary`, `Laboratory`, `Accountant` |
| `patient_status` | string | `normal`, `warning`, `critical` |
| `data_density` | string | `low`, `medium`, `high` |

**Réponse `data` (exemple)**

```json
{
  "version": 1,
  "context": {
    "screen": "patient_profile",
    "role": "Doctor",
    "patient_status": "critical",
    "data_density": "high"
  },
  "theme": "emergency_red",
  "layout": "compact_priority",
  "ui_mode": "critical",
  "primary_color": "#EF4444",
  "secondary_color": "#B91C1C",
  "background": "#FEF2F2",
  "text": "#0F172A",
  "accent_warning": "#F59E0B",
  "accent_critical": "#DC2626",
  "images": {
    "urls": ["https://...", "https://...", "https://..."],
    "fallback": "https://...",
    "source": "curated|pexels"
  },
  "image_hints": ["emergency_room", "doctor_action", "monitor"],
  "components": ["SmartCard", "AlertCard", "AIImageHeader", "SmartTimeline"],
  "priority_order": ["alerts", "summary", "history"],
  "ux_rules": {
    "max_hero_images": 1,
    "progressive_disclosure": true,
    "medical_clean": true
  }
}
```

## `POST /api/ai-ui/context`

Retourne `context` normalisé + `preview` (`ui_mode`, `primary`).

## `GET /api/ai-images/screen`

**Query**

- `screen` (requis)
- `mode` : `normal` | `warning` | `critical`

**Réponse `data`**

```json
{
  "urls": ["...", "...", "..."],
  "fallback": "...",
  "source": "curated|pexels"
}
```

## Sécurité & conformité

- Aucune **PHI** n’est envoyée aux APIs Pexels ; uniquement des requêtes texte génériques en anglais.
- Sans `PEXELS_API_KEY`, le moteur utilise des **URLs curated** (fallback obligatoire).
- Cache Laravel : clé dérivée du contexte ; TTL `AI_UI_CACHE_TTL` (secondes).

## Flutter

- Modèle : `AiUiPayload` (`lib/design_system/ai_ui_models.dart`)
- Builder : `AIScreenBuilder` (`lib/design_system/ai_screen_builder.dart`)
- Composants : `lib/widgets/ai_design/*`
