# Usar imagen oficial de PHP con Apache
FROM php:8.1-apache

# Exponer el puerto 80
EXPOSE 80

# Habilitar el módulo rewrite de Apache
RUN a2enmod rewrite

# Instalar dependencias necesarias y extensiones de PHP (PDO MySQL)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Cambiar el DocumentRoot de Apache para que apunte a src/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/src/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -i '/<Directory ${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copiar todos los archivos del proyecto al directorio web de Apache
COPY . /var/www/html/

# Asegurarse que el usuario de apache tiene permisos
RUN chown -R www-data:www-data /var/www/html

# Iniciar Apache
CMD ["apache2-foreground"]