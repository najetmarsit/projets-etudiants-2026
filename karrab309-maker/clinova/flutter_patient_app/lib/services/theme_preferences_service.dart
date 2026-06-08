import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Préférences UX : thème, taille de texte, onboarding.
class ThemePreferencesService {
  ThemePreferencesService._();
  static final ThemePreferencesService instance = ThemePreferencesService._();

  static const _themeKey = 'clinova.theme_mode';
  static const _textScaleKey = 'clinova.text_scale';
  static const _onboardingDoneKey = 'clinova.onboarding_done';

  final ValueNotifier<ThemeMode> themeMode = ValueNotifier(ThemeMode.system);
  final ValueNotifier<double> textScale = ValueNotifier(1.0);

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final themeIndex = prefs.getInt(_themeKey) ?? ThemeMode.system.index;
    themeMode.value = ThemeMode.values[themeIndex.clamp(0, ThemeMode.values.length - 1)];
    textScale.value = prefs.getDouble(_textScaleKey) ?? 1.0;
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    themeMode.value = mode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_themeKey, mode.index);
  }

  Future<void> setTextScale(double scale) async {
    final v = scale.clamp(0.85, 1.35);
    textScale.value = v;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setDouble(_textScaleKey, v);
  }

  Future<bool> isOnboardingDone() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_onboardingDoneKey) ?? false;
  }

  Future<void> setOnboardingDone() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_onboardingDoneKey, true);
  }
}
