#!/bin/bash

set -e

echo "[ENTRYPOINT] Installing local dependencies..."
gosu www-data bash -c "composer install"

echo "[ENTRYPOINT] Installing examples app dependencies..."
gosu www-data bash -c "cd /var/www/html && composer install"
