import 'dart:math' as math;

import 'package:image/image.dart' as img;
import 'package:optik_mobile/models/exam.dart';
import 'package:optik_mobile/models/scan_result.dart';

FormSpec testSpec() => dynamicSpec(
      questionCount: 4,
      optionCount: 4,
      studentDigits: 4,
      bookletChoices: const ['A', 'B'],
      columns: 2,
    );

FormSpec dynamicSpec({
  int questionCount = 40,
  int optionCount = 5,
  int studentDigits = 8,
  List<String> bookletChoices = const ['A', 'B', 'C', 'D'],
  int columns = 2,
}) {
  final rows = (questionCount / columns).ceil();
  return FormSpec(
    questionCount: questionCount,
    optionCount: optionCount,
    options: ['A', 'B', 'C', 'D', 'E'].sublist(0, optionCount),
    studentDigits: studentDigits,
    bookletChoices: bookletChoices,
    fillThreshold: 0.25,
    studentZone: const [0.06, 0.17, 0.58, 0.30],
    bookletZone: const [0.62, 0.155, 0.94, 0.215],
    questionsZone: const [0.06, 0.33, 0.94, 0.94],
    questionsColumns: columns,
    questionsRowsPerColumn: rows,
    anchors: const [
      [0.04, 0.04],
      [0.96, 0.04],
      [0.96, 0.96],
      [0.04, 0.96],
    ],
  );
}

/// Üretim kodundaki ROI geometrisiyle aynı formülle sentetik form çizer.
img.Image syntheticForm({
  FormSpec? spec,
  required String studentNo,
  required int bookletIndex,
  required Map<int, Object?> answers,
}) {
  final s = spec ?? testSpec();
  const w = OmrGeometry.warpWidth;
  const h = OmrGeometry.warpHeight;
  final form = img.Image(width: w, height: h);
  img.fill(form, color: img.ColorRgb8(255, 255, 255));
  final black = img.ColorRgb8(0, 0, 0);

  // Köşe markerlar (45px kare).
  for (final c in s.anchors) {
    final cx = (c[0] * w).round();
    final cy = (c[1] * h).round();
    img.fillRect(
      form,
      x1: cx - 22,
      y1: cy - 22,
      x2: cx + 22,
      y2: cy + 22,
      color: black,
    );
  }

  void fillCircle(double cx, double cy, double radiusNorm) {
    final px = (cx * w).round();
    final py = (cy * h).round();
    final r = (radiusNorm * w).round();
    img.fillCircle(
      form,
      x: px,
      y: py,
      radius: r,
      color: black,
    );
  }

  // Öğrenci no: digits x 10 satır.
  final sz = s.studentZone;
  final sColW = (sz[2] - sz[0]) / s.studentDigits;
  final sRowH = (sz[3] - sz[1]) / 10;
  final sRadius = math.min(sColW, sRowH) * 0.35;
  for (var d = 0; d < s.studentDigits && d < studentNo.length; d++) {
    final row = int.parse(studentNo[d]);
    final cx = sz[0] + sColW * (d + 0.5);
    final cy = sz[1] + sRowH * (row + 0.5);
    fillCircle(cx, cy, sRadius);
  }

  // Kitapçık.
  final bz = s.bookletZone;
  final bColW = (bz[2] - bz[0]) / s.bookletChoices.length;
  final bRowH = bz[3] - bz[1];
  final bRadius = math.min(bColW, bRowH) * 0.35;
  final bcx = bz[0] + bColW * (bookletIndex + 0.5);
  final bcy = bz[1] + bRowH * 0.5;
  fillCircle(bcx, bcy, bRadius);

  // Sorular.
  final qz = s.questionsZone;
  final cols = s.questionsColumns;
  final rows = s.questionsRowsPerColumn;
  final qCellW = (qz[2] - qz[0]) / cols;
  final qCellH = (qz[3] - qz[1]) / rows;

  answers.forEach((q, mark) {
    final marks = mark == null
        ? <String>[]
        : (mark is String ? [mark] : (mark as List).cast<String>());
    final col = (q - 1) ~/ rows;
    final row = (q - 1) % rows;
    final cellX = qz[0] + qCellW * col;
    final cellY = qz[1] + qCellH * row;
    final bubblesX = cellX + qCellW * 0.18;
    final sliceW = (qCellW * 0.82) / s.optionCount;
    final qRadius = math.min(sliceW, qCellH) * 0.35;

    for (final m in marks) {
      final o = s.options.indexOf(m);
      if (o < 0) continue;
      final cx = bubblesX + sliceW * (o + 0.5);
      final cy = cellY + qCellH * 0.5;
      fillCircle(cx, cy, qRadius);
    }
  });

  return form;
}
