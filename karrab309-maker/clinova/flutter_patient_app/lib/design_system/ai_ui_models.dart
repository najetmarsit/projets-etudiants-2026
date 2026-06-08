/// Payload `data` de `POST /api/ai-ui/generate`.
class AiUiPayload {
  AiUiPayload({
    required this.raw,
    required this.theme,
    required this.layout,
    required this.uiMode,
    required this.primaryColor,
    required this.secondaryColor,
    required this.background,
    required this.textColor,
    required this.components,
    required this.priorityOrder,
    required this.imageUrls,
    required this.imageFallback,
  });

  final Map<String, dynamic> raw;
  final String theme;
  final String layout;
  final String uiMode;
  final String primaryColor;
  final String secondaryColor;
  final String background;
  final String textColor;
  final List<String> components;
  final List<String> priorityOrder;
  final List<String> imageUrls;
  final String imageFallback;

  factory AiUiPayload.fromJson(Map<String, dynamic> json) {
    final images = json['images'] as Map<String, dynamic>? ?? {};
    final urls = (images['urls'] as List<dynamic>?)?.map((e) => '$e').toList() ?? <String>[];

    return AiUiPayload(
      raw: json,
      theme: json['theme'] as String? ?? 'medical_blue_teal',
      layout: json['layout'] as String? ?? 'balanced_clinical',
      uiMode: json['ui_mode'] as String? ?? 'normal',
      primaryColor: json['primary_color'] as String? ?? '#2563EB',
      secondaryColor: json['secondary_color'] as String? ?? '#14B8A6',
      background: json['background'] as String? ?? '#F8FAFC',
      textColor: json['text'] as String? ?? '#0F172A',
      components: (json['components'] as List<dynamic>?)?.map((e) => '$e').toList() ?? [],
      priorityOrder: (json['priority_order'] as List<dynamic>?)?.map((e) => '$e').toList() ?? [],
      imageUrls: urls,
      imageFallback: images['fallback'] as String? ?? '',
    );
  }
}
