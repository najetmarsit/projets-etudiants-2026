import 'package:flutter/material.dart';

/// Monte l’onglet uniquement après la première sélection (lazy load réel).
class LazyIndexedTab extends StatefulWidget {
  final int index;
  final int currentIndex;
  final Widget child;

  const LazyIndexedTab({
    super.key,
    required this.index,
    required this.currentIndex,
    required this.child,
  });

  @override
  State<LazyIndexedTab> createState() => _LazyIndexedTabState();
}

class _LazyIndexedTabState extends State<LazyIndexedTab> {
  bool _activated = false;

  @override
  void didUpdateWidget(covariant LazyIndexedTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!_activated && widget.index == widget.currentIndex) {
      _activated = true;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_activated && widget.index != widget.currentIndex) {
      return const SizedBox.shrink();
    }
    if (!_activated) {
      _activated = true;
    }
    return widget.child;
  }
}
