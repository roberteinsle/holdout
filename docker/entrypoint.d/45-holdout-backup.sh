#!/bin/sh
# Holdout: before database migrations run (50-laravel-automations), keep a copy
# of the database in <data folder>/backups. Only when migrations are pending,
# so every update that changes the database has a copy from right before it.
app_dir="${APP_BASE_DIR:-/var/www/html}"
data_dir="${HOLDOUT_DATA_DIR:-$app_dir/storage/app/data}"
backup_dir="$data_dir/backups"
keep="${HOLDOUT_BACKUP_KEEP:-10}"

[ "${DB_CONNECTION:-sqlite}" = "sqlite" ] || exit 0
[ -s "$DB_DATABASE" ] || exit 0

run_as_app() {
    if [ "$(id -u)" -eq 0 ]; then
        su -s /bin/sh www-data -c "$1"
    else
        sh -c "$1"
    fi
}

if ! run_as_app "php '$app_dir/artisan' migrate:status --pending 2>/dev/null" | grep -q "Pending"; then
    exit 0
fi

target="$backup_dir/holdout-$(date +%Y%m%d-%H%M%S).sqlite"

# VACUUM INTO writes a consistent copy, even if the database is in use.
if run_as_app "mkdir -p '$backup_dir' && php -r '\$db = new PDO(\"sqlite:$DB_DATABASE\"); \$db->exec(\"VACUUM INTO \" . \$db->quote(\"$target\"));' && chmod 600 '$target'"; then
    echo "ℹ️ Holdout: migrations pending, saved a copy of the database to $target"
else
    echo "❌ Holdout: could not back up the database before migrating; not starting." >&2
    exit 1
fi

# Keep only the newest copies.
ls -1t "$backup_dir"/holdout-*.sqlite 2>/dev/null | tail -n +"$((keep + 1))" | while read -r old; do
    rm -f "$old"
done
exit 0
