class NotificationModel {
  final int id;
  final int? patientId;
  final String audience;
  final int? recipientUserId;
  final String channel;
  final String type;
  final String title;
  final String? body;
  final String priority;
  final Map<String, dynamic>? data;
  final DateTime? readAt;
  final DateTime? acknowledgedAt;
  final DateTime createdAt;

  NotificationModel({
    required this.id,
    required this.patientId,
    required this.audience,
    required this.recipientUserId,
    required this.channel,
    required this.type,
    required this.title,
    required this.body,
    required this.priority,
    required this.data,
    required this.readAt,
    required this.acknowledgedAt,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    DateTime? parseDt(dynamic v) => v == null ? null : DateTime.tryParse(v.toString());
    return NotificationModel(
      id: json['id'] as int,
      patientId: json['patient_id'] as int?,
      audience: json['audience'] as String? ?? '',
      recipientUserId: json['recipient_user_id'] as int?,
      channel: json['channel'] as String? ?? '',
      type: json['type'] as String? ?? '',
      title: json['title'] as String? ?? '',
      body: json['body'] as String?,
      priority: json['priority'] as String? ?? 'normal',
      data: (json['data'] as Map?)?.cast<String, dynamic>(),
      readAt: parseDt(json['read_at']),
      acknowledgedAt: parseDt(json['acknowledged_at']),
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ?? DateTime.fromMillisecondsSinceEpoch(0),
    );
  }

  bool get isUnread => readAt == null;
  bool get isUnacknowledged => acknowledgedAt == null;
  bool get isUrgent => priority.toLowerCase() == 'urgent';
}

