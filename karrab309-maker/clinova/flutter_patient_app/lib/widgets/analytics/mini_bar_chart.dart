import 'dart:math' as math;
import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

class MiniBarChart extends StatelessWidget {
  const MiniBarChart({
    super.key,
    required this.values,
    this.labels,
    this.height = 100,
    this.barColor,
  });

  final List<double> values;
  final List<String>? labels;
  final double height;
  final Color? barColor;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: height,
      width: double.infinity,
      child: CustomPaint(
        painter: _MiniBarPainter(values: values, barColor: barColor ?? AppTheme.violet),
      ),
    );
  }
}

class _MiniBarPainter extends CustomPainter {
  _MiniBarPainter({required this.values, required this.barColor});

  final List<double> values;
  final Color barColor;

  @override
  void paint(Canvas canvas, Size size) {
    if (values.isEmpty) return;
    final maxV = values.reduce(math.max);
    final pad = 6.0;
    final barW = (size.width - pad * 2) / values.length * 0.55;
    final gap = (size.width - pad * 2) / values.length;

    for (var i = 0; i < values.length; i++) {
      final v = values[i];
      final h = maxV > 0 ? (v / maxV) * (size.height - pad * 2) : 0.0;
      final x = pad + gap * i + (gap - barW) / 2;
      final y = size.height - pad - h;
      final r = RRect.fromRectAndRadius(
        Rect.fromLTWH(x, y, barW, h),
        const Radius.circular(6),
      );
      final paint = Paint()
        ..shader = LinearGradient(
          begin: Alignment.bottomCenter,
          end: Alignment.topCenter,
          colors: [barColor.withValues(alpha: 0.55), barColor],
        ).createShader(Rect.fromLTWH(x, y, barW, h));
      canvas.drawRRect(r, paint);
    }
  }

  @override
  bool shouldRepaint(covariant _MiniBarPainter old) => old.values != values;
}
