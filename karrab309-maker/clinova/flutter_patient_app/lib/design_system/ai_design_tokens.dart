import 'package:flutter/material.dart';

/// Tokens alignés sur la spec AI UI + fallback Clinova.
abstract final class AiDesignTokens {
  static Color parseHex(String? hex, Color fallback) {
    if (hex == null || hex.isEmpty) return fallback;
    var s = hex.trim();
    if (s.startsWith('#')) s = s.substring(1);
    if (s.length == 6) s = 'FF$s';
    final v = int.tryParse(s, radix: 16);
    if (v == null) return fallback;
    return Color(v);
  }

  static ThemeData overlayTheme(BuildContext context, Map<String, dynamic> payload) {
    final primary = parseHex(payload['primary_color'] as String?, const Color(0xFF2563EB));
    final secondary = parseHex(payload['secondary_color'] as String?, const Color(0xFF14B8A6));
    final base = Theme.of(context);
    final scheme = ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      secondary: secondary,
      brightness: base.brightness,
    );
    return base.copyWith(colorScheme: scheme);
  }
}
