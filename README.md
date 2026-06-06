# Deploy Laravel ke cPanel via Git Pull Manual!

Panduan ini untuk deploy project Laravel di cPanel dengan struktur:

```text
Repository Laravel:
~/repositories/larapanel

Public folder subdomain:
~/public_html/demo.shendro.cloud

URL:
https://demo.shendro.cloud

Web runtime:
PHP 8.4 dari cPanel / PHP Selector

CLI artisan:
PHP 8.5
/opt/cpanel/ea-php85/root/usr/bin/php
```

> Web memakai PHP 8.4, tetapi command terminal memakai PHP 8.5 karena path CLI PHP 8.4 tidak tersedia di server.

---

## 0. Variabel Path

```text
PROJECT_DIR=/home/shec5913/repositories/larapanel
PUBLIC_DIR=/home/shec5913/public_html/demo.shendro.cloud
PHP_CLI=/opt/cpanel/ea-php85/root/usr/bin/php
```

---

## 1. Alur Deploy

```text
Local laptop:
1. npm run build
2. commit public/build
3. git push

cPanel:
1. git pull origin main
2. composer install jika ada package baru
3. package:discover
4. migrate jika ada perubahan database
5. storage link / fallback copy storage
6. clear cache
7. optimize production
8. copy public ke subdomain
9. tulis ulang .htaccess
10. tulis ulang index.php
11. cek website
```

---

# A. Persiapan di Local/Laptop

## A1. Build frontend Vite

```bash
npm install
```

```bash
npm run build
```

Cek hasil build:

```bash
ls public/build/manifest.json
```

Kalau file ini tidak ada, Laravel akan error:

```text
Vite manifest not found
```

## A2. Pastikan `public/build` masuk Git

Cek status:

```bash
git status
```

Kalau `public/build` tidak muncul, cek `.gitignore`.

Jika ada:

```gitignore
/public/build
```

ubah menjadi:

```gitignore
# /public/build
```

## A3. Commit dan push

```bash
git add public/build .gitignore
```

```bash
git commit -m "Build frontend assets"
```

```bash
git push origin main
```

Jika branch kamu `master`, pakai:

```bash
git push origin master
```

---

# B. Deploy di cPanel

## B1. Masuk folder project

```bash
cd /home/shec5913/repositories/larapanel
```

## B2. Cek branch aktif

```bash
git branch
```

## B3. Pull commit terbaru

```bash
git pull origin main
```

Jika branch kamu `master`:

```bash
git pull origin master
```

---

# C. Composer Package

## C1. Install/update package PHP

Jalankan ini setiap deploy, terutama jika ada perubahan `composer.json` atau `composer.lock`.

```bash
/opt/cpanel/ea-php85/root/usr/bin/php composer.phar install --no-dev --optimize-autoloader --no-scripts
```

## C2. Package discovery Laravel

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan package:discover --ansi
```

## C3. Jika muncul error `PailServiceProvider`

Error:

```text
Class "Laravel\Pail\PailServiceProvider" not found
```

Penyebab: `laravel/pail` adalah package development, tetapi server memakai `--no-dev`.

Cari referensi Pail:

```bash
grep -R "Pail" bootstrap config app composer.json composer.lock
```

Jika ada di `bootstrap/providers.php`, edit:

```bash
nano bootstrap/providers.php
```

Hapus baris:

```php
Laravel\Pail\PailServiceProvider::class,
```

Hapus cache manual:

```bash
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/events.php
```

Jalankan ulang:

```bash
/opt/cpanel/ea-php85/root/usr/bin/php composer.phar install --no-dev --optimize-autoloader --no-scripts
```

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan package:discover --ansi
```

---

# D. Migration Database

## D1. Cek status migration

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan migrate:status
```

## D2. Jalankan migration jika ada commit yang mengubah database

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan migrate --force
```

Contoh kasus yang butuh migration:

```text
Unknown column 'google_id'
Unknown column 'avatar'
Base table or view not found
```

## D3. Session driver database

Jika `.env` memakai:

```env
SESSION_DRIVER=database
```

maka tabel `sessions` harus ada.

Jika migration sessions belum ada, buat:

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan session:table
```

Lalu migrate:

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan migrate --force
```

---

# E. Storage Public / Symlink

## E1. Coba Laravel storage link

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan storage:link
```

## E2. Jika error `exec()`

Jika muncul:

```text
Call to undefined function Illuminate\Filesystem\exec()
```

artinya `exec()` diblokir hosting. Gunakan symlink manual.

## E3. Symlink manual ke repository public

```bash
[ -L /home/shec5913/repositories/larapanel/public/storage ] || ln -s /home/shec5913/repositories/larapanel/storage/app/public /home/shec5913/repositories/larapanel/public/storage
```

## E4. Jika symlink juga gagal

Gunakan fallback copy biasa:

```bash
mkdir -p /home/shec5913/public_html/demo.shendro.cloud/storage
```

```bash
cp -a /home/shec5913/repositories/larapanel/storage/app/public/. /home/shec5913/public_html/demo.shendro.cloud/storage/
```

Catatan: fallback copy bukan symlink. Jika ada upload baru, perlu copy ulang storage public ke folder subdomain.

Agar upload baru langsung terlihat tanpa copy ulang, arahkan disk public Laravel ke folder storage subdomain:

```env
PUBLIC_DISK_ROOT=/home/shec5913/public_html/demo.shendro.cloud/storage
PUBLIC_DISK_URL=https://demo.shendro.cloud/storage
```

Lalu clear config cache:

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan config:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan cache:clear
```

---

# F. Clear Cache Laravel

## F1. Clear cache standar

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan config:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan cache:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan view:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan route:clear
```

## F2. Hapus cache manual jika artisan error

```bash
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/events.php
```

Cek isi folder cache:

```bash
ls -la bootstrap/cache
```

Idealnya minimal ada:

```text
.gitignore
```

---

# G. Optimize Production

Jalankan setelah website stabil.

## G1. Cache config

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan config:cache
```

## G2. Cache route

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan route:cache
```

## G3. Cache view

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan view:cache
```

## G4. Jika optimize menyebabkan error

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan optimize:clear
```

---

# H. Copy Public ke Subdomain

## H1. Pastikan Vite manifest ada

```bash
ls -la /home/shec5913/repositories/larapanel/public/build/manifest.json
```

## H2. Copy isi folder public Laravel ke subdomain

```bash
cp -a /home/shec5913/repositories/larapanel/public/. /home/shec5913/public_html/demo.shendro.cloud/
```

Command ini bisa menimpa:

```text
index.php
.htaccess
```

Jadi setelah copy, wajib tulis ulang `.htaccess` dan `index.php`.

---

# I. Tulis Ulang `.htaccess`

## I1. Tulis `.htaccess` final

```bash
cat > /home/shec5913/public_html/demo.shendro.cloud/.htaccess <<'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Serve uploaded files directly from the public storage folder.
    # Without this, some cPanel/LiteSpeed setups route /storage/* into Laravel
    # and uploaded avatars return 404 even when the file exists on disk.
    RewriteCond %{REQUEST_URI} ^/storage/
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# php -- BEGIN cPanel-generated handler, do not edit
# Set the “alt-php84” package as the default “PHP” programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-alt-php84 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
EOF
```

## I2. Cek `.htaccess`

```bash
cat /home/shec5913/public_html/demo.shendro.cloud/.htaccess
```

---

# J. Tulis Ulang `index.php`

## J1. Tulis `index.php` final

```bash
cat > /home/shec5913/public_html/demo.shendro.cloud/index.php <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../../repositories/larapanel/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../../repositories/larapanel/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../../repositories/larapanel/bootstrap/app.php';

$app->handleRequest(Request::capture());
PHP
```

## J2. Cek `index.php`

```bash
cat /home/shec5913/public_html/demo.shendro.cloud/index.php
```

Path penting:

```php
require __DIR__.'/../../repositories/larapanel/vendor/autoload.php';

$app = require_once __DIR__.'/../../repositories/larapanel/bootstrap/app.php';
```

---

# K. Permission

## K1. Permission folder Laravel

```bash
chmod -R 755 /home/shec5913/repositories/larapanel/storage
```

```bash
chmod -R 755 /home/shec5913/repositories/larapanel/bootstrap/cache
```

## K2. Permission folder subdomain

```bash
chmod 755 /home/shec5913/public_html/demo.shendro.cloud
```

```bash
chmod 644 /home/shec5913/public_html/demo.shendro.cloud/.htaccess
```

```bash
chmod 644 /home/shec5913/public_html/demo.shendro.cloud/index.php
```

---

# L. Test Website

## L1. Buka website

```text
https://demo.shendro.cloud
```

## L2. Cek log Laravel jika error 500

```bash
tail -n 100 /home/shec5913/repositories/larapanel/storage/logs/laravel.log
```

## L3. Cek log LiteSpeed/subdomain jika error 403

```bash
tail -n 100 /home/shec5913/public_html/demo.shendro.cloud/error_log
```

---


# M. Deploy Singkat Bertahap

Gunakan bagian ini untuk deploy cepat tanpa satu script besar. Jalankan per blok. Kalau satu blok error, berhenti di situ dan baca pesan errornya.

## M1. Pull dan Composer

```bash
cd /home/shec5913/repositories/larapanel
git pull origin main

if [ -f composer.phar ]; then
  /opt/cpanel/ea-php85/root/usr/bin/php composer.phar install --no-dev --optimize-autoloader --no-interaction --no-scripts
else
  composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
fi
```

## M2. Env Storage dan Artisan

```bash
cd /home/shec5913/repositories/larapanel
mkdir -p /home/shec5913/public_html/demo.shendro.cloud/storage

grep -q '^PUBLIC_DISK_ROOT=' .env \
  && sed -i 's#^PUBLIC_DISK_ROOT=.*#PUBLIC_DISK_ROOT=/home/shec5913/public_html/demo.shendro.cloud/storage#' .env \
  || printf '\nPUBLIC_DISK_ROOT=/home/shec5913/public_html/demo.shendro.cloud/storage\n' >> .env

grep -q '^PUBLIC_DISK_URL=' .env \
  && sed -i 's#^PUBLIC_DISK_URL=.*#PUBLIC_DISK_URL=https://demo.shendro.cloud/storage#' .env \
  || printf 'PUBLIC_DISK_URL=https://demo.shendro.cloud/storage\n' >> .env

rm -f bootstrap/cache/config.php bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/routes-v7.php bootstrap/cache/events.php

/opt/cpanel/ea-php85/root/usr/bin/php artisan package:discover --ansi
/opt/cpanel/ea-php85/root/usr/bin/php artisan migrate --force
/opt/cpanel/ea-php85/root/usr/bin/php artisan optimize:clear
```

## M3. Copy Public Tanpa Menimpa `index.php` dan `.htaccess`

```bash
PROJECT_DIR=/home/shec5913/repositories/larapanel
PUBLIC_DIR=/home/shec5913/public_html/demo.shendro.cloud

[ -f "$PUBLIC_DIR/.htaccess" ] || { echo "File .htaccess belum ada. Jalankan bagian I1 dulu."; exit 1; }
[ -f "$PUBLIC_DIR/index.php" ] || { echo "File index.php belum ada. Jalankan bagian J1 dulu."; exit 1; }

rm -rf "$PUBLIC_DIR/build"
cp -a "$PROJECT_DIR/public/build" "$PUBLIC_DIR/"
find "$PROJECT_DIR/public" -maxdepth 1 -type f ! -name index.php ! -name .htaccess -exec cp -a {} "$PUBLIC_DIR/" \;
```

## M4. Permission dan Cek

```bash
chmod -R 755 /home/shec5913/repositories/larapanel/storage
chmod -R 755 /home/shec5913/repositories/larapanel/bootstrap/cache
chmod -R 755 /home/shec5913/public_html/demo.shendro.cloud/storage
chmod 755 /home/shec5913/public_html/demo.shendro.cloud
chmod 644 /home/shec5913/public_html/demo.shendro.cloud/.htaccess
chmod 644 /home/shec5913/public_html/demo.shendro.cloud/index.php

/opt/cpanel/ea-php85/root/usr/bin/php artisan tinker --execute='dump(config("app.url")); dump(config("filesystems.disks.public.root")); dump(config("filesystems.disks.public.url"));'
```

## M5. Cache Production Setelah Debug Beres

```bash
cd /home/shec5913/repositories/larapanel
/opt/cpanel/ea-php85/root/usr/bin/php artisan config:cache
/opt/cpanel/ea-php85/root/usr/bin/php artisan route:cache
/opt/cpanel/ea-php85/root/usr/bin/php artisan view:cache
```

---

# N. Checklist Deploy

```text
[ ] npm run build sudah dilakukan di local
[ ] public/build/manifest.json ada
[ ] public/build sudah di-commit
[ ] git pull origin main berhasil
[ ] composer install berhasil
[ ] package:discover berhasil
[ ] migrate --force berhasil jika ada migration
[ ] storage link/copy sudah dilakukan
[ ] cache/config/view/route clear berhasil
[ ] optimize production berhasil
[ ] public sudah dicopy ke subdomain
[ ] .htaccess sudah ditulis ulang
[ ] index.php sudah ditulis ulang
[ ] permission storage dan bootstrap/cache benar
[ ] website bisa dibuka
```

---

# O. Troubleshooting

## O1. Error `Vite manifest not found`

Cek:

```bash
ls -la /home/shec5913/repositories/larapanel/public/build/manifest.json
```

Solusi:

```text
npm run build di local
commit public/build
push
git pull di cPanel
copy public ke subdomain
```

## O2. Error `Class Socialite not found`

```bash
/opt/cpanel/ea-php85/root/usr/bin/php composer.phar require laravel/socialite --no-scripts
```

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan package:discover --ansi
```

## O3. Error Google `redirect_uri_mismatch`

`.env`:

```env
GOOGLE_REDIRECT_URI=https://demo.shendro.cloud/auth/google/callback
```

Google Cloud Console:

```text
Authorized JavaScript origins:
https://demo.shendro.cloud

Authorized redirect URIs:
https://demo.shendro.cloud/auth/google/callback
```

## O4. Error `Unknown column`

Contoh:

```text
Unknown column 'google_id'
```

Solusi:

```bash
/opt/cpanel/ea-php85/root/usr/bin/php artisan migrate --force
```

## O5. Error `storage:link` karena `exec()`

Gunakan:

```bash
[ -L /home/shec5913/repositories/larapanel/public/storage ] || ln -s /home/shec5913/repositories/larapanel/storage/app/public /home/shec5913/repositories/larapanel/public/storage
```

Jika symlink gagal:

```bash
mkdir -p /home/shec5913/public_html/demo.shendro.cloud/storage
```

```bash
cp -a /home/shec5913/repositories/larapanel/storage/app/public/. /home/shec5913/public_html/demo.shendro.cloud/storage/
```

## O6. Error 403

Cek PHP web:

```bash
cat > /home/shec5913/public_html/demo.shendro.cloud/check.php <<'PHP'
<?php
echo "OK<br>";
echo "PHP_VERSION: " . PHP_VERSION . "<br>";
echo "PDO: " . (class_exists('PDO') ? 'YES' : 'NO') . "<br>";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "<br>";
PHP
```

Buka:

```text
https://demo.shendro.cloud/check.php
```

Target:

```text
PHP_VERSION: 8.4.x
PDO: YES
pdo_mysql: YES
```

Hapus setelah selesai:

```bash
rm -f /home/shec5913/public_html/demo.shendro.cloud/check.php
```

---

# P. Catatan Keamanan

Jangan commit file `.env`.

Jika secret pernah terlihat di chat/log, rotate:

```text
DB password
Email password
Google OAuth client secret
APP_KEY jika repository pernah public dengan .env
```
