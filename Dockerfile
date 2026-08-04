FROM muqrizmarzuki25/laraveldebian-php8.3:esilibus

WORKDIR /var/www

# The base image's default memory_limit (128M) is too low for bulk operations
# at real inspection scale (CIS 7:2021 Table 3 allows up to 700 samples per
# project) — e.g. seeding or recalculating a large project's score.
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/zz-memory.ini

# Copy all application files
COPY . /var/www

# Install PHP and JS dependencies during build instead of runtime
# This makes the image ready for Kubernetes
RUN composer install --no-interaction --optimize-autoloader --no-dev \
    && npm install \
    && npm run build

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Optimize Laravel for production
RUN php artisan storage:link \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 80

# The base image already has supervisord configured
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
