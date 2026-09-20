import 'dart:typed_data';

/// Cihazda yapılan OMR sonucu: POST /api/scans gövdesine dönüşür.
class OmrResult {
  OmrResult({
    required this.studentNo,
    required this.booklet,
    required this.answers,
    required this.confidence,
    required this.flagged,
  });

  /// İşaret -> API değeri: harf, null (boş) ya da liste (çoklu işaret).
  final String? studentNo;
  final String? booklet;
  final Map<int, Object?> answers;
  final int confidence;
  final List<int> flagged;

  Map<String, dynamic> toScanPayload({
    required int examId,
    required String deviceId,
    String? qrPayload,
  }) {
    final encoded = <String, dynamic>{};
    for (final entry in answers.entries) {
      encoded['${entry.key}'] = entry.value;
    }
    return {
      'exam_id': examId,
      'booklet': booklet,
      'student_no': studentNo,
      'answers': encoded,
      'confidence': confidence,
      'device_id': deviceId,
      if (qrPayload != null) 'qr_payload': qrPayload,
    };
  }
}

/// Sabit A4 kanonik boyut: oran 1:1.4142.
class OmrGeometry {
  static const int warpWidth = 1000;
  static const int warpHeight = 1414;
}

/// Ham yakalama çıktısı: işlenmiş sonuç + saklanacak fotoğraf baytı.
class CaptureOutput {
  CaptureOutput({required this.result, required this.jpeg});

  final OmrResult result;
  final Uint8List jpeg;
}
