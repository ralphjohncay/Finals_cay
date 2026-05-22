#!/bin/sh
set -e

cd /app

mkdir -p var/cache var/log var/sessions public/uploads/products config/jwt
chmod -R 777 var public/uploads config/jwt 2>/dev/null || true

if [ ! -f config/jwt/private.pem ]; then
  echo "Generating JWT key pair..."
  php bin/console lexik:jwt:generate-keypair --skip-if-exists || true
fi

echo "Running database migrations..."
for i in 1 2 3 4 5 6 7 8 9 10; do
  if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
    break
  fi
  echo "Database not ready, retry $i/10..."
  sleep 3
done

php bin/console cache:clear --env=prod --no-warmup || true
php bin/console cache:warmup --env=prod || true

echo "Starting Symfony on 0.0.0.0:${PORT:-8080}"
exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php
