FROM php:8.3-apache

# Herramienta oficial recomendada para instalar extensiones PHP en Docker
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instalar extensiones requeridas para Laravel (PostgreSQL, MySQL, GD, Zip, BCMath, Opcache)
RUN install-php-extensions pdo_pgsql pgsql pdo_mysql gd zip bcmath opcache

# Instalar herramientas básicas del sistema
RUN apt-get update && apt-get install -y git unzip curl && rm -rf /var/lib/apt/lists/*

# Habilitar módulo rewrite de Apache
RUN a2enmod rewrite

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del backend
COPY backend /var/www/html

# Instalar dependencias de Composer sin dev para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurar Apache DocumentRoot a /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar AllowOverride All para soportar .htaccess de Laravel
RUN echo '<Directory /var/www/html/public/>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>' >> /etc/apache2/apache2.conf

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar script de entrada
COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN tr -d '\r' < /usr/local/bin/render-entrypoint.sh > /usr/local/bin/render-entrypoint-clean.sh \
    && mv /usr/local/bin/render-entrypoint-clean.sh /usr/local/bin/render-entrypoint.sh \
    && chmod +x /usr/local/bin/render-entrypoint.sh

EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/render-entrypoint.sh"]
