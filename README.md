from pathlib import Path

path = Path("/mnt/data/README-deploy-cpanel-larapanel.md")
content = path.read_text(encoding="utf-8")

# Add/update a deployment command section with explicit package install, migrate, storage link, optimize/clear.

new_section = r"""## 18. Command Deploy Final Lengkap

Gunakan command ini setiap kali ada commit baru yang perlu dideploy ke cPanel.

```bash
cd /home/shec5913/repositories/larapanel

# 1. Ambil commit terbaru dari GitHub
git pull origin main

# 2. Install/update dependency PHP jika ada package baru di composer.json/composer.lock
/opt/cpanel/ea-php85/root/usr/bin/php composer.phar install --no-dev --optimize-autoloader --no-scripts

# 3. Jalankan package discovery Laravel secara manual
/opt/cpanel/ea-php85/root/usr/bin/php artisan package:discover --ansi

# 4. Jalankan migration jika commit terbaru membawa perubahan database
/opt/cpanel/ea-php85/root/usr/bin/php artisan migrate --force

# 5. Buat storage symlink untuk akses file public upload
# Abaikan jika sudah pernah dibuat dan tidak berubah.
/opt/cpanel/ea-php85/root/usr/bin/php artisan storage:link

# 6. Clear cache lama
/opt/cpanel/ea-php85/root/usr/bin/php artisan config:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan cache:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan view:clear
/opt/cpanel/ea-php85/root/usr/bin/php artisan route:clear

# 7. Optimize ulang untuk production
/opt/cpanel/ea-php85/root/usr/bin/php artisan config:cache
/opt/cpanel/ea-php85/root/usr/bin/php artisan route:cache
/opt/cpanel/ea-php85/root/usr/bin/php artisan view:cache

# 8. Copy isi public Laravel ke folder subdomain
cp -a /home/shec5913/repositories/larapanel/public/. /home/shec5913/public_html/demo.shendro.cloud/

# 9. Tulis ulang .htaccess subdomain
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

# 10. Tulis ulang index.php subdomain agar mengarah ke repository Laravel
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

# 11. Permission dasar
chmod -R 755 /home/shec5913/repositories/larapanel/storage
chmod -R 755 /home/shec5913/repositories/larapanel/bootstrap/cache
chmod 755 /home/shec5913/public_html/demo.shendro.cloud
chmod 644 /home/shec5913/public_html/demo.shendro.cloud/.htaccess
chmod 644 /home/shec5913/public_html/demo.shendro.cloud/index.php
```
