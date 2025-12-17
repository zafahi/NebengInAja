FROM php:8.2-apache

# Aktifkan mysqli
RUN docker-php-ext-install mysqli

# Copy semua file API
COPY . /var/www/html/

# Disable MPM lain supaya tidak double
RUN a2dismod mpm_event mpm_worker

# Expose port default
EXPOSE 80

# Jalankan Apache
CMD ["apache2-foreground"]
