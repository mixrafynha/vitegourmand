FROM dunglas/frankenphp:latest

ENV APP_ENV=prod
ENV APP_DEBUG=0

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data var

EXPOSE 8080

ENV SERVER_NAME=0.0.0.0

CMD ["frankenphp", "php-server", "-r", "public/", "--listen", "0.0.0.0:8080"]
