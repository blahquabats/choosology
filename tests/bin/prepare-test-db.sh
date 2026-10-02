#!/usr/bin/env bash
# Prepare isolated MariaDB database for PHPUnit integration tests.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DB_NAME="${CHOOSOLOGY_TEST_DB:-choosology_test}"

sudo mariadb -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mariadb -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO 'choosology'@'127.0.0.1'; GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO 'choosology'@'localhost'; FLUSH PRIVILEGES;"

# Drop and recreate for a clean schema
sudo mariadb -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`; CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [[ -f "$ROOT/choosology-schema.sql" ]]; then
  iconv -f UTF-16LE -t UTF-8 "$ROOT/choosology-schema.sql" | sudo mariadb "$DB_NAME"
fi

for f in ending_finds_setup.sql messages_digest_setup.sql signup_setup.sql news_setup.sql updates_setup.sql clipboard_setup.sql; do
  if [[ -f "$ROOT/sql/$f" ]]; then
    sudo mariadb "$DB_NAME" < "$ROOT/sql/$f" || true
  fi
done

echo "Prepared database: $DB_NAME"
