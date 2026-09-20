import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/scan_queue.dart';
import 'exam_list_page.dart';

class ReviewArgs {
  ReviewArgs({
    required this.examId,
    required this.expectedBooklet,
    required this.booklet,
    required this.studentNo,
    required this.answers,
    required this.confidence,
    required this.flagged,
    this.qrPayload,
    this.paperImagePath,
  });

  final int examId;
  final String expectedBooklet;
  final String booklet;
  final String studentNo;
  final Map<int, Object?> answers;
  final int confidence;
  final List<int> flagged;
  final String? qrPayload;
  final String? paperImagePath;
}

/// OMR sonucunu onayla/düzelt ve kaydet. Bağlantı yoksa kuyruğa atar.
class ReviewPage extends StatefulWidget {
  const ReviewPage({super.key, required this.args});

  static const route = '/review';

  final ReviewArgs args;

  @override
  State<ReviewPage> createState() => _ReviewPageState();
}

class _ReviewPageState extends State<ReviewPage> {
  late final TextEditingController _studentNo;
  late String _booklet;
  bool _busy = false;
  String? _message;

  @override
  void initState() {
    super.initState();
    _studentNo = TextEditingController(text: widget.args.studentNo);
    _booklet = widget.args.booklet;
  }

  String _answerText(Object? value) => switch (value) {
        null => 'Boş',
        String s => s,
        List list => list.join('+'),
        _ => '?',
      };

  Future<void> _save() async {
    if (_studentNo.text.trim().isEmpty) {
      setState(() => _message = 'Öğrenci numarası gerekli.');
      return;
    }
    setState(() {
      _busy = true;
      _message = null;
    });
    final api = context.read<ApiClient>();
    final encoded = <String, dynamic>{
      for (final e in widget.args.answers.entries) '${e.key}': e.value,
    };
    try {
      final data = await api.postScan(
        examId: widget.args.examId,
        booklet: _booklet,
        studentNo: _studentNo.text.trim(),
        answers: encoded,
        confidence: widget.args.confidence,
        deviceId: 'flutter-mobil',
        qrPayload: widget.args.qrPayload,
        paperImagePath: widget.args.paperImagePath,
      );
      if (!mounted) {
        return;
      }
      setState(
        () => _message =
            'Kaydedildi: ${data['score']} / ${data['max_score']} (${data['status']})',
      );
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout ||
          e.response == null) {
        await context.read<ScanQueue>().enqueue({
          'exam_id': widget.args.examId,
          'booklet': _booklet,
          'student_no': _studentNo.text.trim(),
          'answers': encoded,
          'confidence': widget.args.confidence,
          'device_id': 'flutter-mobil',
          'qr_payload': widget.args.qrPayload,
          'paper_image_path': widget.args.paperImagePath,
        });
        if (mounted) {
          setState(
            () => _message = 'Bağlantı yok. Kuyruğa alındı, sonra gönderilecek.',
          );
        }
      } else {
        setState(
          () => _message =
              (e.response?.data?['message'] as String?) ?? 'Gönderilemedi.',
        );
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final args = widget.args;
    return Scaffold(
      appBar: AppBar(title: const Text('Sonucu Onayla')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_booklet != args.expectedBooklet)
            Container(
              padding: const EdgeInsets.all(8),
              color: Colors.orange.shade100,
              child: Text(
                'Uyarı: formda $_booklet okundu, ${args.expectedBooklet} bekleniyordu.',
              ),
            ),
          TextField(
            controller: _studentNo,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(labelText: 'Öğrenci No'),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            initialValue: _booklet,
            decoration: const InputDecoration(labelText: 'Kitapçık'),
            items: ['A', 'B', 'C', 'D']
                .map((b) => DropdownMenuItem(value: b, child: Text(b)))
                .toList(),
            onChanged: (v) => setState(() => _booklet = v ?? _booklet),
          ),
          const SizedBox(height: 8),
          Text(
            'Güven: %${args.confidence}'
            '${args.flagged.isNotEmpty ? ' • İncelenecek: ${args.flagged.join(', ')}' : ''}',
          ),
          const Divider(),
          for (final e in args.answers.entries)
            ListTile(
              dense: true,
              leading: Text(
                '${e.key}',
                style: const TextStyle(fontFamily: 'monospace'),
              ),
              title: Text(_answerText(e.value)),
              tileColor: args.flagged.contains(e.key)
                  ? Colors.yellow.shade100
                  : null,
            ),
          if (_message != null) ...[
            const SizedBox(height: 8),
            Text(_message!),
          ],
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _busy ? null : _save,
            child: Text(_busy ? 'Gönderiliyor...' : 'Kaydet'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pushNamedAndRemoveUntil(
              ExamListPage.route,
              (_) => false,
            ),
            child: const Text('Sınav listesine dön'),
          ),
        ],
      ),
    );
  }
}
