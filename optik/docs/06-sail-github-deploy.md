# 06 — Sail + GitHub + Deploy akışı

## Dev (şu an, WSL + Docker)

```bash
cd /home/coder/readme/optik
./vendor/bin/sail up -d        # mysql + redis + app
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=DemoSeeder  # sonra
./vendor/bin/sail composer require laravel/sanctum barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode intervention/image
```

- Repo kökü doğrudan Laravel uygulamasıdır (`artisan`, `public/` kökte).
  Eski `backend/` ara klasörü kaldırıldı; Sail kökten koşar.
- Stub yedek: `docs/ref/` altında durur (schema.sql, routes-api.stub).
- Sail bitmeden `sail` komutu yok — kurulum bildirimini bekle.

## GitHub (sonra)

Bu klasör şu an `eekilinc/eekilinc` profil reposunun içinde (`optik/` untracked).
Deploy için ayrı repo önerilir:

1. GitHub'da yeni repo aç: `optik` (private önerilir, kağıt görüntüleri/kullanıcı verisi olacak).
2. `optik/` içeriğini yeni repo köküne taşı (Laravel dosyaları kökte, mobile/, docs/, packages/ alt klasör).
3. İlk push: `main` branch, Sail ile test edilmiş haliyle.

## Deploy (GitHub'dan subdomain'e)

- Hedef: subdomain document root → Laravel `public/`, MySQL, PHP 8.2+, SSL.
- Yöntem A (basit): sunucuda `git pull` + `composer install --no-dev` + `php artisan migrate --force`.
- Yöntem B (otomatik): GitHub Actions → SSH → sunucuda pull + migrate (workflow taslağı: `optik/.github.example/workflows/deploy.yml` eklenecek).
- `APP_URL=https://optik.SITE`, `DB_*` sunucu MySQL bilgileriyle doldurulur. `storage/app/public` kağıt fotoğrafları için disk yeterli olmalı.
