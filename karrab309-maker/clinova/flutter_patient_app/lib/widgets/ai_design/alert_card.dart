import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

enum AlertSeverity { info, warning, critical }

class AlertCard extends StatelessWidget {
  const AlertCard({
    super.key,
    required this.title,
    required this.message,
    this.severity = AlertSeverity.info,
    this.onAction,
    this.actionLabel,
  });

  final String title;
  final String message;
  final AlertSeverity severity;
  final VoidCallback? onAction;
  final String? actionLabel;

  Color get _border {
    switch (severity) {
      case AlertSeverity.critical:
        return AppTheme.statusCritical;
      case AlertSeverity.warning:
        return AppTheme.statusWarning;
      case AlertSeverity.info:
        return AppTheme.statusInfo;
    }
  }

  Color get _bg {
    switch (severity) {
      case AlertSeverity.critical:
        return AppTheme.statusCritical.withValues(alpha: 0.08);
      case AlertSeverity.warning:
        return AppTheme.statusWarning.withValues(alpha: 0.1);
      case AlertSeverity.info:
        return AppTheme.statusInfo.withValues(alpha: 0.08);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: _bg,
      borderRadius: BorderRadius.circular(AppTheme.radius),
      child: InkWell(
        onTap: onAction,
        borderRadius: BorderRadius.circular(AppTheme.radius),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppTheme.radius),
            border: Border.all(color: _border.withValues(alpha: 0.5)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800, color: _border)),
              const SizedBox(height: 6),
              Text(message, style: Theme.of(context).textTheme.bodyMedium),
              if (actionLabel != null && onAction != null) ...[
                const SizedBox(height: 10),
                TextButton(onPressed: onAction, child: Text(actionLabel!)),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
