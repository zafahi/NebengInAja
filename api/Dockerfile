FROM php:8.2-cli

# Aktifkan mysqli
RUN docker-php-ext-install mysqli

# Copy folder api beserta config ke container
COPY . /app/api

WORKDIR /app/api

EXPOSE 8080

# Jalankan PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
