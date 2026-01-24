FROM php:5.6-fpm

# Debian Stretch is archived - update sources to use archive.debian.org
RUN sed -i s/deb.debian.org/archive.debian.org/g /etc/apt/sources.list && \
    sed -i s/security.debian.org/archive.debian.org/g /etc/apt/sources.list && \
    sed -i '/stretch-updates/d' /etc/apt/sources.list && \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid

RUN apt-get update && apt-get install -y --allow-unauthenticated \
    libxml2-dev \
    libcurl4-openssl-dev \
    libpng-dev \
    && docker-php-ext-install \
    mysql \
    mysqli \
    pdo_mysql \
    mbstring \
    xml \
    curl \
    gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/skivsamlingen.se
