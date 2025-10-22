# 1. Usar una imagen base oficial de PHP con Alpine
FROM php:8.3-apache-alpine

# 2. Copiar todo tu código al directorio web del servidor
COPY . /var/www/html/

# 3. Configurar Apache para que use index.php como archivo de inicio
# Esto solo es necesario si tu archivo de inicio no se llama index.html
RUN echo "DirectoryIndex index.php index.html" > /etc/apache2/conf.d/dir.conf

# 4. Exponer el puerto por defecto de Apache
EXPOSE 80

# 5. El comando por defecto para mantener el servidor web corriendo
CMD ["apache2-foreground"]
