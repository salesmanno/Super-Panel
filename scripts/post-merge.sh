#!/bin/bash
set -e

echo "[post-merge] PHP-sjekk..."
php -r "echo 'PHP OK: ' . PHP_VERSION . PHP_EOL;"

echo "[post-merge] Composer autoload-sjekk..."
php -r "require 'vendor/autoload.php'; echo 'Autoload OK' . PHP_EOL;"

echo "[post-merge] Ferdig."
