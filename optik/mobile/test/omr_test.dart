import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:image/image.dart' as img;
import 'package:optik_mobile/omr/omr_processor.dart';

import 'synthetic.dart';

void main() {
  test('köşe sıralaması saat yönüne dizer', () {
    const proc = OmrProcessor();
    final ordered = proc.orderCorners(const [
      Point(900, 1300),
      Point(100, 100),
      Point(900, 100),
      Point(100, 1300),
    ]);
    expect([ordered[0].x, ordered[0].y], [100, 100]);
    expect([ordered[1].x, ordered[1].y], [900, 100]);
    expect([ordered[2].x, ordered[2].y], [900, 1300]);
    expect([ordered[3].x, ordered[3].y], [100, 1300]);
  });

  test('sentetik 4 soruluk formu tam okur', () {
    const proc = OmrProcessor();
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
    final bytes = Uint8List.fromList(img.encodePng(form));
    final out = proc.process(bytes, testSpec());

    expect(out.result.studentNo, '1234');
    expect(out.result.booklet, 'B');
    expect(out.result.answers[1], 'A');
    expect(out.result.answers[2], 'C');
    expect(out.result.answers[3], isNull);
    expect(out.result.answers[4], ['A', 'D']);
    expect(out.result.flagged, [4]);
    expect(out.result.confidence, greaterThan(50));
  });

  test('dinamik 40 soru x 5 şık, 8 hane öğrenci no ve kitapçık tam okur', () {
    const proc = OmrProcessor();
    final spec = dynamicSpec(
      questionCount: 40,
      optionCount: 5,
      studentDigits: 8,
      bookletChoices: ['A', 'B', 'C', 'D'],
      columns: 2,
    );

    final form = syntheticForm(
      spec: spec,
      studentNo: '20240137',
      bookletIndex: 2, // 'C'
      answers: {
        1: 'A',
        20: 'E',
        21: 'B',
        40: 'D',
        15: ['B', 'C'], // multi-mark
      },
    );
    final bytes = Uint8List.fromList(img.encodePng(form));
    final out = proc.process(bytes, spec);

    expect(out.result.studentNo, '20240137');
    expect(out.result.booklet, 'C');
    expect(out.result.answers[1], 'A');
    expect(out.result.answers[20], 'E');
    expect(out.result.answers[21], 'B');
    expect(out.result.answers[40], 'D');
    expect(out.result.answers[15], ['B', 'C']);
    expect(out.result.flagged, [15]);
  });

  test('dinamik 25 soru (tek sayı) x 4 şık tam okur', () {
    const proc = OmrProcessor();
    final spec = dynamicSpec(
      questionCount: 25,
      optionCount: 4,
      studentDigits: 8,
      bookletChoices: ['A', 'B'],
      columns: 2,
    );

    final form = syntheticForm(
      spec: spec,
      studentNo: '98765432',
      bookletIndex: 0, // 'A'
      answers: {
        1: 'D',
        13: 'B',
        14: 'A',
        25: 'C',
      },
    );
    final bytes = Uint8List.fromList(img.encodePng(form));
    final out = proc.process(bytes, spec);

    expect(out.result.studentNo, '98765432');
    expect(out.result.booklet, 'A');
    expect(out.result.answers[1], 'D');
    expect(out.result.answers[13], 'B');
    expect(out.result.answers[14], 'A');
    expect(out.result.answers[25], 'C');
    expect(out.result.answers[10], isNull);
  });
}
