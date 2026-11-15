#!/bin/sh
# Holdout: prepare the data directory on first start, so an installation needs
# no manual configuration. It holds the SQLite database and the app key.
#
# Started as root (the default in deploy/docker-compose.portainer.yml), the
# script hands the mounted folder to www-data itself; PHP still runs as www-data.
data_dir="${HOLDOUT_DATA_DIR:-/var/www/html/storage/app/data}"
app_user="www-data"

can_write() {
    if [ "$(id -u)" -eq 0 ]; then
        su -s /bin/sh "$app_user" -c "test -w '$data_dir'"
    else
        test -w "$data_dir"
    fi
}

mkdir -p "$data_dir" 2>/dev/null

if [ "$(id -u)" -eq 0 ]; then
    chown -R "$app_user:$app_user" "$data_dir" 2>/dev/null
    chmod u+rwx "$data_dir" 2>/dev/null
fi

if ! can_write; then
    echo "❌ Holdout: the app user ($app_user, uid 82) cannot write to the data folder $data_dir." >&2
    if [ "$(id -u)" -eq 0 ]; then
        echo "   Ownership was set, so the folder's own permissions (e.g. Synology ACLs) block it." >&2
        echo "   Synology: File Station > folder > Properties > Permission > add 'Everyone' with read/write." >&2
    else
        echo "   Start the container as root (user: root), or on the host: sudo chown -R 82:82 <folder>" >&2
    fi
    exit 1
fi

run_as_app() {
    if [ "$(id -u)" -eq 0 ]; then
        su -s /bin/sh "$app_user" -c "$1"
    else
        sh -c "$1"
    fi
}

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ -n "$DB_DATABASE" ] && [ ! -f "$DB_DATABASE" ]; then
    run_as_app "touch '$DB_DATABASE'"
    echo "ℹ️ Holdout: created database $DB_DATABASE"
fi

# The key encrypts sessions and cookies. Generated once, then kept with the data.
if [ -z "$APP_KEY" ] && [ ! -s "$data_dir/app.key" ]; then
    run_as_app "php -r 'echo \"base64:\".base64_encode(random_bytes(32));' > '$data_dir/app.key' && chmod 600 '$data_dir/app.key'"
    echo "ℹ️ Holdout: created app key in $data_dir/app.key"
fi
