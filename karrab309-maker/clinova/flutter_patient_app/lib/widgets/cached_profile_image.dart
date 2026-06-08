import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../services/image_cache_service.dart';
import '../utils/media_url.dart';

/// Avatar réseau avec cache mémoire + disque (fichiers temp).
class CachedProfileImage extends StatefulWidget {
  final String url;
  final double size;
  final BoxFit fit;

  const CachedProfileImage({
    super.key,
    required this.url,
    this.size = 48,
    this.fit = BoxFit.cover,
  });

  @override
  State<CachedProfileImage> createState() => _CachedProfileImageState();
}

class _CachedProfileImageState extends State<CachedProfileImage> {
  Uint8List? _bytes;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _resolve();
  }

  @override
  void didUpdateWidget(covariant CachedProfileImage oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.url != widget.url) {
      _resolve();
    }
  }

  Future<void> _resolve() async {
    final cache = ImageCacheService();
    final resolved = resolveApiPublicUrl(widget.url) ?? widget.url;
    final key = resolved;
    final mem = cache.getFromMemory(key);
    if (mem != null) {
      if (mounted) setState(() { _bytes = mem; _loading = false; });
      return;
    }
    final disk = await cache.getFromDisk(key);
    if (disk != null) {
      cache.cacheInMemory(key, disk);
      if (mounted) setState(() { _bytes = disk; _loading = false; });
      return;
    }
    try {
      final res = await http.get(Uri.parse(resolved));
      if (res.statusCode == 200) {
        final b = res.bodyBytes;
        cache.cacheInMemory(key, b);
        await cache.cacheToDisk(key, b);
        if (mounted) setState(() { _bytes = b; _loading = false; });
      } else if (mounted) {
        setState(() => _loading = false);
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return SizedBox(
        width: widget.size,
        height: widget.size,
        child: const CircleAvatar(child: Icon(Icons.person, size: 20)),
      );
    }
    if (_bytes == null) {
      return CircleAvatar(
        radius: widget.size / 2,
        child: Icon(Icons.person, size: widget.size * 0.45),
      );
    }
    return ClipOval(
      child: Image.memory(
        _bytes!,
        width: widget.size,
        height: widget.size,
        fit: widget.fit,
        gaplessPlayback: true,
      ),
    );
  }
}
