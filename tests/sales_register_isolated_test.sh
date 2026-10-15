#!/usr/bin/env bash
set -euo pipefail
# Sales only. Disposable socket DB, fake Vipps, no external DB configuration.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MYSQLD="$(command -v mysqld)"
BASE="$(cd "$(dirname "$MYSQLD")/.." && pwd)"
TEMP_ROOT="$(mktemp -d /tmp/sales-register-mysql.XXXXXX)"
PID=''
cleanup() {
  if [[ -n "$PID" ]]; then kill -TERM "$PID" 2>/dev/null || true; wait "$PID" 2>/dev/null || true; fi
  if [[ "${SALES_TEST_KEEP:-0}" != 1 ]]; then rm -rf "$TEMP_ROOT"; else echo "Fixture retained: $TEMP_ROOT"; fi
}
trap cleanup EXIT INT TERM
mkdir -p "$TEMP_ROOT/data"
"$MYSQLD" --no-defaults --basedir="$BASE" --initialize-insecure --datadir="$TEMP_ROOT/data" --user="$(id -un)" --log-error="$TEMP_ROOT/init.log"
"$MYSQLD" --no-defaults --basedir="$BASE" --datadir="$TEMP_ROOT/data" --socket="$TEMP_ROOT/mysql.sock" --pid-file="$TEMP_ROOT/mysql.pid" --skip-networking --mysqlx=OFF --user="$(id -un)" --innodb-buffer-pool-size=64M --log-error="$TEMP_ROOT/mysql.log" >"$TEMP_ROOT/mysql.stdout" 2>&1 &
PID=$!
READY=0
for _ in $(seq 1 500); do
  if php -r 'try {new PDO("mysql:unix_socket=".$argv[1],"root","");}catch(Throwable $e){exit(1);}' "$TEMP_ROOT/mysql.sock" >/dev/null 2>&1; then READY=1; break; fi
  sleep .1
done
if [[ "$READY" != 1 ]]; then cat "$TEMP_ROOT/mysql.log"; exit 1; fi
SALES_REGISTER_ONLY="${SALES_REGISTER_ONLY:-1}" php "$ROOT/tests/sales_mysql_isolated_test.php" "$ROOT" "$TEMP_ROOT"
