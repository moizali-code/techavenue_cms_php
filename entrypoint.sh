#!/bin/sh
set -e

# Ensure storage dirs are writable
chown -R www-data:www-data /var/www/html/storage

# Initialize models and repair seeded image references on startup
php /var/www/html/init-models.php
php /var/www/html/scripts/migrate-assets.php

# Re-chown after PHP creates SQLite files as root
chown -R www-data:www-data /var/www/html/storage

exec apache2-foreground
