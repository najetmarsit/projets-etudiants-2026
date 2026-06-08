import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../theme/app_theme.dart';

/// Onglet de la barre inférieure (mobile) — style Clinova unifié.
class ClinovaMobileNavItem {
  const ClinovaMobileNavItem({
    required this.label,
    required this.icon,
    required this.iconSelected,
  });

  final String label;
  final IconData icon;
  final IconData iconSelected;
}

/// Barre de navigation basse partagée (patient / médecin / infirmier).
/// Contrainte en hauteur explicite + texte dans [Expanded] + [FittedBox] → évite tout overflow du bas.
class ClinovaMobileBottomNav extends StatelessWidget {
  const ClinovaMobileBottomNav({
    super.key,
    required this.currentIndex,
    required this.onSelect,
    required this.items,
    this.enableHaptics = true,
  });

  final int currentIndex;
  final ValueChanged<int> onSelect;
  final List<ClinovaMobileNavItem> items;
  final bool enableHaptics;

  /// Hauteur minimum du bloc onglets (icône + libellé), hors [SafeArea] bas.
  static const double _baseContentHeight = 76;
  static const double _minSlotWidth = 52;
  static const double _scrollItemWidth = 72;

  static double _tileHeight(BuildContext context) {
    final bump = (MediaQuery.textScalerOf(context).scale(11) - 11).clamp(0.0, 22.0);
    return _baseContentHeight + bump;
  }

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) return const SizedBox.shrink();

    final width = MediaQuery.sizeOf(context).width;
    final count = items.length;
    final slotW = count > 0 ? (width - 20) / count : _minSlotWidth;
    final scrollable = slotW < _minSlotWidth;
    final tileH = _tileHeight(context);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final surfaceColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final muted = isDark ? const Color(0xFF94A3B8) : AppTheme.textMuted;

    Widget buildTile(int index, {double? fixedWidth}) {
      final d = items[index];
      final selected = index == currentIndex;
      final labelStyle = Theme.of(context).textTheme.labelSmall?.copyWith(
            fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
            fontSize: selected ? 11 : 10,
            color: selected ? AppTheme.primary : muted,
            letterSpacing: -0.2,
          );

      final iconDecoration = AnimatedContainer(
        duration: const Duration(milliseconds: 220),
        curve: Curves.easeOutCubic,
        padding: const EdgeInsets.all(7),
        decoration: BoxDecoration(
          gradient: selected
              ? LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppTheme.primaryLight,
                    AppTheme.primaryLight.withValues(alpha: 0.58),
                  ],
                )
              : null,
          borderRadius: BorderRadius.circular(15),
          boxShadow: selected
              ? [
                  BoxShadow(
                    color: AppTheme.primary.withValues(alpha: 0.16),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ]
              : null,
          border: Border.all(
            color: selected ? AppTheme.primary.withValues(alpha: 0.3) : Colors.transparent,
          ),
        ),
        child: Icon(
          selected ? d.iconSelected : d.icon,
          size: selected ? 24 : 22,
          color: selected ? AppTheme.primaryDark : muted.withValues(alpha: 0.88),
        ),
      );

      final tile = Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            if (currentIndex != index) {
              if (enableHaptics) HapticFeedback.selectionClick();
              onSelect(index);
            }
          },
          borderRadius: BorderRadius.circular(18),
          splashColor: AppTheme.primary.withValues(alpha: 0.12),
          highlightColor: AppTheme.primary.withValues(alpha: 0.06),
          child: SizedBox(
            height: tileH,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 1),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Expanded(
                    flex: 62,
                    child: Center(child: iconDecoration),
                  ),
                  Expanded(
                    flex: 38,
                    child: Align(
                      alignment: Alignment.topCenter,
                      child: Padding(
                        padding: const EdgeInsets.only(left: 1, right: 1, top: 1),
                        child: FittedBox(
                          fit: BoxFit.scaleDown,
                          alignment: Alignment.topCenter,
                          child: Text(
                            d.label,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.center,
                            style: (labelStyle ?? Theme.of(context).textTheme.labelSmall)?.copyWith(height: 1.05),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );

      if (fixedWidth != null) {
        return SizedBox(width: fixedWidth, child: tile);
      }
      return tile;
    }

    return Material(
      color: surfaceColor,
      elevation: 12,
      shadowColor: Colors.black.withValues(alpha: 0.07),
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
        side: BorderSide(color: AppTheme.border.withValues(alpha: 0.6)),
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
        child: DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                surfaceColor,
                (isDark ? const Color(0xFF0F172A) : AppTheme.background).withValues(alpha: 0.5),
              ],
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                height: 2,
                width: double.infinity,
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        AppTheme.primary.withValues(alpha: 0),
                        AppTheme.primary.withValues(alpha: 0.48),
                        AppTheme.violet.withValues(alpha: 0.36),
                        AppTheme.primary.withValues(alpha: 0),
                      ],
                    ),
                  ),
                ),
              ),
              SafeArea(
                top: false,
                minimum: EdgeInsets.zero,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(4, 6, 4, 6),
                  child: SizedBox(
                    height: tileH,
                    child: scrollable
                        ? ListView.separated(
                            scrollDirection: Axis.horizontal,
                            physics: const BouncingScrollPhysics(),
                            padding: const EdgeInsets.symmetric(horizontal: 2),
                            itemCount: count,
                            separatorBuilder: (_, __) => const SizedBox(width: 2),
                            itemBuilder: (context, index) => buildTile(index, fixedWidth: _scrollItemWidth),
                          )
                        : Row(
                            children: List.generate(
                              count,
                              (i) => Expanded(child: buildTile(i)),
                            ),
                          ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
