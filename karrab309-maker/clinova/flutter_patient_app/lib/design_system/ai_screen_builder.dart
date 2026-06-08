import 'package:flutter/material.dart';
import 'ai_design_tokens.dart';
import 'ai_ui_models.dart';

/// Applique le thème issu du moteur AI UI et construit les enfants dans l’ordre [priorityOrder] (logique métier côté parent).
class AIScreenBuilder extends StatelessWidget {
  const AIScreenBuilder({
    super.key,
    required this.payload,
    required this.builder,
  });

  final AiUiPayload payload;
  final Widget Function(BuildContext context, AiUiPayload payload) builder;

  @override
  Widget build(BuildContext context) {
    final theme = AiDesignTokens.overlayTheme(context, {
      'primary_color': payload.primaryColor,
      'secondary_color': payload.secondaryColor,
    });
    return Theme(
      data: theme,
      child: Builder(
        builder: (ctx) => builder(ctx, payload),
      ),
    );
  }
}
