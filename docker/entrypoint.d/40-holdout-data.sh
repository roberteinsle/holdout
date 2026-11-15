#!/bin/sh
# Holdout: prepare the data directory on first start, so an installation needs
# no manual configuration. It holds the SQLite database and the app key.
data_dir="${HOLDOUT_DATA_DIR:-/var/www/html/storage/app/data}"

mkdir -p "$data_dir" 2>/dev/null

if [ ! -w "$data_dir" ]; then
    echo "❌ Holdout: cannot write to $data_dir (running as uid $(id -u))." >&2
    echo "   Give the mounted folder to this user, e.g. on the host: sudo chown -R $(id -u):$(id -g) <folder>" >&2
    exit 1
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ -n "$DB_DATABASE" ] && [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    echo "ℹ️ Holdout: created database $DB_DATABASE"
fi

# The key encrypts sessions and cookies. Generated once, then kept with the data.
if [ -z "$APP_KEY" ] && [ ! -s "$data_dir/app.key" ]; then
    php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$data_dir/app.key"
    chmod 600 "$data_dir/app.key"
    echo "ℹ️ Holdout: created app key in $data_dir/app.key"
fi
