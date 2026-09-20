import 'dart:convert';
import 'dart:io';

import 'package:path_provider/path_provider.dart';

/// Çevrimdışı kuyruk: gönderilemeyen taramalar JSON dosya olarak saklanır,
/// bağlantı gelince sırayla tekrar denenir.
class ScanQueue {
  Future<Directory> _dir() async {
    final docs = await getApplicationDocumentsDirectory();
    final dir = Directory('${docs.path}/scan_queue');
    if (!await dir.exists()) {
      await dir.create(recursive: true);
    }
    return dir;
  }

  Future<void> enqueue(Map<String, dynamic> payload) async {
    final dir = await _dir();
    final file = File(
      '${dir.path}/${DateTime.now().microsecondsSinceEpoch}.json',
    );
    await file.writeAsString(jsonEncode(payload));
  }

  Future<List<File>> pending() async {
    final dir = await _dir();
    final files = dir
        .listSync()
        .whereType<File>()
        .where((f) => f.path.endsWith('.json'))
        .toList()
      ..sort((a, b) => a.path.compareTo(b.path));
    return files;
  }

  Future<Map<String, dynamic>> read(File file) async =>
      jsonDecode(await file.readAsString()) as Map<String, dynamic>;

  Future<void> remove(File file) async {
    if (await file.exists()) {
      await file.delete();
    }
  }

  Future<int> count() async => (await pending()).length;
}
