FROM php:8.3-apache



# Copy all your code into the standard web root

COPY . /var/www/html/



# Expose the port

EXPOSE 80



# Start the web server

CMD ["apache2-foreground"]
