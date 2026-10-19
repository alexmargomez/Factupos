FROM php:8.2-cli-bookworm

# Instalar dependencias del sistema y wkhtmltopdf para SnappyPDF
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    sqlite3 \
    wkhtmltopdf \
    fonts-dejavu-core \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos de dependencias e instalar paquetes
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copiar el código fuente del backend
COPY . .

# Asegurar directorios y permisos de Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

EXPOSE ${BACKEND_PORT:-8000}

CMD ["sh", "-c", "\
    if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi && \
    if [ ! -f .env ]; then cp .env.example .env; fi && \
    sed -i \"s|^DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION:-mariadb}|\" .env && \
    sed -i \"s|^DB_HOST=.*|DB_HOST=factupos-mariadb|\" .env && \
    sed -i \"s|^DB_PORT=.*|DB_PORT=3306|\" .env && \
    sed -i \"s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-factupos}|\" .env && \
    sed -i \"s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-factupos_user}|\" .env && \
    sed -i \"s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-factupos_pass}|\" .env && \
    grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force && \
    php artisan config:clear && \
    until php artisan migrate --force; do echo 'Esperando a MariaDB...'; sleep 3; done && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=${BACKEND_PORT:-8000} \
"]
