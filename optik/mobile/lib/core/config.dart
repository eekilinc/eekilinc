// API adresi: derleme anında --dart-define=API_URL=... ile ezilebilir.
// Android emülatörden host makineye erişim için 10.0.2.2 kullanılır.
const String apiBaseUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'http://10.0.2.2/api',
);
