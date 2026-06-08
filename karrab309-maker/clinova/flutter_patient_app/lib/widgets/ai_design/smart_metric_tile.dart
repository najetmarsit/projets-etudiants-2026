import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

class SmartMetricTile extends StatelessWidget {
  const SmartMetricTile({
    super.key,
    required this.label,
    required this.value,
    this.trend,
    this.icon = Icons.insights_rounded,
  });

  final String label;
  final String value;
  final String? trend;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.35),
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
        border: Border.all(color: AppTheme.border.withValues(alpha: 0.6)),
      ),
      child: Row(
        children: [
          Icon(icon, color: Theme.of(context).colorScheme.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: Theme.of(context).textTheme.labelMedium?.copyWith(color: AppTheme.textMuted)),
                Text(value, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                if (trend != null)
                  Text(trend!, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: AppTheme.statusSuccess)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
