#!/bin/sh
# Holdout: prepare the data directory on first start, so an installation needs
# no manual configuration. It holds the SQLite database and the app key.
#
# Started as root (the default in deploy/docker-compose.portainer.yml), the
# script hands the mounted folder to www-data itself; PHP still runs as www-data.
# With PUID/PGID (like linuxserver images), www-data takes that UID/GID first, so
# the files belong to a user of the host, e.g. a DSM user on a Synology NAS.
app_dir="${APP_BASE_DIR:-/var/www/html}"
data_dir="${HOLDOUT_DATA_DIR:-$app_dir/storage/app/data}"
app_user="www-data"

can_write() {
    if [ "$(id -u)" -eq 0 ]; then
        su -s /bin/sh "$app_user" -c "test -w '$data_dir'"
    else
        test -w "$data_dir"
    fi
}

if [ "$(id -u)" -eq 0 ] && [ -n "$PUID" ]; then
    want_gid="${PGID:-$PUID}"
    if [ "$(id -u "$app_user")" != "$PUID" ] || [ "$(id -g "$app_user")" != "$want_gid" ]; then
        if ! docker-php-serversideup-set-id "$app_user" "$PUID:$want_gid" >/dev/null; then
            echo "❌ Holdout: could not set $app_user to PUID=$PUID PGID=$want_gid." >&2
            exit 1
        fi
        # The app is world-readable; only the folders PHP writes to need the new IDs.
        chown -R "$app_user:$app_user" "$app_dir/storage" "$app_dir/bootstrap/cache" /var/cache/nginx /var/log/nginx 2>/dev/null
    fi
    echo "ℹ️ Holdout: running PHP as uid $(id -u "$app_user"), gid $(id -g "$app_user")"
fi

mkdir -p "$data_dir" 2>/dev/null

if [ "$(id -u)" -eq 0 ]; then
    chown -R "$app_user:$app_user" "$data_dir" 2>/dev/null
    chmod u+rwx "$data_dir" 2>/dev/null
fi

if ! can_write; then
    echo "❌ Holdout: the app user ($app_user, uid $(id -u "$app_user")) cannot write to the data folder $data_dir." >&2
    if [ "$(id -u)" -eq 0 ]; then
        echo "   Ownership was set, so the folder's own permissions (e.g. Synology ACLs) block it." >&2
        echo "   Set PUID/PGID to a host user who may write there (Synology: often PUID=1026, PGID=100)," >&2
        echo "   or give that folder read/write for 'Everyone' (Synology: File Station > Properties > Permission)." >&2
    else
        echo "   Start the container as root (user: root) with PUID/PGID, or on the host: sudo chown -R $(id -u):$(id -g) <folder>" >&2
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
    run_as_app "touch '$DB_DATABASE' && chmod 600 '$DB_DATABASE'"
    echo "ℹ️ Holdout: created database $DB_DATABASE"
fi

# The key encrypts sessions and cookies. Generated once, then kept with the data.
if [ -z "$APP_KEY" ] && [ ! -s "$data_dir/app.key" ]; then
    run_as_app "php -r 'echo \"base64:\".base64_encode(random_bytes(32));' > '$data_dir/app.key' && chmod 600 '$data_dir/app.key'"
    echo "ℹ️ Holdout: created app key in $data_dir/app.key"
fi
