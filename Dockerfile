FROM php:8.2-apache

# Install mysqli extension for MySQL connectivity
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy all app files to the Apache web root
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/

# Create a startup script that sets the PORT at runtime (Render provides PORT as env var)
RUN echo '#!/bin/bash\n\
sed -i "s/Listen 80/Listen ${PORT:-10000}/g" /etc/apache2/ports.conf\n\
sed -i "s/:80/:${PORT:-10000}/g" /etc/apache2/sites-available/000-default.conf\n\
apache2-foreground' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
