FROM composer:2.2.25@sha256:be23a9c3f73293f966a981be03896f9aaaade6c2cc9415f4cf828d1f39c5005c AS composer
FROM php:8.2.5-cli-bullseye@sha256:979521db4cc92c31e8b69bdaeb6185bb41b714db3ad7e1d9d980d97f316d540c

# Build only missing extensions; keep the official exact-version PHP executable.
COPY sources.list /etc/apt/sources.list
RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 gd pdo_mysql
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/cajpemajuan-development.ini
COPY entrypoint.sh local.example.php router.php init-config.php /runtime/
RUN chmod 755 /runtime/entrypoint.sh
WORKDIR /workspace
ENTRYPOINT ["/runtime/entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8082", "-t", "public", "/runtime/router.php"]
