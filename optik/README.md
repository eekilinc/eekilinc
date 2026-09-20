# OptikReader — Optik Form Okuma

Web: **Laravel + Blade + Tailwind + Sanctum + MySQL** (repo kökü doğrudan Laravel uygulamasıdır;
subdomain document root → `public/`)
Mobil: **Flutter** (hesap ile giriş → tara → kaydet) — `mobile/`
Form: önce **sabit (40 soru x 5 şık)**, ama spec **dinamik uyumlu** (v1 JSON).

```
OptikReader/
  app/, routes/, database/, resources/, public/ ... <- Laravel (kök)
  mobile/                   <- Flutter iskelet
  docs/                     <- analiz + mimari + API + kurulum notları
  packages/form-spec/v1.json <- TEK KAYNAK: web PDF + mobil parser aynı dosyayı kullanır
```

## Karar defteri

1. Web = Laravel (+ Blade/Tailwind web panel, Sanctum API mobil için).
2. Mobil = Flutter (tek kod iOS+Android).
3. Yayın = subdomain (document root **`public/`**).
4. Tarama = **login zorunlu** (Sanctum token, her scan `scanned_by` ile kayıtlı).
5. DB = **MySQL**.
6. Form = sabit başla + dinamik hazır: `question_count / option_count / booklet / student_no_digits` parametrik.
   Kitapçık türü (A/B/C/D) + Öğrenci ID baloncuğu **v1'de zorunlu**.

## Geliştirme (Sail)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test --compact
```

## Neden bu mimari?

- ZipGrade'in eksiği (sınav üretmiyor, versiyon takibi yok) bizde yok: `Exam + FormTemplate(spec JSON) + AnswerKey(booklet)` tek bütün.
- Mobil anahtarı görmez (güvenlik): mobil OMR yapar → `answers[]` + fotoğrafı API'ye gönderir → **puanı server hesaplar**.
  İnternet yoksa mobil kuyruğa atar, login token ile sonradan sync eder.
- Form değişince app güncellemesi gerekmez: mobil `GET /api/exams/{id}` ile `form-spec` çeker, koordinatlar normalize (0-1).
