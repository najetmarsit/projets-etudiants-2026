# Vision UX/UI Clinova — référence produit

Ce document aligne la **description cible** de l’application Clinova (tableaux de bord par rôle, cartes, analytics, navigation, mode sombre) avec l’**implémentation actuelle** du dépôt et trace les écarts pour les prochaines itérations.

## Principes retenus (cible)

- **Rôles** : médecins, infirmiers, laboratoire, administration — écrans et indicateurs contextualisés.
- **Lisibilité** : cartes structurées, espacement, hiérarchie typographique, codes couleur **sémantiques** (info, succès, alerte, critique).
- **Données** : mini-graphiques et KPI pour vue d’ensemble sans surcharge cognitive.
- **Navigation** : accès rapide (RDV, dossiers, analyses), barre inférieure ou hub « Plus » selon le périmètre mobile.
- **Multi-plateforme** : Flutter mobile + Angular web + API Laravel ; même identité **teal / violet** Clinova.

## État du code (référence rapide)

| Zone | Emplacement / statut |
|------|----------------------|
| **AI UI Engine (Laravel + JSON)** | `app/Services/AIUIEngineService.php`, `SmartImageSelectorService.php`, `AIUIController.php`, `config/ai_ui.php`, `docs/AI_UI_JSON_SCHEMA.md` |
| **AI UI Flutter** | `lib/design_system/` (`AiUiPayload`, `AIScreenBuilder`), `lib/widgets/ai_design/` |
| Thème teal/violet, dark | `flutter_patient_app/lib/theme/app_theme.dart`, `ThemePreferencesService` |
| Couleurs statut (info / succès / avertissement / critique) | `AppTheme.statusInfo`, `statusSuccess`, `statusWarning`, `statusCritical` |
| Patient — accueil analytics | `patient_dashboard_tab.dart` |
| Staff — mini dashboard | `staff_dashboard_tab.dart` |
| Graphiques légers (sans lib lourde) | `lib/widgets/analytics/` |
| Performance liste / détail patient | `docs/PERF_PATIENT_FLOW.md`, caches `ApiService` |

## Écarts par rapport aux maquettes type « showcase »

Les écrans illustrés (timeline patient médicale dédiée, agenda médecin plein écran 5 onglets fixes, vue lab « Uploading / Validated », etc.) sont une **cible design** : ils peuvent être rapprochés progressivement sans dupliquer chaque libellé des maquettes (certaines captures contiennent des fautes volontaires ou placeholders — à ne pas copier telles quelles en production).

### Pistes d’implémentation priorisées

1. **Badges de statut** : utiliser `AppTheme.status*` sur listes RDV, alertes, analyses, notifications.
2. **Timeline médicale** : enrichir ou router vers un écran timeline réutilisant `MedicalTimeline` + données API existantes (opérations, suivi, rapports).
3. **Médecin / infirmier** : rapprocher l’en-tête d’accueil du mockup (salutation + prochains créneaux) en s’appuyant sur les endpoints déjà exposés.
4. **Angular web** : paralléliser le même langage visuel (SCSS partagé conceptuellement avec `AppTheme`).

## Rappel médical / conformité

Toute évolution UI doit **conserver** les flux validés, le JWT, les rôles RBAC et les endpoints ; les maquettes guident le **rendu** et l’**ergonomie**, pas la logique métier sous-jacente.
