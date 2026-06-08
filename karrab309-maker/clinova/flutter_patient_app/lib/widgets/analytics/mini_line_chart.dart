import 'dart:math' as math;
import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

/// Courbe légère (CustomPainter) — pas de dépendance chart externe.
class MiniLineChart extends StatelessWidget {
  const MiniLineChart({
    super.key,
    required this.values,
    this.labels,
    this.height = 120,
    this.lineColor,
    this.fillGradient,
    this.showDots = true,
  });

  final List<double> values;
  final List<String>? labels;
  final double height;
  final Color? lineColor;
  final List<Color>? fillGradient;
  final bool showDots;

  @override
  Widget build(BuildContext context) {
    final color = lineColor ?? AppTheme.primary;
    return SizedBox(
      height: height,
      width: double.infinity,
      child: CustomPaint(
        painter: _MiniLinePainter(
          values: values,
          lineColor: color,
          fillColors: fillGradient ?? [color.withValues(alpha: 0.28), color.withValues(alpha: 0.02)],
          showDots: showDots,
        ),
      ),
    );
  }
}

class _MiniLinePainter extends CustomPainter {
  _MiniLinePainter({
    required this.values,
    required this.lineColor,
    required this.fillColors,
    required this.showDots,
  });

  final List<double> values;
  final Color lineColor;
  final List<Color> fillColors;
  final bool showDots;

  @override
  void paint(Canvas canvas, Size size) {
    if (values.isEmpty) return;
    final maxV = values.reduce(math.max);
    final minV = values.reduce(math.min);
    final range = (maxV - minV).abs() < 0.001 ? 1.0 : (maxV - minV);
    final pad = 8.0;
    final w = size.width - pad * 2;
    final h = size.height - pad * 2;

    final points = <Offset>[];
    for (var i = 0; i < values.length; i++) {
      final x = pad + (values.length == 1 ? w / 2 : w * i / (values.length - 1));
      final y = pad + h - ((values[i] - minV) / range) * h;
      points.add(Offset(x, y));
    }

    if (points.length >= 2) {
      final path = Path()..moveTo(points.first.dx, size.height - pad);
      for (final p in points) {
        path.lineTo(p.dx, p.dy);
      }
      path.lineTo(points.last.dx, size.height - pad);
      path.close();
      final fillPaint = Paint()
        ..shader = LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: fillColors,
        ).createShader(Rect.fromLTWH(0, 0, size.width, size.height));
      canvas.drawPath(path, fillPaint);
    }

    final linePaint = Paint()
      ..color = lineColor
      ..strokeWidth = 2.5
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    if (points.length == 1) {
      if (showDots) {
        canvas.drawCircle(points.first, 4, Paint()..color = lineColor);
      }
      return;
    }

    final linePath = Path();
    for (var i = 0; i < points.length; i++) {
      final p = points[i];
      if (i == 0) {
        linePath.moveTo(p.dx, p.dy);
      } else {
        final prev = points[i - 1];
        final cpX = (prev.dx + p.dx) / 2;
        linePath.cubicTo(cpX, prev.dy, cpX, p.dy, p.dx, p.dy);
      }
    }
    canvas.drawPath(linePath, linePaint);

    if (showDots) {
      final dotPaint = Paint()..color = lineColor;
      for (final p in points) {
        canvas.drawCircle(p, 3.5, dotPaint);
        canvas.drawCircle(p, 2, Paint()..color = Colors.white);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _MiniLinePainter old) => old.values != values;
}
