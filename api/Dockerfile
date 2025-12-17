# Gunakan PHP + Apache resmi
FROM php:8.2-apache

# Aktifkan ekstensi mysqli
RUN docker-php-ext-install mysqli

# Copy semua file API ke root Apache
COPY . /var/www/html/

# Expose port default Apache
EXPOSE 80

# Jalankan Apache di foreground
CMD ["apache2-foreground"]
