# 02 — Mimari ve Veritabanı (Laravel + MySQL + Flutter)

## Stack (kesin)

- **Backend:** Laravel 11, PHP 8.2+, Blade + Tailwind + Alpine.js (web panel), Laravel Sanctum (mobil token), MySQL 8.
- **PDF/QR:** `barryvdh/laravel-dompdf` (A4 form basımı), `simplesoftwareio/simple-qrcode` (QR payload).
- **Görsel:** `intervention/image` (server-side crop/normalize, yedek OMR), `public` diskte kağıt fotoğrafları.
- **Mobil:** Flutter stable, `camera`, `google_mlkit_barcode_scanning`, `dio`, `flutter_secure_storage`, `drift` (offline kuyruk).

## Akış

1. Öğretmen web'de: Sınıf → Öğrenci (CSV) → Sınav (40x5, kitapçık A/B) → Cevap anahtarı (her kitapçık) → PDF bas.
2. PDF üstünde QR: `OPTIK1:{exam_id}:{form_version}:{booklet}:{checksum}` + 4 köşe marker + öğrenci-no baloncuğu (8 hane) + kitapçık baloncuğu (A/B/C/D).
3. Mobil: login (Sanctum token) → sınav listesi + form-spec cache → kamera tara (QR + marker hizalama) → OMR (cihazda) → önizleme/düzeltme → `POST /api/scans`.
4. Server: QR checksum doğrula → öğrenci_no eşleştir → cevap anahtarıyla puanla → `scans` kaydet + fotoğraf sakla → web'de sonuç/item analizi.

## Puanlama kuralı (v1)

- `score = sum(doğru * points)`, boş=0, yanlış=0 (negatif yok, faz-2'de eklenecek), iptal soru herkes doğru sayılır.
- Çift işaret = o soru `review` (yanlış değil, incelenecek). Güven < eşik → `review_required`.
- Aynı `(exam_id, student_id, booklet)` tekrarı → `duplicate` uyar, üzerine yazma (ayarlanabilir).

## MySQL şema (özet, tam SQL: `database/schema/optik-schema.sql` — yedek; kurulum migration ile yapılır)

- `users(id, name, email unique, password, role teacher|admin)`
- `classes(id, user_id FK, name)`
- `students(id, class_id FK, student_no varchar(20), first_name, last_name, unique(class_id, student_no))`
- `exams(id, user_id FK, class_id FK nullable, title, course, question_count default 40, option_count default 5, booklets json default ["A","B"], status draft|published|archived)`
- `form_templates(id, exam_id FK, version int, spec json, pdf_path nullable)` — spec = `packages/form-spec/v1.json` şablonundan üretilir
- `answer_keys(id, exam_id FK, booklet char(1), answers json, points json, cancelled json)` — `answers={"1":"B",...}`
- `scans(id, exam_id FK, student_id FK nullable, student_no_raw varchar(20), booklet char(1), answers json, score decimal(6,2), max_score decimal(6,2), confidence tinyint, status ok|review|duplicate, paper_image_path, scanned_by FK users, device_id varchar(64), created_at)`
- Standart `personal_access_tokens` (Sanctum).

## Dinamik forma hazırlık (neden sonradan zor olmaz)

- Koordinatlar **normalize (0-1)**, piksel değil. `spec.questions.layout={columns, rows_per_column, option_count}` parametrik.
- Mobil parser `question_count/option_count`'u hardcode okumaz, spec'ten okur. 40x5 bugün, 80x4 yarın aynı kod.
- `form_templates.version` ile eski basılı kağıtlar da okunur (mobil spec'i QR'daki version ile ister).
