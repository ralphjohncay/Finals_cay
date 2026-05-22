#!/bin/sh
set -e

cd /app

mkdir -p var/cache var/log public/uploads/products
chmod -R 775 var public/uploads 2>/dev/null || true

if [ ! -f config/jwt/private.pem ]; then
  echo "Generating JWT key pair..."
  php bin/console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null || true
fi

echo "Running database migrations..."
for i in 1 2 3 4 5 6 7 8 9 10; do
  if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null; then
    break
  fi
  echo "Database not ready, retry $i/10..."
  sleep 3
done

php bin/console cache:clear --env=prod --no-warmup 2>/dev/null || true
php bin/console cache:warmup --env=prod 2>/dev/null || true

PORT="${PORT:-8080}"
echo "Starting PHP server on 0.0.0.0:${PORT} (document root: public/)"
exec php -S "0.0.0.0:${PORT}" -t public public/router.php
