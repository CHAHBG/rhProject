FROM php:8.2-apache

# Install extensions specifically for MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Install CA certificates for SSL connections (Aiven requirement)
RUN apt-get update && apt-get install -y ca-certificates && update-ca-certificates

# Enable Apache mod_rewrite if needed (good practice for PHP apps)
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

# Set working directory permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Render uses this by default)
EXPOSE 80
