import 'user_model.dart';
import '../utils/media_url.dart';

class MessageModel {
  final int id;
  final int senderId;
  final int receiverId;
  final String content;
  final bool readStatus;
  final String? attachmentUrl;
  final UserModel? sender;
  final UserModel? receiver;
  final String? createdAt;

  MessageModel({
    required this.id,
    required this.senderId,
    required this.receiverId,
    required this.content,
    required this.readStatus,
    this.attachmentUrl,
    this.sender,
    this.receiver,
    this.createdAt,
  });

  factory MessageModel.fromJson(Map<String, dynamic> json) {
    return MessageModel(
      id: json['id'] as int,
      senderId: json['sender_id'] as int,
      receiverId: json['receiver_id'] as int,
      content: json['content'] as String? ?? '',
      readStatus: json['read_status'] as bool? ?? false,
      attachmentUrl: resolveApiPublicUrl(json['attachment_url'] as String?),
      sender: json['sender'] != null ? UserModel.fromJson(json['sender'] as Map<String, dynamic>) : null,
      receiver: json['receiver'] != null ? UserModel.fromJson(json['receiver'] as Map<String, dynamic>) : null,
      createdAt: json['created_at'] as String?,
    );
  }
}
