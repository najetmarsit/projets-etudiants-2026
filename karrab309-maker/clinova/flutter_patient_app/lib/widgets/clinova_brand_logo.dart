import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../config/app_assets.dart';

/// Taille du logo Clinova sur fond dégradé (connexion, splash, etc.).
enum ClinovaBrandLogoSize {
  /// Grand logo — même ordre de grandeur que l’écran web Angular.
  hero,

  /// Légèrement plus compact pour le splash (toujours bien visible).
  splash,
}

/// Logo Clinova (PNG fond transparent) dans un cadre type « verre » sur dégradé.
class ClinovaBrandLogo extends StatelessWidget {
  const ClinovaBrandLogo({
    super.key,
    this.size = ClinovaBrandLogoSize.hero,
  });

  final ClinovaBrandLogoSize size;

  @override
  Widget build(BuildContext context) {
    final mq = MediaQuery.sizeOf(context);
    final shortest = mq.shortestSide;
    final h = mq.height;
    // Évite le débordement vertical (Chrome bas, clavier) : plafonner selon la hauteur.
    final double outer = switch (size) {
      ClinovaBrandLogoSize.hero => math.min(
            (shortest * 0.38).clamp(200.0, 340.0),
            (h * 0.40).clamp(140.0, 340.0),
          ),
      ClinovaBrandLogoSize.splash => math.min(
            (shortest * 0.34).clamp(168.0, 300.0),
            (h * 0.36).clamp(120.0, 300.0),
          ),
    };
    final double inner = (outer * 0.86).clamp(120.0, 280.0);

    return Container(
      width: outer,
      height: outer,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.22),
        borderRadius: BorderRadius.circular(36),
        border: Border.all(color: Colors.white.withValues(alpha: 0.28)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.28),
            blurRadius: 40,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Image.asset(
        AppAssets.appLogo,
        width: inner,
        height: inner,
        fit: BoxFit.contain,
        errorBuilder: (_, __, ___) => Icon(
          Icons.health_and_safety_rounded,
          size: inner * 0.38,
          color: Colors.white,
        ),
      ),
    );
  }
}
