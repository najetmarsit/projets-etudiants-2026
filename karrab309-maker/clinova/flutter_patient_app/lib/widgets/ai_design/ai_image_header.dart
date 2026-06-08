import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

/// En-tête visuel max 1 image hero (règle UX médicale).
class AIImageHeader extends StatelessWidget {
  const AIImageHeader({
    super.key,
    required this.imageUrl,
    this.fallbackUrl,
    this.height = 140,
    this.title,
    this.subtitle,
  });

  final String imageUrl;
  final String? fallbackUrl;
  final double height;
  final String? title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final fallback = fallbackUrl ?? imageUrl;
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppTheme.radius),
      child: Stack(
        alignment: Alignment.bottomLeft,
        children: [
          SizedBox(
            height: height,
            width: double.infinity,
            child: Image.network(
              imageUrl,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Image.network(
                fallback,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  color: AppTheme.primaryLight,
                  child: const Icon(Icons.medical_services_rounded, size: 48, color: AppTheme.primary),
                ),
              ),
            ),
          ),
          if (title != null)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 32, 16, 12),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.transparent, Colors.black.withValues(alpha: 0.55)],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(title!, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
                  if (subtitle != null)
                    Text(subtitle!, style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 13)),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
