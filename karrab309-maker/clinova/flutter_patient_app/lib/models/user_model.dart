import '../utils/media_url.dart';

class UserModel {
  final int id;
  final String name;
  final String username;
  final String email;
  final String role;
  final String? locale;
  final String? profilePhotoUrl;

  UserModel({
    required this.id,
    required this.name,
    required this.username,
    required this.email,
    required this.role,
    this.locale,
    this.profilePhotoUrl,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      username: json['username'] as String? ?? '',
      email: json['email'] as String? ?? '',
      role: json['role'] as String? ?? 'Patient',
      locale: json['locale'] as String?,
      profilePhotoUrl: resolveApiPublicUrl(json['profile_photo_url'] as String?),
    );
  }
}
