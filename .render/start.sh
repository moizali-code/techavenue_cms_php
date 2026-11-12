#!/bin/sh
# Render start command. Free plan disks are wiped on every sleep/redeploy, so each boot re-seeds a working CMS.
set -e
cd /var/www/html

if [ ! -f storage/.render-seeded ]; then
    mkdir -p storage/uploads/techavenue
    cp -r .render/seed/. storage/uploads/techavenue/
fi

php init-models.php
[ -f storage/.render-seeded ] || php scripts/import-content.php --apply
# Exits 1 when some seed paths have no image file; that is not fatal for serving the site.
php scripts/migrate-assets.php || true
php .render/setup.php
touch storage/.render-seeded

chown -R www-data:www-data storage
exec apache2-foreground
