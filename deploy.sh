#!/usr/bin/env bash
#
# Production deploy for Hostinger shared hosting.
#
# The server cannot build anything: proc_open is in disable_functions (so
# Composer will not run) and Node is not installed (so Vite will not run).
# vendor/ and public/build are therefore committed to the repo — build them
# locally before pushing:
#
#   composer install --no-dev --prefer-dist --optimize-autoloader
#   npm run build
#   git commit -am "..." && git push
#
# Then run this over SSH.

set -euo pipefail

APP_DIR="$HOME/carpoolhub"
BRANCH="master"

cd "$APP_DIR"

echo "==> Pulling $BRANCH"
git pull --ff-only origin "$BRANCH"

# artisan storage:link is unusable here: it calls PHP's symlink(), which is in
# disable_functions. The shell's ln is not affected, so link it directly.
echo "==> Linking storage"
ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"

echo "==> Migrating"
php artisan migrate --force

echo "==> Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Done: $(git rev-parse --short HEAD)"
