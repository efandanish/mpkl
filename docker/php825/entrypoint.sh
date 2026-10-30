#!/bin/sh
set -eu
php -r 'if (PHP_VERSION !== "8.2.5") { fwrite(STDERR, "Expected exactly PHP 8.2.5\n"); exit(1); }'
# /source is read-only. All application writes stay in this container's copy.
tar -C /source --exclude='./.git' --exclude='./app/config/config.php' --exclude='./database/caj_pemajuan.sql' --exclude='./_installation/04 users.sql' --exclude='./docker/php825/local.php' --exclude='./app/config/local.php' \
    --exclude='./app/logs' --exclude='./app/uploads' --exclude='./app/backups' \
    --exclude='./public/uploads/receipts' -cf - . | tar -C /workspace -xf -
php /runtime/init-config.php
cp /runtime/local.example.php /workspace/app/config/local.php
mkdir -p /workspace/app/logs /workspace/app/uploads /workspace/app/backups \
    /workspace/public/uploads/receipts /tmp/php825-receipts
# Dependencies are generated inside this disposable container, never copied from the host.
if [ ! -f /workspace/vendor/autoload.php ]; then
    composer install --no-dev --prefer-dist --no-interaction --no-progress
fi
exec "$@"
