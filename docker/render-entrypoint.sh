#!/bin/bash
set -e

# Configurar puerto dinámico de Render ($PORT por defecto suele ser 10000 o 80)
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Crear carpetas de almacenamiento si no existen
mkdir -p /var/www/html/storage/framework/{sessions,views,cache,cache/data} /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Limpiar y optimizar cachés de Laravel
php artisan config:clear || true
php artisan cache:clear || true

# Ejecutar migraciones si se define RUN_MIGRATIONS=true
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force || true
fi

# Ejecutar seeders si se define RUN_SEEDERS=true
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeds iniciales..."
    php artisan db:seed --force || true
fi

echo "Iniciando Apache en puerto ${PORT}..."
exec apache2-foreground
