FROM php:8.2-cli

# Aktifkan ekstensi mysqli
RUN docker-php-ext-install mysqli

# Copy semua file API
COPY . /app

# Set working directory
WORKDIR /app

# Expose port 8080
EXPOSE 8080

# Jalankan built-in PHP server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
