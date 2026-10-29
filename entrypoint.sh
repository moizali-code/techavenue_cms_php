#!/bin/sh
set -e

# Ensure storage dirs are writable
chown -R www-data:www-data /var/www/html/storage

# Run model init on every start — script is idempotent (skips existing models)
php /var/www/html/init-models.php

# Re-chown after PHP creates SQLite files as root
chown -R www-data:www-data /var/www/html/storage

exec apache2-foreground
