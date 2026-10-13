FROM php:8.3-apache

# Instalar dependencias necesarias para las extensiones
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && docker-php-ext-install pdo pdo_mysql

# Instalar la extensión de Redis mediante PECL
RUN pecl install redis && docker-php-ext-enable redis

# Habilitar mod_rewrite de Apache si tus rutas de PHP lo requieren
RUN a2enmod rewrite

# Copiar el código fuente del backend al directorio público de Apache
COPY src/ /var/www/html/

# Ajustar permisos para Apache
RUN chown -R www-data:www-data /var/www/html/