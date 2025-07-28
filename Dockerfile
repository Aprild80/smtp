FROM php:8.1-cli

# Install dependencies (e.g., sockets if needed)
RUN apt-get update && apt-get install -y \
    git unzip curl && \
    docker-php-ext-install sockets

# Copy all your PHP files
COPY . /var/www/html
WORKDIR /var/www/html

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080"]
