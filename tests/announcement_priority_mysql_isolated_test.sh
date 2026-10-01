#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MYSQLD="$(command -v mysqld || true)"
TEMP_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/announcement-priority-mysql.XXXXXX")"
DATA_DIR="$TEMP_ROOT/data"
SOCKET="$TEMP_ROOT/mysql.sock"
SERVER_LOG="$TEMP_ROOT/mysqld.log"
SERVER_PID=''

cleanup() {
    if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
        kill -TERM "$SERVER_PID" 2>/dev/null || true
        wait "$SERVER_PID" 2>/dev/null || true
    fi
    rm -rf "$TEMP_ROOT"
}
trap cleanup EXIT INT TERM

if [[ -z "$MYSQLD" ]] || ! command -v php >/dev/null || ! php -m | grep -qi '^mysqli$'; then
    echo "mysqld, PHP, and mysqli are required for the isolated News priority test" >&2
    exit 2
fi

BASEDIR="$(cd "$(dirname "$MYSQLD")/.." && pwd)"
mkdir -p "$DATA_DIR"
"$MYSQLD" --no-defaults --basedir="$BASEDIR" --initialize-insecure \
    --datadir="$DATA_DIR" --user="$(id -un)" --log-error="$TEMP_ROOT/initialize.log"
"$MYSQLD" --no-defaults --basedir="$BASEDIR" --datadir="$DATA_DIR" \
    --socket="$SOCKET" --pid-file="$TEMP_ROOT/mysqld.pid" --skip-networking \
    --mysqlx=OFF --user="$(id -un)" --log-error="$SERVER_LOG" \
    --innodb-buffer-pool-size=64M >"$TEMP_ROOT/mysqld.stdout" 2>&1 &
SERVER_PID=$!

ready=0
for _ in $(seq 1 100); do
    if [[ -S "$SOCKET" ]] && php -r '
        try {
            new mysqli("localhost", "root", "", "", 0, $argv[1]);
            exit(0);
        } catch (Throwable $error) {
            exit(1);
        }
    ' "$SOCKET" >/dev/null 2>&1; then
        ready=1
        break
    fi
    if ! kill -0 "$SERVER_PID" 2>/dev/null; then
        break
    fi
    sleep 0.1
done
if [[ "$ready" != 1 ]]; then
    cat "$SERVER_LOG" >&2
    echo "Isolated MySQL socket server did not start" >&2
    exit 1
fi

ANNOUNCEMENT_PRIORITY_TEST_SOCKET="$SOCKET" \
ANNOUNCEMENT_PRIORITY_TEST_TMPDIR="$TEMP_ROOT" \
    php "$ROOT/tests/announcement_priority_mysql_isolated_test.php" "$ROOT"