FROM php:8.2-apache

# Enable mysqli
RUN docker-php-ext-install mysqli

# Copy source code
COPY . /var/www/html/

# Apache port
EXPOSE 80
