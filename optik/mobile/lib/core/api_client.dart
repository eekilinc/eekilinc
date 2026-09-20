import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'config.dart';

/// Kimlik doğrulamalı API istemcisi. Token güvenli depoda tutulur.
class ApiClient {
  ApiClient({Dio? dio, FlutterSecureStorage? storage})
      : _dio = dio ?? Dio(BaseOptions(baseUrl: apiBaseUrl)),
        _storage = storage ?? const FlutterSecureStorage();

  final Dio _dio;
  final FlutterSecureStorage _storage;

  static const _tokenKey = 'optik_token';

  Future<String?> getToken() => _storage.read(key: _tokenKey);

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  Options _authed(String token) =>
      Options(headers: {'Authorization': 'Bearer $token'});

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/login',
      data: {'email': email, 'password': password, 'device_name': deviceName},
    );
    final token = res.data!['token'] as String;
    await saveToken(token);
    return Map<String, dynamic>.from(res.data!['user'] as Map);
  }

  Future<void> logout() async {
    final token = await getToken();
    if (token != null) {
      try {
        await _dio.post('/logout', options: _authed(token));
      } finally {
        await clearToken();
      }
    }
  }

  Future<List<Map<String, dynamic>>> exams() async {
    final token = await getToken();
    final res = await _dio.get<Map<String, dynamic>>(
      '/exams',
      options: _authed(token ?? ''),
    );
    return (res.data!['data'] as List).cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> examDetail(int id) async {
    final token = await getToken();
    final res = await _dio.get<Map<String, dynamic>>(
      '/exams/$id',
      options: _authed(token ?? ''),
    );
    return Map<String, dynamic>.from(res.data!['data'] as Map);
  }

  /// Tarama sonucunu fotoğrafla birlikte gönderir.
  Future<Map<String, dynamic>> postScan({
    required int examId,
    required String booklet,
    required String studentNo,
    required Map<String, dynamic> answers,
    required int confidence,
    required String deviceId,
    String? qrPayload,
    String? paperImagePath,
  }) async {
    final token = await getToken();
    final form = FormData.fromMap({
      'exam_id': examId,
      'booklet': booklet,
      'student_no': studentNo,
      'answers': answers,
      'confidence': confidence,
      'device_id': deviceId,
      if (qrPayload != null) 'qr_payload': qrPayload,
      if (paperImagePath != null)
        'paper_image': await MultipartFile.fromFile(paperImagePath),
    });
    final res = await _dio.post<Map<String, dynamic>>(
      '/scans',
      data: form,
      options: _authed(token ?? ''),
    );
    return Map<String, dynamic>.from(res.data!['data'] as Map);
  }
}
