import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'clinova_ui.dart';

/// Carte d’erreur réseau / API avec action de nouvel essai.
class ErrorStateCard extends StatelessWidget {
  const ErrorStateCard({
    super.key,
    required this.message,
    required this.retryLabel,
    required this.onRetry,
    this.hint,
  });

  final String message;
  final String retryLabel;
  final String? hint;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return ClinovaSurfaceCard(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.error_outline_rounded, color: Theme.of(context).colorScheme.error, size: 28),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  message,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.error,
                    height: 1.35,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
          if (hint != null && hint!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(hint!, style: TextStyle(color: AppTheme.textMuted, fontSize: 13, height: 1.35)),
          ],
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh_rounded, size: 20),
            label: Text(retryLabel),
            style: FilledButton.styleFrom(
              backgroundColor: AppTheme.primary,
              foregroundColor: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}
