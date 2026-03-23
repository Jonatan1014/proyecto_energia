#!/bin/bash
set -e

echo "🚀 Iniciando DeviceManager..."

# Crear directorios de uploads si no existen
mkdir -p /var/www/html/src/public/uploads/negocios
mkdir -p /var/www/html/src/public/uploads/evidencias

# Establecer permisos correctos
chown -R www-data:www-data /var/www/html/src/public/uploads
chmod -R 775 /var/www/html/src/public/uploads

# Verificar conexión a base de datos
if [ -n "${DB_HOST}" ]; then
    echo "⏳ Esperando conexión a base de datos..."
    timeout=30
    while ! mysqladmin ping -h"${DB_HOST}" --silent 2>/dev/null; do
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo "⚠️  Timeout esperando base de datos, continuando..."
            break
        fi
        sleep 1
    done
    if [ $timeout -gt 0 ]; then
        echo "✓ Base de datos conectada"
    fi
fi

# Ejecutar comando de Apache
echo "✓ Iniciando Apache..."
exec apache2-foreground
