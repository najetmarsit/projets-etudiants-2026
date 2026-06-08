import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

class MedicalTimelineEntry {
  const MedicalTimelineEntry({
    required this.title,
    required this.subtitle,
    required this.timeLabel,
    this.icon = Icons.circle,
    this.color,
  });

  final String title;
  final String subtitle;
  final String timeLabel;
  final IconData icon;
  final Color? color;
}

class MedicalTimeline extends StatelessWidget {
  const MedicalTimeline({super.key, required this.entries, this.maxItems = 5});

  final List<MedicalTimelineEntry> entries;
  final int maxItems;

  @override
  Widget build(BuildContext context) {
    final list = entries.take(maxItems).toList();
    if (list.isEmpty) {
      return const SizedBox.shrink();
    }
    return Column(
      children: List.generate(list.length, (i) {
        final e = list[i];
        final isLast = i == list.length - 1;
        final color = e.color ?? AppTheme.primary;
        return IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              SizedBox(
                width: 36,
                child: Column(
                  children: [
                    Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.12),
                        shape: BoxShape.circle,
                        border: Border.all(color: color.withValues(alpha: 0.35)),
                      ),
                      child: Icon(e.icon, size: 14, color: color),
                    ),
                    if (!isLast)
                      Expanded(
                        child: Container(
                          width: 2,
                          margin: const EdgeInsets.symmetric(vertical: 4),
                          color: AppTheme.border,
                        ),
                      ),
                  ],
                ),
              ),
              Expanded(
                child: Padding(
                  padding: EdgeInsets.only(bottom: isLast ? 0 : 14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              e.title,
                              style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                            ),
                          ),
                          Text(
                            e.timeLabel,
                            style: Theme.of(context).textTheme.labelSmall?.copyWith(color: AppTheme.textMuted),
                          ),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(e.subtitle, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textMuted)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      }),
    );
  }
}
