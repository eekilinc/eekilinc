# Optik Mobil (Flutter)

Hesap ile giriş → sınav listesi → kamera ile optik form tara → onayla → kaydet.
Bağlantı yoksa tarama kuyruğa alınır, sonra otomatik gönderilir.

## Çalıştırma

```bash
cd mobile
flutter pub get
# API adresi (Sail yerelde 80. portta çalışıyorsa emülatörden 10.0.2.2):
flutter run --dart-define=API_URL=http://10.0.2.2/api
# Gerçek cihaz + LAN'daki backend:
flutter run --dart-define=API_URL=http://192.168.1.50/api
```

Varsayılan `API_URL`: `http://10.0.2.2/api` (`lib/core/config.dart`).

## APK

```bash
flutter build apk --debug --dart-define=API_URL=https://optik.SITE/api
# çıktı: build/app/outputs/flutter-apk/app-debug.apk
```

Android'de kamera izni `AndroidManifest`'te, iOS'ta `NSCameraUsageDescription`
`Info.plist`'te tanımlı.

## OMR notları

- `lib/omr/omr_processor.dart`: saf Dart (OpenCV yok) — marker bulma,
  homografi + warp (sayfa çerçevesi), baloncuk doluluk okuma.
- Warp çıktısı **sayfa çerçevesindedir**: hedef dörtgen spec'teki
  `anchors.positions`'tan alınır, bu yüzden zone koordinatları birebir uyar.
- Test: `flutter test` (sentetik form üretip tam okuma doğrular).
- Saha kalibrasyonu (gerçek baskı + farklı ışıklar) yapılmadan eşikler
  (`fill_threshold`) değiştirilmemeli.

## Akış

Login → Sınavlar (kitapçık seç) → Tara (canlı QR doğrular, köşe kılavuzu,
deklanşör) → Onay (no/kitapçık düzelt, çoklu işaret sarı) → Kaydet →
başarılıysa skor, bağlantı yoksa kuyruk.
