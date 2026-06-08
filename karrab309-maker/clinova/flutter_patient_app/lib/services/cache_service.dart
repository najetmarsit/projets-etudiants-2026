import 'dart:collection';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

class CacheEntry<T> {
  final T data;
  final DateTime fetchedAt;
  final DateTime staleAt;
  final DateTime expiresAt;

  CacheEntry(this.data, this.fetchedAt, this.staleAt, this.expiresAt);

  bool get isExpired => DateTime.now().isAfter(expiresAt);
  bool get isStale => DateTime.now().isAfter(staleAt);
}

class CacheService {
  static final CacheService _instance = CacheService._internal();
  factory CacheService() => _instance;
  CacheService._internal();

  final _memoryCache = LinkedHashMap<String, CacheEntry<dynamic>>();
  static const _defaultTtl = Duration(minutes: 5);
  static const _defaultStale = Duration(seconds: 30);
  static const _maxMemoryEntries = 50;

  T? get<T>(String key) {
    final entry = _memoryCache[key];
    if (entry == null) return null;
    if (entry.isExpired) {
      _memoryCache.remove(key);
      return null;
    }
    final data = entry.data;
    if (data is T) return data;
    _memoryCache.remove(key);
    return null;
  }

  /// Retourne les données même si stale (pour affichage immédiat).
  /// Ne caste pas [CacheEntry] en [CacheEntry<T>] : la map est [CacheEntry<dynamic>] (web / VM).
  T? getStale<T>(String key) {
    final entry = _memoryCache[key];
    if (entry == null || entry.isExpired) {
      if (entry != null) _memoryCache.remove(key);
      return null;
    }
    final data = entry.data;
    if (data is T) return data;
    _memoryCache.remove(key);
    return null;
  }

  bool isStale(String key) {
    final entry = _memoryCache[key];
    if (entry == null) return true;
    if (entry.isExpired) {
      _memoryCache.remove(key);
      return true;
    }
    return entry.isStale;
  }

  void set<T>(String key, T data, {Duration? ttl, Duration? stale}) {
    if (_memoryCache.length >= _maxMemoryEntries) {
      _memoryCache.remove(_memoryCache.keys.first);
    }
    final now = DateTime.now();
    final ttlDur = ttl ?? _defaultTtl;
    final staleDur = stale ?? _defaultStale;
    _memoryCache[key] = CacheEntry(
      data,
      now,
      now.add(staleDur),
      now.add(ttlDur),
    );
  }

  void invalidate(String key) {
    _memoryCache.remove(key);
  }

  /// Supprime toutes les entrées dont la clé satisfait [predicate] (ex. préfixe patients:).
  void removeKeysWhere(bool Function(String key) predicate) {
    final keys = _memoryCache.keys.where(predicate).toList();
    for (final k in keys) {
      _memoryCache.remove(k);
    }
  }

  void clear() {
    _memoryCache.clear();
  }

  Future<void> persistString(String key, String value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(key, value);
  }

  Future<String?> getPersistedString(String key) async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(key);
  }

  Future<void> persistJson(String key, Map<String, dynamic> value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(key, jsonEncode(value));
  }

  Future<Map<String, dynamic>?> getPersistedJson(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final str = prefs.getString(key);
    if (str == null) return null;
    try {
      return jsonDecode(str) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> removePersisted(String key) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(key);
  }
}
