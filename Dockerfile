# Usar una imagen oficial de PHP con Apache
FROM php:8.1-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Copiar el código de la aplicación
COPY . /var/www/html

WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar dependencias solo si el proyecto usa Composer
RUN if [ -f composer.json ]; then \
			composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader; \
		else \
			echo "No composer.json found, skipping composer install"; \
		fi

# Exponer puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]