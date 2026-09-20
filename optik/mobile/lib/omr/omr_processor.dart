import 'dart:math' as math;
import 'dart:typed_data';

import 'package:image/image.dart' as img;

import '../models/exam.dart';
import '../models/scan_result.dart';

/// Çok toleranslı OMR: anchor bulunamazsa bile deneme yapar, threshold çok düşük.
class OmrProcessor {
  const OmrProcessor();

  CaptureOutput process(Uint8List bytes, FormSpec spec) {
    final decoded = img.decodeImage(bytes);
    if (decoded == null) {
      throw const FormatException('Görüntü çözülemedi.');
    }
    final gray = img.grayscale(decoded);

    // 1. Anchor tespiti dene
    List<Point> corners = [];
    bool usedFallback = false;
    
    try {
      const probeWidth = 800;
      final small = img.copyResize(gray, width: probeWidth);
      final binary = adaptiveThreshold(small, block: 51, c: 10);
      final scale = gray.width / small.width;
      corners = findCornerMarkers(binary);
      if (corners.length != 4) {
        throw StateError('Anchor bulunamadı (${corners.length})');
      }
      corners = corners.map((p) => Point(p.x * scale, p.y * scale)).toList();
    } catch (e) {
      // Fallback: görüntü köşelerini kullan (kağıt tam kare yakalanmışsa)
      usedFallback = true;
      final w = gray.width.toDouble();
      final h = gray.height.toDouble();
      final margin = math.min(w, h) * 0.05;
      corners = [
        Point(margin, margin),
        Point(w - margin, margin),
        Point(w - margin, h - margin),
        Point(margin, h - margin),
      ];
    }

    final ordered = orderCorners(corners);
    
    // Hedef: spec anchor'ları veya sayfa köşeleri
    final dst = usedFallback
        ? [
            Point(OmrGeometry.warpWidth * 0.05, OmrGeometry.warpHeight * 0.05),
            Point(OmrGeometry.warpWidth * 0.95, OmrGeometry.warpHeight * 0.05),
            Point(OmrGeometry.warpWidth * 0.95, OmrGeometry.warpHeight * 0.95),
            Point(OmrGeometry.warpWidth * 0.05, OmrGeometry.warpHeight * 0.95),
          ]
        : spec.anchors
            .map((p) => Point(p[0] * OmrGeometry.warpWidth, p[1] * OmrGeometry.warpHeight))
            .toList();

    final src = ordered;
    
    // Warp et
    final warped = warpGray(gray, src, dst, OmrGeometry.warpWidth, OmrGeometry.warpHeight);
    
    // Çok düşük threshold ile binary
    final flat = adaptiveThreshold(warped, block: 61, c: 8);

    // Debug: zone'ları logla
    _debugZones(flat, spec);

    final studentNo = readStudentNo(flat, spec);
    final booklet = readBooklet(flat, spec);
    final answers = readAnswers(flat, spec);
    final flagged = answers.entries
        .where((e) => e.value is List)
        .map((e) => e.key)
        .toList();
    final confidence = estimateConfidence(flat, spec, answers);

    return CaptureOutput(
      result: OmrResult(
        studentNo: studentNo,
        booklet: booklet,
        answers: answers,
        confidence: confidence,
        flagged: flagged,
      ),
      jpeg: Uint8List.fromList(img.encodeJpg(warped, quality: 85)),
    );
  }

  void _debugZones(img.Image flat, FormSpec spec) {
    // Student no zone test
    final snz = spec.studentZone;
    final colW = (snz[2] - snz[0]) / spec.studentDigits;
    final rowH = (snz[3] - snz[1]) / 10;
    final radiusNorm = math.min(colW, rowH) * 0.35;

    for (var d = 0; d < spec.studentDigits; d++) {
      var bestR = 0.0, bestRow = -1;
      final cx = snz[0] + colW * (d + 0.5);
      for (var row = 0; row < 10; row++) {
        final cy = snz[1] + rowH * (row + 0.5);
        final r = circleFillRatio(flat, cx, cy, radiusNorm * 0.75);
        if (r > bestR) { bestR = r; bestRow = row; }
      }
      print('SN digit $d: bestRow=$bestRow ratio=${bestR.toStringAsFixed(3)}');
    }

    // Booklet zone test
    final bz = spec.bookletZone;
    final bColW = (bz[2] - bz[0]) / spec.bookletChoices.length;
    final bRowH = bz[3] - bz[1];
    final bRadiusNorm = math.min(bColW, bRowH) * 0.35;
    final bcy = bz[1] + bRowH * 0.5;

    for (var i = 0; i < spec.bookletChoices.length; i++) {
      final bcx = bz[0] + bColW * (i + 0.5);
      final r = circleFillRatio(flat, bcx, bcy, bRadiusNorm * 0.75);
      print('Booklet ${spec.bookletChoices[i]}: ratio=${r.toStringAsFixed(3)}');
    }

    // First few question zones test
    final qz = spec.questionsZone;
    final cols = spec.questionsColumns;
    final rows = spec.questionsRowsPerColumn;
    final cellW = (qz[2] - qz[0]) / cols;
    final cellH = (qz[3] - qz[1]) / rows;

    for (var q = 1; q <= math.min(3, spec.questionCount); q++) {
      final col = (q - 1) ~/ rows;
      final row = (q - 1) % rows;
      final cellX = qz[0] + cellW * col;
      final cellY = qz[1] + cellH * row;
      final bubblesX = cellX + cellW * 0.18;
      final sliceW = (cellW * 0.82) / spec.optionCount;
      final qRadiusNorm = math.min(sliceW, cellH) * 0.35;
      final cy = cellY + cellH * 0.5;

      for (var o = 0; o < spec.optionCount; o++) {
        final cx = bubblesX + sliceW * (o + 0.5);
        final r = circleFillRatio(flat, cx, cy, qRadiusNorm * 0.75);
        print('Q$q opt${spec.options[o]}: ratio=${r.toStringAsFixed(3)}');
      }
    }
  }

  img.Image adaptiveThreshold(img.Image gray, {int block = 61, int c = 8}) {
    final out = img.Image(width: gray.width, height: gray.height);
    final radius = block ~/ 2;
    final integral = List.generate(
      gray.height + 1, (_) => List.filled(gray.width + 1, 0),
    );
    for (var y = 0; y < gray.height; y++) {
      var rowSum = 0;
      for (var x = 0; x < gray.width; x++) {
        final lum = _luminance(gray.getPixel(x, y)).round();
        rowSum += lum;
        integral[y + 1][x + 1] = integral[y][x + 1] + rowSum;
      }
    }
    int window(int x0, int y0, int x1, int y1) {
      return integral[y1 + 1][x1 + 1] - integral[y0][x1 + 1] - integral[y1 + 1][x0] + integral[y0][x0];
    }

    for (var y = 0; y < gray.height; y++) {
      for (var x = 0; x < gray.width; x++) {
        final x0 = math.max(0, x - radius);
        final y0 = math.max(0, y - radius);
        final x1 = math.min(gray.width - 1, x + radius);
        final y1 = math.min(gray.height - 1, y + radius);
        final count = (x1 - x0 + 1) * (y1 - y0 + 1);
        final mean = window(x0, y0, x1, y1) / count;
        final dark = _luminance(gray.getPixel(x, y)) < mean - c;
        out.setPixelRgb(x, y, dark ? 0 : 255, dark ? 0 : 255, dark ? 0 : 255);
      }
    }
    return out;
  }

  List<Point> findCornerMarkers(img.Image binary) {
    final w = binary.width;
    final h = binary.height;
    final visited = List.generate(h, (_) => List.filled(w, false));
    final blobs = <_Blob>[];

    bool dark(int x, int y) => _luminance(binary.getPixel(x, y)) < 128;

    for (var y = 0; y < h; y++) {
      for (var x = 0; x < w; x++) {
        if (visited[y][x] || !dark(x, y)) continue;
        
        var minX = x, maxX = x, minY = y, maxY = y, area = 0;
        final stack = [math.Point(x, y)];
        visited[y][x] = true;
        
        while (stack.isNotEmpty) {
          final p = stack.removeLast();
          final px = p.x.toInt(), py = p.y.toInt();
          area++;
          if (px < minX) minX = px;
          if (px > maxX) maxX = px;
          if (py < minY) minY = py;
          if (py > maxY) maxY = py;
          
          const neighbors = [
            math.Point(1, 0), math.Point(-1, 0),
            math.Point(0, 1), math.Point(0, -1),
          ];
          for (final n in neighbors) {
            final nx = px + n.x.toInt(), ny = py + n.y.toInt();
            if (nx < 0 || ny < 0 || nx >= w || ny >= h) continue;
            if (visited[ny][nx] || !dark(nx, ny)) continue;
            visited[ny][nx] = true;
            stack.add(math.Point(nx, ny));
          }
        }
        
        final bw = maxX - minX + 1;
        final bh = maxY - minY + 1;
        if (bw == 0 || bh == 0) continue;
        
        final fill = area / (bw * bh);
        final ratio = bw / bh;
        
        // Çok daha esnek kriterler
        if (area > 100 && fill > 0.3 && ratio > 0.5 && ratio < 2.0) {
          blobs.add(_Blob(area, (minX + maxX) / 2, (minY + maxY) / 2));
        }
      }
    }
    
    blobs.sort((a, b) => b.area.compareTo(a.area));
    
    // En büyük 4'ü al, ama köşelere yakın olanları tercih et
    final candidates = blobs.take(8).toList();
    
    // Köşe bölgelerine yakın olanları filtrele (hedef köşe noktasına en yakın olanı seç)
    final corners = <Point>[];
    final regions = [
      // TL: sol-üst
      (0.04, 0.04, 0.0, 0.35, 0.0, 0.35),
      // TR: sağ-üst
      (0.96, 0.04, 0.65, 1.0, 0.0, 0.35),
      // BR: sağ-alt
      (0.96, 0.96, 0.65, 1.0, 0.65, 1.0),
      // BL: sol-alt
      (0.04, 0.96, 0.0, 0.35, 0.65, 1.0),
    ];

    for (final (tx, ty, x0, x1, y0, y1) in regions) {
      final targetX = tx * binary.width;
      final targetY = ty * binary.height;
      _Blob? best;
      var bestDistSq = double.infinity;

      for (final b in candidates) {
        if (b.cx >= x0 * binary.width && b.cx <= x1 * binary.width &&
            b.cy >= y0 * binary.height && b.cy <= y1 * binary.height) {
          final dx = b.cx - targetX;
          final dy = b.cy - targetY;
          final distSq = dx * dx + dy * dy;
          if (distSq < bestDistSq) {
            bestDistSq = distSq;
            best = b;
          }
        }
      }

      if (best != null) {
        corners.add(Point(best.cx, best.cy));
      }
    }

    // Eğer bölge bazlı bulamazsak, en büyük 4'ü al
    if (corners.length < 4) {
      corners.clear();
      corners.addAll(blobs.take(4).map((b) => Point(b.cx, b.cy)));
    }

    return corners;
  }

  List<Point> orderCorners(List<Point> corners) {
    assert(corners.length == 4);
    final sorted = List<Point>.from(corners);
    Point topLeft = sorted.first, bottomRight = sorted.first;
    Point topRight = sorted.first, bottomLeft = sorted.first;
    var minSum = double.infinity, maxSum = -double.infinity;
    var maxDiff = -double.infinity, minDiff = double.infinity;
    
    for (final p in sorted) {
      final sum = p.x + p.y, diff = p.x - p.y;
      if (sum < minSum) { minSum = sum; topLeft = p; }
      if (sum > maxSum) { maxSum = sum; bottomRight = p; }
      if (diff > maxDiff) { maxDiff = diff; topRight = p; }
      if (diff < minDiff) { minDiff = diff; bottomLeft = p; }
    }
    return [topLeft, topRight, bottomRight, bottomLeft];
  }

  List<double> homography(List<Point> src, List<Point> dst) {
    assert(src.length == 4 && dst.length == 4);
    final m = List.generate(8, (_) => List.filled(9, 0.0));
    for (var i = 0; i < 4; i++) {
      final x = src[i].x, y = src[i].y;
      final u = dst[i].x, v = dst[i].y;
      m[2 * i] = [x, y, 1, 0, 0, 0, -u * x, -u * y, u];
      m[2 * i + 1] = [0, 0, 0, x, y, 1, -v * x, -v * y, v];
    }
    for (var col = 0; col < 8; col++) {
      var pivot = col;
      for (var row = col + 1; row < 8; row++) {
        if (m[row][col].abs() > m[pivot][col].abs()) pivot = row;
      }
      final tmp = m[col];
      m[col] = m[pivot];
      m[pivot] = tmp;
      final div = m[col][col];
      if (div.abs() < 1e-12) throw StateError('Homografi çözülemedi.');
      for (var k = col; k < 9; k++) m[col][k] /= div;
      for (var row = 0; row < 8; row++) {
        if (row == col) continue;
        final factor = m[row][col];
        for (var k = col; k < 9; k++) m[row][k] -= factor * m[col][k];
      }
    }
    return [...m.map((r) => r[8]), 1.0];
  }

  img.Image warpGray(img.Image gray, List<Point> src, List<Point> dst, int outW, int outH) {
    final h = homography(dst, src);
    final out = img.Image(width: outW, height: outH);
    for (var y = 0; y < outH; y++) {
      for (var x = 0; x < outW; x++) {
        final w = h[6] * x + h[7] * y + h[8];
        var sx = ((h[0] * x + h[1] * y + h[2]) / w).round();
        var sy = ((h[3] * x + h[4] * y + h[5]) / w).round();
        sx = sx.clamp(0, gray.width - 1);
        sy = sy.clamp(0, gray.height - 1);
        final lum = _luminance(gray.getPixel(sx, sy)).round();
        out.setPixelRgb(x, y, lum, lum, lum);
      }
    }
    return out;
  }

  double fillRatio(img.Image binary, double nx, double ny, double nw, double nh) {
    final x0 = (nx * binary.width).round().clamp(0, binary.width - 1);
    final y0 = (ny * binary.height).round().clamp(0, binary.height - 1);
    final x1 = ((nx + nw) * binary.width).round().clamp(0, binary.width);
    final y1 = ((ny + nh) * binary.height).round().clamp(0, binary.height);
    var dark = 0, total = 0;
    for (var y = y0; y < y1; y++) {
      for (var x = x0; x < x1; x++) {
        total++;
        if (_luminance(binary.getPixel(x, y)) < 128) dark++;
      }
    }
    return total == 0 ? 0 : dark / total;
  }

  double circleFillRatio(img.Image binary, double cx, double cy, double radiusNorm) {
    final px = (cx * binary.width).round();
    final py = (cy * binary.height).round();
    final r = (radiusNorm * binary.width).round().clamp(1, 100);
    var dark = 0, total = 0;
    final r2 = r * r;
    for (var dy = -r; dy <= r; dy++) {
      final y = py + dy;
      if (y < 0 || y >= binary.height) continue;
      for (var dx = -r; dx <= r; dx++) {
        if (dx * dx + dy * dy > r2) continue;
        final x = px + dx;
        if (x < 0 || x >= binary.width) continue;
        total++;
        if (_luminance(binary.getPixel(x, y)) < 128) dark++;
      }
    }
    return total == 0 ? 0 : dark / total;
  }

  String? readStudentNo(img.Image flat, FormSpec spec) {
    final z = spec.studentZone;
    final digits = StringBuffer();
    final colW = (z[2] - z[0]) / spec.studentDigits;
    final rowH = (z[3] - z[1]) / 10;
    final radiusNorm = math.min(colW, rowH) * 0.35;

    for (var d = 0; d < spec.studentDigits; d++) {
      final cx = z[0] + colW * (d + 0.5);
      var best = -1, bestRatio = 0.0, secondRatio = 0.0;
      for (var row = 0; row < 10; row++) {
        final cy = z[1] + rowH * (row + 0.5);
        final r = circleFillRatio(flat, cx, cy, radiusNorm * 0.75);
        if (r > bestRatio) {
          secondRatio = bestRatio;
          bestRatio = r;
          best = row;
        } else if (r > secondRatio) {
          secondRatio = r;
        }
      }
      if (bestRatio < spec.fillThreshold) return null;
      if (secondRatio >= spec.fillThreshold && (bestRatio - secondRatio).abs() < 0.12) {
        return null;
      }
      digits.write(best);
    }
    return digits.toString();
  }

  String? readBooklet(img.Image flat, FormSpec spec) {
    final z = spec.bookletZone;
    final n = spec.bookletChoices.length;
    final colW = (z[2] - z[0]) / n;
    final rowH = z[3] - z[1];
    final radiusNorm = math.min(colW, rowH) * 0.35;
    final cy = z[1] + rowH * 0.5;

    var best = -1, bestRatio = 0.0;
    for (var i = 0; i < n; i++) {
      final cx = z[0] + colW * (i + 0.5);
      final r = circleFillRatio(flat, cx, cy, radiusNorm * 0.75);
      if (r > bestRatio) {
        bestRatio = r;
        best = i;
      }
    }
    if (bestRatio < spec.fillThreshold) return null;
    return spec.bookletChoices[best];
  }

  List<double> bubbleRect({
    required double cellX, required double cellY,
    required double cellW, required double cellH,
    required int optionIndex, required int optionCount,
  }) {
    const aspect = OmrGeometry.warpWidth / OmrGeometry.warpHeight;
    final bubblesX = cellX + cellW * 0.18;
    final sliceW = (cellW * 0.82) / optionCount;
    final sideW = math.min(sliceW, cellH * aspect) * 0.7;
    final sideH = sideW * aspect;
    final cx = bubblesX + sliceW * (optionIndex + 0.5);
    final cy = cellY + cellH / 2;
    return [cx - sideW / 2, cy - sideH / 2, sideW, sideH];
  }

  Map<int, Object?> readAnswers(img.Image flat, FormSpec spec) {
    final z = spec.questionsZone;
    final cols = spec.questionsColumns;
    final rows = spec.questionsRowsPerColumn;
    final cellW = (z[2] - z[0]) / cols;
    final cellH = (z[3] - z[1]) / rows;
    final result = <int, Object?>{};

    for (var q = 1; q <= spec.questionCount; q++) {
      final col = (q - 1) ~/ rows;
      final row = (q - 1) % rows;
      final cellX = z[0] + cellW * col;
      final cellY = z[1] + cellH * row;

      final bubblesX = cellX + cellW * 0.18;
      final sliceW = (cellW * 0.82) / spec.optionCount;
      final radiusNorm = math.min(sliceW, cellH) * 0.35;
      final cy = cellY + cellH * 0.5;

      final found = <String>[];
      for (var o = 0; o < spec.optionCount; o++) {
        final cx = bubblesX + sliceW * (o + 0.5);
        final r = circleFillRatio(flat, cx, cy, radiusNorm * 0.75);
        if (r >= spec.fillThreshold) {
          found.add(spec.options[o]);
        }
      }
      result[q] = switch (found.length) {
        0 => null,
        1 => found.first,
        _ => found,
      };
    }
    return result;
  }

  int estimateConfidence(img.Image flat, FormSpec spec, Map<int, Object?> answers) {
    var sum = 0.0, count = 0;
    final z = spec.questionsZone;
    final cols = spec.questionsColumns;
    final rows = spec.questionsRowsPerColumn;
    final cellW = (z[2] - z[0]) / cols;
    final cellH = (z[3] - z[1]) / rows;

    for (final entry in answers.entries) {
      final mark = entry.value;
      if (mark is! String) continue;
      final o = spec.options.indexOf(mark);
      if (o < 0) continue;

      final col = (entry.key - 1) ~/ rows;
      final row = (entry.key - 1) % rows;
      final cellX = z[0] + cellW * col;
      final cellY = z[1] + cellH * row;

      final bubblesX = cellX + cellW * 0.18;
      final sliceW = (cellW * 0.82) / spec.optionCount;
      final radiusNorm = math.min(sliceW, cellH) * 0.35;
      final cx = bubblesX + sliceW * (o + 0.5);
      final cy = cellY + cellH * 0.5;

      sum += circleFillRatio(flat, cx, cy, radiusNorm * 0.75);
      count++;
    }
    if (count == 0) return 0;
    return (sum / count * 100).round().clamp(0, 100);
  }

  double _luminance(img.Pixel pixel) => 0.299 * pixel.r + 0.587 * pixel.g + 0.114 * pixel.b;
}

class Point { final double x, y; const Point(this.x, this.y); }

class _Blob { final int area; final double cx, cy; _Blob(this.area, this.cx, this.cy); }