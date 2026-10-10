#!/usr/bin/env bash
# Per-boot Cloud Agent services: MariaDB + PHP built-in server (:8000) + IPv6 localhost forward.
# Idempotent. Prefer tmux sessions so panes survive and are inspectable.
set -euo pipefail

cd /workspace

TMUX_CFG=""
if [[ -f /exec-daemon/tmux.portal.conf ]]; then
	TMUX_CFG="-f /exec-daemon/tmux.portal.conf"
fi

tmux_has() {
	# shellcheck disable=SC2086
	tmux $TMUX_CFG has-session -t "=$1" 2>/dev/null
}

tmux_new() {
	local name="$1"
	shift
	# shellcheck disable=SC2086
	tmux $TMUX_CFG new-session -d -s "$name" -c /workspace -- "${SHELL:-bash}" -l
	# shellcheck disable=SC2086
	tmux $TMUX_CFG send-keys -t "$name:0.0" "$*" C-m
}

echo "[cloud-agent-start] ensuring MariaDB"
sudo mkdir -p /var/run/mysqld
sudo chown mysql:mysql /var/run/mysqld 2>/dev/null || true

if ! sudo mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
	if ! tmux_has mariadb; then
		tmux_new mariadb 'sudo mysqld_safe --datadir=/var/lib/mysql'
	fi
	for _ in $(seq 1 90); do
		if sudo mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
			break
		fi
		sleep 1
	done
fi

if ! sudo mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
	echo "[cloud-agent-start] MariaDB failed to start" >&2
	# shellcheck disable=SC2086
	tmux $TMUX_CFG capture-pane -t mariadb -p 2>/dev/null | tail -30 || true
	exit 1
fi

# Recreate local connect file if missing (gitignored).
if [[ ! -f connect.local.php ]]; then
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

echo "[cloud-agent-start] ensuring PHP server on 0.0.0.0:8000"
if ! curl -sf --max-time 2 -o /dev/null http://127.0.0.1:8000/ >/dev/null 2>&1; then
	# Free a stale listener if present.
	if ss -ltn 2>/dev/null | grep -q ':8000 '; then
		echo "[cloud-agent-start] port 8000 busy but not serving HTTP; leaving as-is"
	elif ! tmux_has php-dev; then
		tmux_new php-dev 'php -S 0.0.0.0:8000 -t /workspace'
	fi
	for _ in $(seq 1 30); do
		if curl -sf --max-time 2 -o /dev/null http://127.0.0.1:8000/ >/dev/null 2>&1; then
			break
		fi
		sleep 1
	done
fi

# Desktop Simple Browser often prefers IPv6 ::1 for localhost.
if command -v socat >/dev/null 2>&1; then
	if ! ss -ltn 2>/dev/null | grep -q '\[::1\]:8000'; then
		if ! tmux_has php-ipv6; then
			tmux_new php-ipv6 'socat TCP6-LISTEN:8000,bind=[::1],fork,reuseaddr TCP4:127.0.0.1:8000'
		fi
	fi
fi

CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 3 http://127.0.0.1:8000/index.php?stay=1 || true)"
echo "[cloud-agent-start] http://127.0.0.1:8000/index.php?stay=1 → ${CODE}"
# shellcheck disable=SC2086
tmux $TMUX_CFG ls 2>/dev/null || true
echo "[cloud-agent-start] ready"
