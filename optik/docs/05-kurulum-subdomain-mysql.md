# 05 — Subdomain + MySQL Kurulum (hedef sunucu)

Bu adımlar **yerelde değil, yayın sunucusunda** koşar (yerel PHP'de mbstring/zip yok).

## 1. Gereksinim

PHP 8.2+, ext: `mbstring, xml, ctype, json, bcmath, curl, gd, zip, pdo_mysql`, Composer 2, MySQL 8, SSL.

```bash
php -v && php -m | grep -E 'mbstring|zip|pdo_mysql|gd'
mysql --version
```

## 2. Subdomain

- DNS: `optik` A kaydı → sunucu IP. Plesk/cPanel: subdomain kökü `.../optik.site.com/`, document root **`.../optik.site.com/public`** olacak.
- SSL ver (Let's Encrypt). Zorunlu: mobil HTTPS ister.

## 3. Kurulum (repo kökü Laravel uygulamasıdır)

```bash
cd /var/www/optik.site.com
git clone https://github.com/eekilinc/OptikReader.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
# .env düzenle: APP_URL=https://optik.site.com, DB_* (aşağıda), FILESYSTEM_DISK=public
php artisan key:generate --force
php artisan storage:link
```

## 4. MySQL

```sql
CREATE DATABASE optik CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
CREATE USER 'optik'@'localhost' IDENTIFIED BY 'GUCLU-SIFRE';
GRANT ALL ON optik.* TO 'optik'@'localhost'; FLUSH PRIVILEGES;
```

`.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=optik
DB_USERNAME=optik
DB_PASSWORD=GUCLU-SIFRE
```

```bash
# şema iki yoldan biri (öncelik migration):
php artisan migrate --force
# veya ham SQL (yedek):
mysql optik < database/schema/optik-schema.sql
```

## 5. Yayın kontrolleri

`storage bootstrap/cache` yazılabilir, `APP_DEBUG=false`, `php artisan config:cache route:cache view:cache`, zamanlanmış görev + queue (review kuyruğu için) opsiyonel.
