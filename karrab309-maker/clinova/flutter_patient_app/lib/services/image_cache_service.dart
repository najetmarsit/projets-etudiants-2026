import 'dart:io';
import 'dart:typed_data';
import 'package:crypto/crypto.dart';
import 'package:flutter/foundation.dart';
import 'package:path_provider/path_provider.dart';

class ImageCacheService {
  static final ImageCacheService _instance = ImageCacheService._internal();
  factory ImageCacheService() => _instance;
  ImageCacheService._internal();

  final _memoryCache = <String, Uint8List>{};
  final _memoryOrder = <String>[];
  static const _maxMemoryBytes = 50 * 1024 * 1024;
  int _currentMemoryBytes = 0;

  Uint8List? getFromMemory(String key) => _memoryCache[key];

  void cacheInMemory(String key, Uint8List bytes) {
    while (_currentMemoryBytes + bytes.length > _maxMemoryBytes && _memoryOrder.isNotEmpty) {
      final oldest = _memoryOrder.removeAt(0);
      final removed = _memoryCache.remove(oldest);
      if (removed != null) _currentMemoryBytes -= removed.length;
    }
    if (!_memoryCache.containsKey(key)) {
      _memoryOrder.add(key);
    }
    _memoryCache[key] = bytes;
    _currentMemoryBytes += bytes.length;
  }

  Future<File> _diskFile(String key) async {
    final dir = await getTemporaryDirectory();
    final hash = md5.convert(key.codeUnits).toString();
    return File('${dir.path}/clinova_img_$hash.bin');
  }

  Future<void> cacheToDisk(String key, Uint8List bytes) async {
    if (kIsWeb) return;
    try {
      final file = await _diskFile(key);
      await file.writeAsBytes(bytes, flush: true);
    } catch (_) {}
  }

  Future<Uint8List?> getFromDisk(String key) async {
    if (kIsWeb) return null;
    try {
      final file = await _diskFile(key);
      if (!await file.exists()) return null;
      return await file.readAsBytes();
    } catch (_) {
      return null;
    }
  }

  Future<void> clearDiskCache() async {
    if (kIsWeb) return;
    try {
      final dir = await getTemporaryDirectory();
      await for (final f in dir.list()) {
        if (f is File && f.path.contains('clinova_img_')) {
          await f.delete();
        }
      }
    } catch (_) {}
  }

  void clearMemoryCache() {
    _memoryCache.clear();
    _memoryOrder.clear();
    _currentMemoryBytes = 0;
  }
}
