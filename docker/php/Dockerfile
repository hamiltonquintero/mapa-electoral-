FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    bash \
    git \
    unzip \
    curl

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        mbstring \
        opcache \
        bcmath \
        pcntl

# Instalar Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Crear usuario no root
RUN addgroup -g 1000 www && adduser -D -u 1000 -G www www

# Permisos
RUN chown -R www:www /var/www/html

USER www

# Exponer puerto PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
