#!/usr/bin/env bash
# Idempotent Cloud Agent bootstrap: PHP 8.3 + MariaDB + local DB + optional test deps.
# Belongs in environment install (snapshotted). Do not start long-running servers here.
set -euo pipefail

cd /workspace

export DEBIAN_FRONTEND=noninteractive

echo "[cloud-agent-install] apt packages"
sudo apt-get update -qq
sudo apt-get install -y -qq \
	php8.3-cli php8.3-mysqli php8.3-gd php8.3-xml php8.3-mbstring php8.3-curl \
	mariadb-server mariadb-client \
	socat curl ca-certificates unzip \
	>/tmp/cloud-agent-apt.log 2>&1 || {
	echo "[cloud-agent-install] apt failed; last log lines:" >&2
	tail -40 /tmp/cloud-agent-apt.log >&2
	exit 1
}

# MariaDB needs a short start during install so we can create the DB/user.
# start script will bring it up again on every boot.
if ! sudo mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
	echo "[cloud-agent-install] starting MariaDB for schema bootstrap"
	sudo mkdir -p /var/run/mysqld
	sudo chown mysql:mysql /var/run/mysqld
	# Prefer mysqld_safe (no systemd in cloud VMs).
	if ! pgrep -x mysqld >/dev/null && ! pgrep -x mariadbd >/dev/null; then
		sudo mysqld_safe --datadir=/var/lib/mysql >/tmp/mysqld_safe-install.log 2>&1 &
		disown || true
	fi
	for _ in $(seq 1 60); do
		if sudo mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
			break
		fi
		sleep 1
	done
fi

if ! sudo mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
	echo "[cloud-agent-install] MariaDB did not become ready" >&2
	tail -40 /tmp/mysqld_safe-install.log 2>/dev/null || true
	exit 1
fi

echo "[cloud-agent-install] ensuring choosology database + TCP user"
sudo mariadb <<'SQL'
CREATE DATABASE IF NOT EXISTS choosology CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'choosology'@'127.0.0.1' IDENTIFIED BY 'choosology';
CREATE USER IF NOT EXISTS 'choosology'@'localhost' IDENTIFIED BY 'choosology';
GRANT ALL PRIVILEGES ON choosology.* TO 'choosology'@'127.0.0.1';
GRANT ALL PRIVILEGES ON choosology.* TO 'choosology'@'localhost';
FLUSH PRIVILEGES;
SQL

# Import core schema only when empty (UTF-16 LE dump).
TABLE_COUNT="$(sudo mariadb -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='choosology';")"
if [[ "${TABLE_COUNT}" -lt 5 ]] && [[ -f choosology-schema.sql ]]; then
	echo "[cloud-agent-install] importing choosology-schema.sql (UTF-16LE → UTF-8)"
	iconv -f UTF-16LE -t UTF-8 choosology-schema.sql | sudo mariadb choosology
fi

# Optional seeds (ignore failures if already applied / missing).
for seed in sql/news_setup.sql sql/updates_setup.sql sql/lite_ui_setup.sql sql/datascrip_setup.sql sql/date_format_setup.sql sql/clipboard_setup.sql sql/ending_finds_setup.sql; do
	if [[ -f "$seed" ]]; then
		sudo mariadb choosology < "$seed" >/dev/null 2>&1 || true
	fi
done

if [[ ! -f connect.local.php ]]; then
	echo "[cloud-agent-install] writing connect.local.php"
	cat > connect.local.php <<'PHP'
<?php
return [
	'host' => '127.0.0.1',
	'user' => 'choosology',
	'password' => 'choosology',
	'pics_root' => '/workspace/storage/pics',
];
PHP
fi

mkdir -p storage/pics/universal storage/pics/thumbs

# Test tooling (best-effort; do not fail the environment if Composer/npm are flaky).
if [[ -f composer.json ]]; then
	if ! command -v composer >/dev/null 2>&1; then
		curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
		php /tmp/composer-setup.php --install-dir=/tmp --filename=composer --quiet \
			&& sudo mv /tmp/composer /usr/local/bin/composer \
			&& sudo chmod 755 /usr/local/bin/composer \
			|| echo "[cloud-agent-install] composer phar install warning (non-fatal)"
		rm -f /tmp/composer-setup.php
	fi
	if command -v composer >/dev/null 2>&1; then
		composer install --no-interaction --prefer-dist >/tmp/composer-install.log 2>&1 || {
			echo "[cloud-agent-install] composer install warning (non-fatal)"
			tail -20 /tmp/composer-install.log || true
		}
	fi
fi

if [[ -f package.json ]] && command -v npm >/dev/null 2>&1; then
	npm install --no-fund --no-audit >/tmp/npm-install.log 2>&1 || {
		echo "[cloud-agent-install] npm install warning (non-fatal)"
		tail -20 /tmp/npm-install.log || true
	}
fi

php -v | head -1
php -m | grep -E 'mysqli|gd|dom' || true
sudo mariadb -N -e "SELECT VERSION();"
echo "[cloud-agent-install] done"
