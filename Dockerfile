FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip

RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["bash"]
