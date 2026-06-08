import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// Bannière illustration (accueil, sections) — coins 20px, léger voile bas.
class ClinovaIllustrationBanner extends StatelessWidget {
  const ClinovaIllustrationBanner({
    super.key,
    required this.assetPath,
    this.height = 112,
    this.borderRadius = 20,
    this.semanticLabel,
  });

  final String assetPath;
  final double height;
  final double borderRadius;
  final String? semanticLabel;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: semanticLabel,
      image: semanticLabel != null,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(borderRadius),
        child: SizedBox(
          height: height,
          width: double.infinity,
          child: Stack(
            fit: StackFit.expand,
            children: [
              Image.asset(
                assetPath,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => _fallbackBackground(context),
              ),
              DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.white.withValues(alpha: 0.0),
                      Colors.black.withValues(alpha: 0.07),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _fallbackBackground(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppTheme.primaryLight,
            AppTheme.violet.withValues(alpha: 0.12),
          ],
        ),
      ),
      child: Icon(Icons.photo_outlined, size: 40, color: AppTheme.primary.withValues(alpha: 0.5)),
    );
  }
}

/// Vignette illustrative pour la zone [trailing] de [ClinovaPageHeader] (léger, pas de grande bannière).
class ClinovaHeaderThumb extends StatelessWidget {
  const ClinovaHeaderThumb({
    super.key,
    required this.assetPath,
    this.size = 58,
    this.borderRadius = 16,
    this.semanticLabel,
  });

  final String assetPath;
  final double size;
  final double borderRadius;
  final String? semanticLabel;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: semanticLabel,
      image: semanticLabel != null,
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(borderRadius),
          border: Border.all(color: AppTheme.border.withValues(alpha: 0.75)),
          boxShadow: [
            BoxShadow(
              color: AppTheme.primary.withValues(alpha: 0.06),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Image.asset(
          assetPath,
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => ColoredBox(
            color: AppTheme.primaryLight,
            child: Icon(Icons.image_outlined, color: AppTheme.primary.withValues(alpha: 0.45)),
          ),
        ),
      ),
    );
  }
}

/// Bandeau d’illustration intégré en haut d’une carte (coins supérieurs alignés sur la carte).
class ClinovaCardTopMedia extends StatelessWidget {
  const ClinovaCardTopMedia({
    super.key,
    required this.assetPath,
    this.height = 72,
    this.topRadius = 20,
  });

  final String assetPath;
  final double height;
  final double topRadius;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.vertical(top: Radius.circular(topRadius)),
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: Stack(
          fit: StackFit.expand,
          children: [
            Image.asset(
              assetPath,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      AppTheme.primaryLight,
                      AppTheme.violet.withValues(alpha: 0.1),
                    ],
                  ),
                ),
              ),
            ),
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  colors: [
                    Colors.white.withValues(alpha: 0.0),
                    Colors.white.withValues(alpha: 0.35),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Vignette horizontale pour une ligne type liste (carte « riche »).
class ClinovaListTileThumbnail extends StatelessWidget {
  const ClinovaListTileThumbnail({
    super.key,
    required this.assetPath,
    this.width = 76,
    this.height = 76,
    this.borderRadius = 18,
  });

  final String assetPath;
  final double width;
  final double height;
  final double borderRadius;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: SizedBox(
        width: width,
        height: height,
        child: Image.asset(
          assetPath,
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => ColoredBox(
            color: AppTheme.primaryLight.withValues(alpha: 0.6),
            child: Icon(Icons.photo_outlined, color: AppTheme.primary.withValues(alpha: 0.4)),
          ),
        ),
      ),
    );
  }
}

/// En-tête de page (équivalent `.page-head` web).
class ClinovaPageHeader extends StatelessWidget {
  const ClinovaPageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.trailing,
    this.icon,
    this.leading,
  });

  final String title;
  final String? subtitle;
  final Widget? trailing;
  final IconData? icon;
  /// Action à gauche (ex. retour) — évite un second bandeau / AppBar.
  final Widget? leading;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (leading != null) ...[
          leading!,
          const SizedBox(width: 6),
        ],
        if (icon != null) ...[
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppTheme.primary.withValues(alpha: 0.14),
                  AppTheme.violet.withValues(alpha: 0.10),
                ],
              ),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppTheme.border.withValues(alpha: 0.6)),
            ),
            child: Icon(icon, color: AppTheme.primary, size: 22),
          ),
          const SizedBox(width: 14),
        ],
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ShaderMask(
                shaderCallback: (rect) => const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [AppTheme.text, AppTheme.primary],
                ).createShader(rect),
                blendMode: BlendMode.srcIn,
                child: Text(
                  title,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.6,
                      ),
                ),
              ),
              if (subtitle != null && subtitle!.trim().isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  subtitle!,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: AppTheme.textMuted,
                        fontWeight: FontWeight.w600,
                        height: 1.35,
                      ),
                ),
              ],
            ],
          ),
        ),
        if (trailing != null) ...[
          const SizedBox(width: 12),
          trailing!,
        ],
      ],
    );
  }
}

/// Pill / badge (équivalent `.pill` web).
class ClinovaPill extends StatelessWidget {
  const ClinovaPill({
    super.key,
    required this.text,
    this.variant = ClinovaPillVariant.muted,
  });

  final String text;
  final ClinovaPillVariant variant;

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color border;
    Color fg;
    switch (variant) {
      case ClinovaPillVariant.danger:
        bg = const Color(0x1EEF4444);
        border = const Color(0x33EF4444);
        fg = const Color(0xFFB91C1C);
        break;
      case ClinovaPillVariant.newItem:
        bg = const Color(0x1E0D9488);
        border = const Color(0x330D9488);
        fg = const Color(0xFF0F766E);
        break;
      case ClinovaPillVariant.muted:
        bg = const Color(0x2394A3B8);
        border = const Color(0x3394A3B8);
        fg = const Color(0xFF334155);
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: border),
      ),
      child: Text(
        text.toUpperCase(),
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              fontWeight: FontWeight.w900,
              letterSpacing: 1.2,
              color: fg,
            ),
      ),
    );
  }
}

enum ClinovaPillVariant { muted, danger, newItem }

/// Empty state (équivalent `.empty-state` web).
class ClinovaEmptyState extends StatelessWidget {
  const ClinovaEmptyState({
    super.key,
    required this.title,
    required this.text,
    this.icon = Icons.inbox_rounded,
    this.illustrationAsset,
    this.showIconBadge = true,
  });

  final String title;
  final String text;
  final IconData icon;
  /// Image optionnelle au-dessus du message (rendu plus parlant pour l’utilisateur).
  final String? illustrationAsset;
  /// Si [illustrationAsset] est fourni : afficher quand même le badge icône en dessous.
  final bool showIconBadge;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 18),
      child: Column(
        children: [
          if (illustrationAsset != null) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(18),
              child: SizedBox(
                height: 128,
                width: double.infinity,
                child: Image.asset(
                  illustrationAsset!,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    color: AppTheme.background,
                    child: Icon(icon, color: AppTheme.textMuted, size: 48),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],
          if (showIconBadge)
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(18),
                color: AppTheme.violet.withValues(alpha: 0.12),
                border: Border.all(color: AppTheme.border.withValues(alpha: 0.7)),
              ),
              child: Icon(icon, color: AppTheme.violet, size: 26),
            ),
          if (showIconBadge) const SizedBox(height: 12),
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w900,
                  letterSpacing: -0.2,
                ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 6),
          Text(
            text,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppTheme.textMuted,
                  fontWeight: FontWeight.w600,
                  height: 1.4,
                ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

/// Card “modern” (proche `.card-modern` web).
class ClinovaModernCard extends StatelessWidget {
  const ClinovaModernCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(18),
    this.margin = EdgeInsets.zero,
    this.borderColor,
    this.backgroundColor,
    this.cornerRadius = 20,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry margin;
  final Color? borderColor;
  final Color? backgroundColor;
  final double cornerRadius;

  @override
  Widget build(BuildContext context) {
    final r = BorderRadius.circular(cornerRadius);
    return Container(
      margin: margin,
      decoration: BoxDecoration(
        color: backgroundColor ?? AppTheme.surface.withValues(alpha: 0.96),
        borderRadius: r,
        border: Border.all(color: borderColor ?? AppTheme.border.withValues(alpha: 0.85)),
        boxShadow: AppTheme.cardShadow,
      ),
      child: ClipRRect(
        borderRadius: r,
        child: Padding(
          padding: padding,
          child: child,
        ),
      ),
    );
  }
}

/// En-tête de section (icône + titre + sous-titre), style proche des cartes « card-modern » web.
class ClinovaSectionHeader extends StatelessWidget {
  const ClinovaSectionHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.icon = Icons.dashboard_customize_rounded,
  });

  final String title;
  final String? subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppTheme.primary.withValues(alpha: 0.14),
                AppTheme.violet.withValues(alpha: 0.1),
              ],
            ),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppTheme.border.withValues(alpha: 0.6)),
          ),
          child: Icon(icon, color: AppTheme.primary, size: 22),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.35,
                      color: AppTheme.text,
                    ),
              ),
              if (subtitle != null && subtitle!.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  subtitle!,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppTheme.textMuted,
                        height: 1.4,
                      ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

/// Carte surface blanche + ombre légère (équivalent .card web).
class ClinovaSurfaceCard extends StatelessWidget {
  const ClinovaSurfaceCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(18),
    this.margin = EdgeInsets.zero,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry margin;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radius),
        border: Border.all(color: AppTheme.border),
        boxShadow: AppTheme.cardShadow,
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppTheme.radius),
        child: Padding(
          padding: padding,
          child: child,
        ),
      ),
    );
  }
}
