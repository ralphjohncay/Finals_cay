#!/bin/sh
set -e

cd /app

if [ -z "$APP_SECRET" ]; then
  echo "ERROR: APP_SECRET is not set. Add it in Railway → Finals_cay → Variables."
  exit 1
fi

if [ -z "$DATABASE_URL" ]; then
  echo "ERROR: DATABASE_URL is not set. Use DATABASE_URL=\${{MySQL.DATABASE_URL}} in Railway."
  exit 1
fi

mkdir -p var/cache var/log var/sessions public/uploads/products config/jwt
chmod -R 777 var public/uploads config/jwt 2>/dev/null || true

# Prefer stable keys from Railway variables (survives redeploys). Otherwise generate once per volume.
if [ -n "$JWT_PRIVATE_KEY" ] && [ -n "$JWT_PUBLIC_KEY" ]; then
  printf '%s\n' "$JWT_PRIVATE_KEY" > config/jwt/private.pem
  printf '%s\n' "$JWT_PUBLIC_KEY" > config/jwt/public.pem
  chmod 600 config/jwt/private.pem 2>/dev/null || true
  echo "JWT keys loaded from environment variables."
elif [ ! -f config/jwt/private.pem ]; then
  echo "Generating JWT key pair (set JWT_PRIVATE_KEY + JWT_PUBLIC_KEY in Railway to keep tokens valid across deploys)..."
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
