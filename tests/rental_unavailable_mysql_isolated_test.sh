#!/usr/bin/env bash
set -euo pipefail

# Disposable socket-only MySQL. Never use the app's database configuration.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MYSQLD="$(command -v mysqld || true)"
TEMP_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/rental-unavailable-mysql.XXXXXX")"
SERVER_PID=''
cleanup() {
    if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
        kill -TERM "$SERVER_PID" 2>/dev/null || true
        wait "$SERVER_PID" 2>/dev/null || true
    fi
    rm -rf "$TEMP_ROOT"
}
trap cleanup EXIT INT TERM
if [[ -z "$MYSQLD" ]] || ! php -m | grep -qi '^pdo_mysql$'; then
    echo "mysqld and PHP pdo_mysql are required for the isolated rental test" >&2
    exit 2
fi
BASEDIR="$(cd "$(dirname "$MYSQLD")/.." && pwd)"
mkdir -p "$TEMP_ROOT/data" "$TEMP_ROOT/sessions"
"$MYSQLD" --no-defaults --basedir="$BASEDIR" --initialize-insecure \
    --datadir="$TEMP_ROOT/data" --user="$(id -un)" --log-error="$TEMP_ROOT/initialize.log"
"$MYSQLD" --no-defaults --basedir="$BASEDIR" --datadir="$TEMP_ROOT/data" \
    --socket="$TEMP_ROOT/mysql.sock" --pid-file="$TEMP_ROOT/mysql.pid" --skip-networking \
    --mysqlx=OFF --user="$(id -un)" --log-error="$TEMP_ROOT/mysql.log" \
    --innodb-buffer-pool-size=64M >"$TEMP_ROOT/mysql.stdout" 2>&1 &
SERVER_PID=$!
ready=0
for _ in $(seq 1 100); do
    if [[ -S "$TEMP_ROOT/mysql.sock" ]] && php -r '
        try { new PDO("mysql:unix_socket=".$argv[1], "root", ""); exit(0); }
        catch (Throwable $e) { exit(1); }
    ' "$TEMP_ROOT/mysql.sock" >/dev/null 2>&1; then
        ready=1
        break
    fi
    if ! kill -0 "$SERVER_PID" 2>/dev/null; then break; fi
    sleep 0.1
done
if [[ "$ready" != 1 ]]; then
    cat "$TEMP_ROOT/mysql.log" >&2
    exit 1
fi
php "$ROOT/tests/rental_unavailable_mysql_isolated_test.php" "$ROOT" "$TEMP_ROOT"