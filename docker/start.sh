#!/bin/sh
set -e
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
cd /var/www/html
php artisan config:clear
php artisan migrate --force --seed          # crée/complète les tables (idempotent) + données de référence
php artisan upl:creer-admin --env || true   # crée le 1er administrateur si UPL_ADMIN_* sont définis (une seule fois)
exec apache2-foreground
