FROM php:8.0.2-fpm

# Set working directory
WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libxml2-dev

# Install extensions (will also enable them)
#
# soap is needed for composer - 'RUN docker-php-ext-install soap'
# throws 'No package 'libxml-2.0' found'
# libxml2-dev needs to be installed beforehand
#
RUN docker-php-ext-install pdo_mysql soap

# Copy php.ini
COPY ./php.ini /usr/local/etc/php/

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
