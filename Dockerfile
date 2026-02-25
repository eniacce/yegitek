FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libicu-dev libonig-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring opcache intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "opcache.enable=1\nopcache.memory_consumption=256\n\
    opcache.max_accelerated_files=20000\nopcache.validate_timestamps=0" \
    > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

ARG APP_SECRET=dummy
RUN APP_ENV=prod APP_SECRET=${APP_SECRET} \
    php bin/console cache:warmup --no-debug || echo "Cache warmup skipped"

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# .htaccess oluştur
RUN cat > /var/www/html/public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]
    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>
EOF

# Apache config + rewrite + port 8080
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' \
        /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/' /etc/apache2/sites-available/*.conf \
    && echo '<Directory /var/www/html/public>\n    AllowOverride All\n    FallbackResource /index.php\n</Directory>' \
        > /etc/apache2/conf-enabled/symfony.conf \
    && chown -R www-data:www-data /var/log/apache2 /var/run/apache2 \
    && chmod -R g+rwX /var/log/apache2 /var/run/apache2

RUN chown -R 1001:0 /var/www/html \
    && chmod -R g=u /var/www/html \
    && chmod -R 775 var/

EXPOSE 8080

USER 1001
