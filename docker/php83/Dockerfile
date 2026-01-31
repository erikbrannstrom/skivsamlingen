FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libcurl4-openssl-dev \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    unzip \
    git \
    && docker-php-ext-install \
    mysqli \
    pdo_mysql \
    mbstring \
    xml \
    curl \
    gd \
    zip \
    bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker-entrypoint.sh /usr/local/bin/
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

WORKDIR /var/www/skivsamlingen.se
