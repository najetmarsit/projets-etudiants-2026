import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

class LabResultCard extends StatelessWidget {
  const LabResultCard({
    super.key,
    required this.title,
    required this.subtitle,
    this.isAbnormal = false,
    this.trailing,
    this.onTap,
  });

  final String title;
  final String subtitle;
  final bool isAbnormal;
  final Widget? trailing;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final accent = isAbnormal ? AppTheme.statusCritical : AppTheme.statusSuccess;
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
        side: BorderSide(color: accent.withValues(alpha: 0.45), width: isAbnormal ? 1.5 : 1),
      ),
      child: ListTile(
        onTap: onTap,
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text(subtitle),
        trailing: trailing ?? Icon(isAbnormal ? Icons.warning_amber_rounded : Icons.check_circle_rounded, color: accent),
      ),
    );
  }
}
