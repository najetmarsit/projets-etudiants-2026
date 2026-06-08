import 'package:flutter/material.dart';

/// Transitions fluides (fade + slide) pour navigation secondaire.
class ClinovaPageRoute<T> extends PageRouteBuilder<T> {
  ClinovaPageRoute({required Widget page, RouteSettings? settings})
      : super(
          settings: settings,
          pageBuilder: (context, animation, secondaryAnimation) => page,
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic);
            return FadeTransition(
              opacity: curved,
              child: SlideTransition(
                position: Tween<Offset>(begin: const Offset(0, 0.04), end: Offset.zero).animate(curved),
                child: child,
              ),
            );
          },
          transitionDuration: const Duration(milliseconds: 280),
        );
}
