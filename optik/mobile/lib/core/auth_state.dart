import 'package:flutter/foundation.dart';

import 'api_client.dart';

/// Oturum durumu: açılışta token varsa sınav listesine, yoksa logine.
class AuthState extends ChangeNotifier {
  AuthState(this._api);

  final ApiClient _api;
  Map<String, dynamic>? user;
  bool loaded = false;

  Future<void> load() async {
    final token = await _api.getToken();
    if (token == null) {
      loaded = true;
      notifyListeners();
      return;
    }
    try {
      // Token geçerliliğini hafif bir istekle doğrula.
      await _api.exams();
      user = {'cached': true};
    } catch (_) {
      await _api.clearToken();
      user = null;
    }
    loaded = true;
    notifyListeners();
  }

  Future<void> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    user = await _api.login(
      email: email,
      password: password,
      deviceName: deviceName,
    );
    notifyListeners();
  }

  Future<void> logout() async {
    await _api.logout();
    user = null;
    notifyListeners();
  }
}
