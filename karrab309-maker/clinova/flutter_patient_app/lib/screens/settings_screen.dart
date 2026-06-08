import 'package:flutter/material.dart';
import '../services/theme_preferences_service.dart';
import '../theme/app_theme.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prefs = ThemePreferencesService.instance;
    return Scaffold(
      appBar: AppBar(title: const Text('Paramètres')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('Apparence', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          ValueListenableBuilder<ThemeMode>(
            valueListenable: prefs.themeMode,
            builder: (context, mode, _) {
              return SegmentedButton<ThemeMode>(
                segments: const [
                  ButtonSegment(value: ThemeMode.system, label: Text('Système'), icon: Icon(Icons.brightness_auto_rounded)),
                  ButtonSegment(value: ThemeMode.light, label: Text('Clair'), icon: Icon(Icons.light_mode_rounded)),
                  ButtonSegment(value: ThemeMode.dark, label: Text('Sombre'), icon: Icon(Icons.dark_mode_rounded)),
                ],
                selected: {mode},
                onSelectionChanged: (s) => prefs.setThemeMode(s.first),
              );
            },
          ),
          const SizedBox(height: 24),
          Text('Accessibilité', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          ValueListenableBuilder<double>(
            valueListenable: prefs.textScale,
            builder: (context, scale, _) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Taille du texte : ${(scale * 100).round()}%', style: TextStyle(color: AppTheme.textMuted)),
                  Slider(
                    value: scale,
                    min: 0.85,
                    max: 1.35,
                    divisions: 10,
                    label: '${(scale * 100).round()}%',
                    onChanged: prefs.setTextScale,
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 8),
          const ListTile(
            leading: Icon(Icons.accessibility_new_rounded),
            title: Text('Contrastes renforcés'),
            subtitle: Text('Utilisez le mode sombre système pour un meilleur confort visuel.'),
          ),
        ],
      ),
    );
  }
}
