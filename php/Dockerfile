FROM php:8.2-apache

# System deps for scrypt PECL extension
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Core PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# scrypt via PECL
RUN pecl install scrypt \
    && docker-php-ext-enable scrypt

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN a2enmod rewrite

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
