FROM php:8.3-apache

# Instalar dependencias del sistema requeridas por Laravel, PostgreSQL y MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        intl \
        opcache

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
