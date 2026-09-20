import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/auth_state.dart';
import '../core/scan_queue.dart';
import '../models/exam.dart';
import 'login_page.dart';
import 'scan_page.dart';

class ExamListPage extends StatefulWidget {
  const ExamListPage({super.key});

  static const route = '/exams';

  @override
  State<ExamListPage> createState() => _ExamListPageState();
}

class _ExamListPageState extends State<ExamListPage> {
  List<ExamSummary> _exams = [];
  bool _loading = true;
  String? _error;
  int _queued = 0;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    final api = context.read<ApiClient>();
    final auth = context.read<AuthState>();
    final queue = context.read<ScanQueue>();
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await api.exams();
      final queued = await queue.count();
      setState(() {
        _exams = list.map(ExamSummary.fromJson).toList();
        _queued = queued;
      });
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 && mounted) {
        await auth.logout();
        if (mounted) {
          Navigator.of(context).pushReplacementNamed(LoginPage.route);
        }
        return;
      }
      setState(() => _error = 'Liste alınamadı. İnterneti kontrol edin.');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sınavlar'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await context.read<AuthState>().logout();
              if (context.mounted) {
                Navigator.of(context).pushReplacementNamed(LoginPage.route);
              }
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? Center(child: Text(_error!))
                : ListView(
                    children: [
                      if (_queued > 0)
                        ListTile(
                          leading: const Icon(Icons.cloud_upload_outlined),
                          title: Text('$_queued tarama kuyrukta'),
                          subtitle: const Text(
                            'Bağlantı gelince otomatik gönderilecek.',
                          ),
                        ),
                      for (final exam in _exams)
                        ExpansionTile(
                          title: Text(exam.title),
                          subtitle: Text(
                            '${exam.course ?? ''} • ${exam.questionCount} soru • ${exam.booklets.join('/')}',
                          ),
                          children: [
                            for (final booklet in exam.booklets)
                              ListTile(
                                leading: Text(
                                  booklet,
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                title: Text('$booklet kitapçığını tara'),
                                trailing: const Icon(Icons.photo_camera),
                                onTap: () => Navigator.of(context).pushNamed(
                                  ScanPage.route,
                                  arguments: {
                                    'examId': exam.id,
                                    'booklet': booklet,
                                  },
                                ),
                              ),
                          ],
                        ),
                    ],
                  ),
      ),
    );
  }
}
