#!/usr/bin/env bash
#
# Production deploy for Hostinger shared hosting.
#
# The server cannot build: proc_open is in disable_functions (Composer will not
# run) and Node is not installed (Vite will not run). GitHub Actions therefore
# builds vendor/ and public/build on its own runner, uploads them as
# build.tar.gz, and calls this script. See .github/workflows/deploy.yml.
#
# Normally you never run this by hand — pushing to master is the deploy.
# Running it manually only pulls source and rebuilds caches; it will NOT
# refresh vendor/ or the compiled assets unless a build.tar.gz is present.

set -euo pipefail

APP_DIR="$HOME/carpoolhub"
BRANCH="master"

cd "$APP_DIR"

echo "==> Pulling $BRANCH"
git pull --ff-only origin "$BRANCH"

if [ -f build.tar.gz ]; then
  echo "==> Unpacking build artifacts"
  tar xzf build.tar.gz
  rm -f build.tar.gz
else
  echo "==> No build.tar.gz — keeping existing vendor/ and public/build"
fi

# Fail fast rather than serving a half-broken site: git pull deletes the old
# tracked copies of these, so if the upload stage failed they will be missing.
[ -f vendor/autoload.php ] || { echo "FATAL: vendor/ missing"; exit 1; }
[ -f public/build/manifest.json ] || { echo "FATAL: public/build missing"; exit 1; }

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
