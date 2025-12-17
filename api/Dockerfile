FROM php:8.2-cli

# Aktifkan ekstensi mysqli
RUN docker-php-ext-install mysqli

# Copy semua
COPY . /app

WORKDIR /app

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
