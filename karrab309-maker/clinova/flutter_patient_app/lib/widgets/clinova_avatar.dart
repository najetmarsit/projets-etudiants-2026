import 'package:crypto/crypto.dart';
import 'dart:convert';
import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import '../utils/media_url.dart';

/// Avatar avec initiales et couleur déterministe (sans photo).
class ClinovaAvatar extends StatelessWidget {
  const ClinovaAvatar({
    super.key,
    required this.name,
    this.size = 44,
    this.imageUrl,
  });

  final String name;
  final double size;
  final String? imageUrl;

  static Color _colorFor(String seed) {
    final hash = md5.convert(utf8.encode(seed)).bytes;
    final hues = [AppTheme.primary, AppTheme.violet, AppTheme.accent, AppTheme.primaryDark];
    return hues[hash[0] % hues.length];
  }

  static String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts.first.substring(0, 1).toUpperCase();
    return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final color = _colorFor(name);
    final resolved = resolveApiPublicUrl(imageUrl);
    if (resolved != null && resolved.isNotEmpty) {
      return CircleAvatar(
        radius: size / 2,
        backgroundColor: color.withValues(alpha: 0.15),
        backgroundImage: NetworkImage(resolved),
      );
    }
    return CircleAvatar(
      radius: size / 2,
      backgroundColor: color.withValues(alpha: 0.18),
      child: Text(
        _initials(name),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: size * 0.36,
        ),
      ),
    );
  }
}
