#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MYSQLD="$(command -v mysqld)"
BASE="$(cd "$(dirname "$MYSQLD")/.." && pwd)"
TMP="$(mktemp -d /tmp/ledger-reporting.XXXXXX)"
PID=''
cleanup() {
  if [[ -n "$PID" ]]; then kill -TERM "$PID" 2>/dev/null || true; wait "$PID" 2>/dev/null || true; fi
  rm -rf "$TMP"
}
trap cleanup EXIT INT TERM
mkdir -p "$TMP/data"
"$MYSQLD" --no-defaults --basedir="$BASE" --initialize-insecure --datadir="$TMP/data" --user="$(id -un)" --log-error="$TMP/init.log"
"$MYSQLD" --no-defaults --basedir="$BASE" --datadir="$TMP/data" --socket="$TMP/mysql.sock" --pid-file="$TMP/mysql.pid" --skip-networking --mysqlx=OFF --user="$(id -un)" --innodb-buffer-pool-size=64M --log-error="$TMP/mysql.log" >"$TMP/mysql.stdout" 2>&1 &
PID=$!
READY=0
for _ in $(seq 1 300); do
  if php -r 'try{new PDO("mysql:unix_socket=".$argv[1],"root","");}catch(Throwable $e){exit(1);}' "$TMP/mysql.sock" >/dev/null 2>&1; then READY=1; break; fi
  sleep .1
done
if [[ "$READY" != 1 ]]; then cat "$TMP/mysql.log"; exit 1; fi
php "$ROOT/tests/ledger_reporting_mysql_test.php" "$ROOT" "$TMP"
