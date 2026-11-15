#!/bin/sh
# Holdout: when started as root, the Laravel automations (migrations, caches)
# ran as root. Give everything PHP writes back to www-data.
[ "$(id -u)" -eq 0 ] || exit 0

app_dir="${APP_BASE_DIR:-/var/www/html}"
data_dir="${HOLDOUT_DATA_DIR:-$app_dir/storage/app/data}"

chown -R www-data:www-data "$app_dir/storage" "$app_dir/bootstrap/cache" "$data_dir" 2>/dev/null
exit 0
