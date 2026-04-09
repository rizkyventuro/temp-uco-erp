#!/bin/bash
set -e

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

php artisan migrate --force
php artisan storage:link 2>/dev/null || true

php artisan schedule:work &
php artisan queue:work --queue=push-multiple-notif &
php artisan queue:work --queue=default --tries=3 --timeout=60 &

exec apache2-foreground