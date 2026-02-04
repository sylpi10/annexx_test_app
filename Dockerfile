FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    libicu-dev libzip-dev \
    default-mysql-client \
 && docker-php-ext-install intl pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://get.symfony.com/cli/installer | bash \
 && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

CMD ["bash"]
