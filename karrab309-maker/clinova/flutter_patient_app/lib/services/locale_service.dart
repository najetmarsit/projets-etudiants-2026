import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _localeKey = 'medical_locale';

class LocaleService {
  static const supportedLocales = [
    Locale('en'),
    Locale('fr'),
    Locale('ar'),
  ];

  static final localeNotifier = ValueNotifier<Locale?>(null);

  static Future<Locale?> getSavedLocale() async {
    final prefs = await SharedPreferences.getInstance();
    final code = prefs.getString(_localeKey);
    if (code == null) return null;
    if (code == 'ar') return const Locale('ar');
    if (code == 'fr') return const Locale('fr');
    return const Locale('en');
  }

  static Future<void> saveLocale(String code) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_localeKey, code);
  }

  static Future<void> applyLocaleFromUser(String? locale) async {
    if (locale == null || locale.isEmpty) return;
    if (locale == 'ar' || locale == 'fr' || locale == 'en') {
      await saveLocale(locale);
      localeNotifier.value = Locale(locale);
    }
  }
}
