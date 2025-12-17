FROM php:8.2-cli

# Install Apache
RUN apt-get update && apt-get install -y apache2 libapache2-mod-php

# Aktifkan mysqli
RUN docker-php-ext-install mysqli

# Copy kode API
COPY . /var/www/html/

# Disable modul MPM lain
RUN a2dismod mpm_event mpm_worker

# Expose port
EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
