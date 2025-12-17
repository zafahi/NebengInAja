FROM php:8.2-cli

# Aktifkan mysqli
RUN docker-php-ext-install mysqli

# Copy folder api dan config ke container
COPY api /app/api
COPY config /app/config

WORKDIR /app/api

EXPOSE 8080

# Jalankan PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
