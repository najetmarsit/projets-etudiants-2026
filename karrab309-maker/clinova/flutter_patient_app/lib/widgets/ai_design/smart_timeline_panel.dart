import 'package:flutter/material.dart';
import '../analytics/medical_timeline.dart';
import '../../theme/app_theme.dart';

/// Timeline médicale dans le design system AI (réutilise [MedicalTimeline]).
class SmartTimelinePanel extends StatelessWidget {
  const SmartTimelinePanel({
    super.key,
    required this.title,
    required this.entries,
  });

  final String title;
  final List<MedicalTimelineEntry> entries;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
        const SizedBox(height: 10),
        MedicalTimeline(entries: entries),
        if (entries.isEmpty)
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text('Aucun élément', style: TextStyle(color: AppTheme.textMuted)),
          ),
      ],
    );
  }
}
