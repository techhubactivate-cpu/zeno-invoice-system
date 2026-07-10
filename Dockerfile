FROM php:8.1-apache

# Install MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy your code
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Restart Apache
CMD ["apache2-foreground"]
