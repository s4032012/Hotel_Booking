#!/bin/bash
set -euo pipefail

if [[ -z "${APP_ADMIN_EMAIL:-}" || -z "${APP_ADMIN_PASSWORD:-}" || -z "${APP_ADMIN_NAME:-}" ]]; then
    echo "Skipping admin seed because APP_ADMIN_* env vars are missing."
    exit 0
fi

escape_sql() {
    printf "%s" "$1" | sed "s/'/''/g"
}

ADMIN_NAME_ESCAPED="$(escape_sql "${APP_ADMIN_NAME}")"
ADMIN_EMAIL_ESCAPED="$(escape_sql "${APP_ADMIN_EMAIL}")"
ADMIN_PASSWORD_ESCAPED="$(escape_sql "${APP_ADMIN_PASSWORD}")"

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" <<SQL
INSERT INTO users (full_name, email, phone, password, role)
SELECT '${ADMIN_NAME_ESCAPED}', '${ADMIN_EMAIL_ESCAPED}', '', '${ADMIN_PASSWORD_ESCAPED}', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = '${ADMIN_EMAIL_ESCAPED}'
);
SQL
