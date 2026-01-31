#!/bin/sh

# Configure MySQL client SSL mode if specified
if [ "$MYSQL_SKIP_SSL" = "true" ]; then
    echo "[client]" > /etc/my.cnf
    echo "skip-ssl" >> /etc/my.cnf
fi

exec "$@"
