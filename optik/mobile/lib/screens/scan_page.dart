import 'package:camera/camera.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_mlkit_barcode_scanning/google_mlkit_barcode_scanning.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../models/exam.dart';
import '../models/scan_result.dart';
import '../omr/omr_processor.dart';
import 'review_page.dart';

/// Canlı kamera: QR ile formu doğrula, 4 köşe kılavuzuyla hizala ve çek.
class ScanPage extends StatefulWidget {
  const ScanPage({super.key, required this.examId, required this.booklet});

  static const route = '/scan';

  final int examId;
  final String booklet;

  @override
  State<ScanPage> createState() => _ScanPageState();
}

class _ScanPageState extends State<ScanPage> {
  CameraController? _camera;
  final _scanner = BarcodeScanner();
  ExamDetail? _exam;
  String? _error;
  bool _busy = false;
  String? _qrRaw;
  bool _qrOk = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final api = context.read<ApiClient>();
    try {
      final detail = ExamDetail.fromJson(
        await api.examDetail(widget.examId),
      );
      final cameras = await availableCameras();
      final back = cameras.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.back,
        orElse: () => cameras.first,
      );
      final controller = CameraController(
        back,
        ResolutionPreset.high,
        enableAudio: false,
      );
      await controller.initialize();
      await controller.startImageStream(_onFrame);
      setState(() => _exam = detail);
      _camera = controller;
    } catch (e) {
      setState(() => _error = 'Kamera açılamadı: $e');
    }
  }

  Future<void> _onFrame(CameraImage image) async {
    if (_qrOk || _busy || !mounted) {
      return;
    }
    try {
      final input = _toInputImage(image);
      if (input == null) {
        return;
      }
      final codes = await _scanner.processImage(input);
      for (final code in codes) {
        final target = QrTarget.tryParse(code.rawValue ?? '');
        if (target == null) {
          continue;
        }
        setState(() => _qrRaw = target.raw);
        if (target.examId == widget.examId &&
            target.booklet == widget.booklet) {
          if (_exam != null && target.version != _exam!.formVersion) {
            setState(
              () => _error = 'Form eski (v${target.version}). Güncel formu yazdırın.',
            );
            return;
          }
          setState(() => _qrOk = true);
          await _camera?.stopImageStream();
        } else {
          setState(
            () => _error = 'Bu QR başka sınava/kitapçığa ait.',
          );
        }
        return;
      }
    } catch (_) {
      // Kare atlanır, akış sürer.
    }
  }

  InputImage? _toInputImage(CameraImage image) {
    final camera = _camera;
    if (camera == null) {
      return null;
    }
    final format = InputImageFormatValue.fromRawValue(image.format.raw);
    if (format == null) {
      return null;
    }
    final plane = image.planes.first;
    return InputImage.fromBytes(
      bytes: plane.bytes,
      metadata: InputImageMetadata(
        size: Size(image.width.toDouble(), image.height.toDouble()),
        rotation: InputImageRotationValue.fromRawValue(
              camera.description.sensorOrientation,
            ) ??
            InputImageRotation.rotation0deg,
        format: format,
        bytesPerRow: plane.bytesPerRow,
      ),
    );
  }

  Future<void> _capture() async {
    final camera = _camera;
    final exam = _exam;
    if (camera == null || exam == null || _busy) {
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final file = await camera.takePicture();
      final bytes = await file.readAsBytes();
      final output = await compute(
        _runOmr,
        _OmrInput(bytes: bytes, spec: exam.formSpec),
      );
      if (!mounted) {
        return;
      }
      // Fotoğrafı geçici dosyaya yaz (gönderimde eklenir).
      final path = '${file.path}.paper.jpg';
      await XFile.fromData(output.jpeg).saveTo(path);
      if (!mounted) {
        return;
      }
      Navigator.of(context).pushReplacementNamed(
        ReviewPage.route,
        arguments: ReviewArgs(
          examId: widget.examId,
          expectedBooklet: widget.booklet,
          booklet: output.result.booklet ?? widget.booklet,
          studentNo: output.result.studentNo ?? '',
          answers: output.result.answers,
          confidence: output.result.confidence,
          flagged: output.result.flagged,
          qrPayload: _qrRaw,
          paperImagePath: path,
        ),
      );
    } catch (e) {
      setState(() => _error = 'Okuma başarısız: $e');
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  void dispose() {
    _camera?.dispose();
    _scanner.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final camera = _camera;
    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.booklet} kitapçığı tara'),
      ),
      body: _error != null && camera == null
          ? Center(child: Text(_error!))
          : camera == null || !camera.value.isInitialized
              ? const Center(child: CircularProgressIndicator())
              : Stack(
                  fit: StackFit.expand,
                  children: [
                    CameraPreview(camera),
                    CustomPaint(painter: _GuidePainter()),
                    Positioned(
                      top: 12,
                      left: 12,
                      right: 12,
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        color: Colors.black54,
                        child: Text(
                          _qrRaw == null
                              ? 'QR kodu kadraja getirin'
                              : _qrOk
                                  ? 'Form doğrulandı. Köşeleri kılavuza hizalayıp çekin.'
                                  : 'QR okundu, bu forma ait değil.',
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                    ),
                    if (_error != null)
                      Positioned(
                        bottom: 100,
                        left: 12,
                        right: 12,
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          color: Colors.red.shade700,
                          child: Text(
                            _error!,
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
                      ),
                    Positioned(
                      bottom: 24,
                      left: 0,
                      right: 0,
                      child: Center(
                        child: FloatingActionButton.large(
                          onPressed: _busy ? null : _capture,
                          child: _busy
                              ? const CircularProgressIndicator(
                                  color: Colors.white,
                                )
                              : const Icon(Icons.photo_camera),
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }
}

class _OmrInput {
  _OmrInput({required this.bytes, required this.spec});

  final Uint8List bytes;
  final FormSpec spec;
}

CaptureOutput _runOmr(_OmrInput input) =>
    const OmrProcessor().process(input.bytes, input.spec);

/// 4 köşe hizalama kılavuzu.
class _GuidePainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.greenAccent
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    const inset = 24.0;
    const len = 48.0;
    void corner(double x, double y, double dx, double dy) {
      canvas.drawLine(Offset(x, y), Offset(x + dx * len, y), paint);
      canvas.drawLine(Offset(x, y), Offset(x, y + dy * len), paint);
    }

    corner(inset, inset, 1, 1);
    corner(size.width - inset, inset, -1, 1);
    corner(inset, size.height - inset, 1, -1);
    corner(size.width - inset, size.height - inset, -1, -1);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
