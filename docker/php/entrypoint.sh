#!/bin/sh
# Avvio del container di sviluppo: prepara dipendenze e cartelle di runtime.
set -e

cd /var/www/html

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    echo "[connectingpc] vendor/ assente: eseguo composer install"
    composer install --no-interaction --no-progress --prefer-dist
fi

for dir in storage/logs storage/cache storage/sessions storage/uploads storage/tiles storage/maintenance; do
    mkdir -p "$dir"
done
chown -R www-data:www-data storage 2>/dev/null || true
chmod -R ug+rwX storage 2>/dev/null || true

exec docker-php-entrypoint "$@"
