#!/bin/sh
set -e

echo "Starting FleetOps Backend..."

# Create .env file from environment variables if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file from environment variables..."
    cat > .env << EOF
APP_NAME=FleetOps
APP_ENV=${APP_ENV:-production}
APP_KEY=
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=${DB_CONNECTION:-sqlsrv}
DB_HOST=${DB_HOST:-sqlserver}
DB_PORT=${DB_PORT:-1433}
DB_DATABASE=${DB_DATABASE:-fleetops}
DB_USERNAME=${DB_USERNAME:-sa}
DB_PASSWORD=${DB_PASSWORD}
DB_ENCRYPT=${DB_ENCRYPT:-optional}
DB_TRUST_SERVER_CERTIFICATE=${DB_TRUST_SERVER_CERTIFICATE:-true}

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@fleetops.com"
MAIL_FROM_NAME="FleetOps"
EOF
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Wait for database to be ready
echo "Waiting for database connection..."
MAX_RETRIES=30
RETRY_COUNT=0
until php artisan db:show >/dev/null 2>&1; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "Database connection failed after $MAX_RETRIES attempts"
        exit 1
    fi
    echo "Database not ready, waiting... (attempt $RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done
echo "Database connection established!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear all caches
echo "Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for better performance
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache

echo "FleetOps Backend is ready!"

# Start PHP-FPM
exec php-fpm
