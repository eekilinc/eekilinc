// ignore_for_file: avoid_print
import 'dart:typed_data';

import 'package:image/image.dart' as img;
import 'package:optik_mobile/models/scan_result.dart';
import 'package:optik_mobile/omr/omr_processor.dart';

import 'synthetic.dart';

/// Tanı aracı: `dart test/debug_tool.dart` ile koşar, flutter test'e girmez.
Future<void> main() async {
  const proc = OmrProcessor();
  final spec = testSpec();
  final form = syntheticForm(
    studentNo: '1234',
    bookletIndex: 1,
    answers: {
      1: 'A',
      2: 'C',
      3: null,
      4: ['A', 'D'],
    },
  );
  final gray = img.grayscale(form);
  final small = img.copyResize(gray, width: 700);
  final binary = proc.adaptiveThreshold(small, block: 31, c: 12);
  final corners = proc.findCornerMarkers(binary);
  print('corners: ${corners.map((p) => '(${p.x.toStringAsFixed(1)},${p.y.toStringAsFixed(1)})').join(' ')}');

  final bytes = Uint8List.fromList(img.encodePng(form));
  // Adım adım: warp sonrası hane oranları.
  final decoded = img.decodeImage(bytes)!;
  final g = img.grayscale(decoded);
  final s = img.copyResize(g, width: 700);
  final b = proc.adaptiveThreshold(s, block: 31, c: 12);
  final cs = proc.orderCorners(proc.findCornerMarkers(b));
  final scale = g.width / s.width;
  final src = cs.map((p) => Point(p.x * scale, p.y * scale)).toList();
  final dst = spec.anchors
      .map(
        (p) => Point(
          p[0] * OmrGeometry.warpWidth,
          p[1] * OmrGeometry.warpHeight,
        ),
      )
      .toList();
  final warped = proc.warpGray(g, src, dst, OmrGeometry.warpWidth, OmrGeometry.warpHeight);
  final flat = proc.adaptiveThreshold(warped, block: 41, c: 14);
  // ASCII küçük resim: yön/ayna hatasını gözle gör.
  void dump(String label, img.Image image) {
    print('--- $label ---');
    const tw = 50, th = 35;
    for (var ty = 0; ty < th; ty++) {
      final line = StringBuffer();
      for (var tx = 0; tx < tw; tx++) {
        var dark = 0, total = 0;
        for (var y = ty * image.height ~/ th;
            y < (ty + 1) * image.height ~/ th;
            y++) {
          for (var x = tx * image.width ~/ tw;
              x < (tx + 1) * image.width ~/ tw;
              x++) {
            total++;
            final px = image.getPixel(x, y);
            if ((0.299 * px.r + 0.587 * px.g + 0.114 * px.b) < 128) {
              dark++;
            }
          }
        }
        line.write(dark / total > 0.08 ? '#' : '.');
      }
      print(line.toString());
    }
  }

  dump('drawn', form);
  dump('warped', flat);
  final z = spec.studentZone;
  for (var d = 0; d < spec.studentDigits; d++) {
    final ratios = <String>[];
    for (var row = 0; row < 10; row++) {
      final r = proc.fillRatio(
        flat,
        z[0] + (z[2] - z[0]) * d / spec.studentDigits,
        z[1] + (z[3] - z[1]) * row / 10,
        (z[2] - z[0]) / spec.studentDigits,
        (z[3] - z[1]) / 10,
      );
      ratios.add(r.toStringAsFixed(2));
    }
    print('digit $d: ${ratios.join(' ')}');
  }
}
