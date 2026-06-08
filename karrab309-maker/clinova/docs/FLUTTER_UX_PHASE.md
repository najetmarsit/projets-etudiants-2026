# Phase UX/UI mobile Flutter — Clinova

## Objectif

Moderniser l’expérience mobile (design premium teal/violet, fluidité, analytics) **sans modifier** les APIs Laravel, JWT ni les workflows métier.

## Livrables principaux

### Design system & thème
- `lib/services/theme_preferences_service.dart` — `ThemeMode.system` / clair / sombre, échelle de texte
- `lib/main.dart` — thème dynamique + `TextScaler` accessibilité
- Barre de navigation basse compatible mode sombre

### Composants analytics (CustomPainter, sans lib chart)
- `lib/widgets/analytics/mini_line_chart.dart`
- `lib/widgets/analytics/mini_bar_chart.dart`
- `lib/widgets/analytics/progress_ring.dart`
- `lib/widgets/analytics/kpi_card.dart`
- `lib/widgets/analytics/medical_timeline.dart`

### Écrans
- `lib/screens/patient_dashboard_tab.dart` — KPI patient, graphiques RDV / glycémie / tension, timeline, raccourcis
- `lib/screens/staff_dashboard_tab.dart` — KPI médecin / infirmier
- `lib/screens/splash_screen.dart` — auto-login JWT
- `lib/screens/onboarding_screen.dart` — première ouverture
- `lib/screens/settings_screen.dart` — thème + taille texte

### Navigation
- **Patient** : 4 onglets (Accueil, Suivi, RDV, Plus) — dossier / finance / rapports dans « Plus » + raccourcis dashboard
- **Médecin** : 5 onglets (+ Accueil dashboard)
- **Infirmier** : 4 onglets (+ Accueil dashboard)

### UX transversal
- `lib/widgets/clinova_avatar.dart` — initiales colorées
- `lib/widgets/clinova_empty_state.dart` — états vides + retry
- `lib/widgets/clinova_page_route.dart` — transitions fade/slide

## Prochaines étapes suggérées

- Remplacer les `CircularProgressIndicator` restants par skeletons sur login / PDF
- Swipe actions sur listes messages / notifications
- Recherche globale (overlay)
- Hero animations sur avatars patient
- Favoris utilisateur (SharedPreferences)

## Test manuel

```bash
cd flutter_patient_app
flutter pub get
flutter analyze
flutter run -d chrome
```

Vérifier : splash → onboarding (1ère fois) → auto-login → dashboard patient ; bascule thème dans Paramètres (onglet Plus).
