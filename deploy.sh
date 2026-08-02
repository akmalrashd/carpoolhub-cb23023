#!/usr/bin/env bash
#
# Production deploy for Hostinger shared hosting.
#
# The server cannot run Composer: proc_open is in disable_functions. GitHub
# Actions therefore installs vendor/ on its own runner, uploads it as
# build.tar.gz, and calls this script. See .github/workflows/deploy.yml.
#
# There is no asset build step. Every stylesheet the app serves is a committed
# file under public/css/, so `git reset --hard` alone puts the frontend in place.
#
# Normally you never run this by hand — pushing to master is the deploy.
# Running it manually only pulls source and rebuilds caches; it will NOT
# refresh vendor/ unless a build.tar.gz is present.

set -euo pipefail

APP_DIR="$HOME/carpoolhub"
BRANCH="master"

cd "$APP_DIR"

# NOTE: this script deliberately does NOT update the source tree. The workflow
# does the git fetch/reset BEFORE invoking it. If the pull happened in here it
# would overwrite this file while bash was still reading it, and bash — which
# reads scripts incrementally by byte offset — would carry on executing the
# stale layout. That silently skipped the unpack step once and 500'd the site.

if [ -f build.tar.gz ]; then
  echo "==> Unpacking build artifacts"
  tar xzf build.tar.gz
  rm -f build.tar.gz
else
  echo "==> No build.tar.gz — keeping existing vendor/"
fi

# Fail fast rather than serving a half-broken site: vendor/ is not tracked, so
# if the upload stage failed it will be missing entirely.
[ -f vendor/autoload.php ] || { echo "FATAL: vendor/ missing"; exit 1; }

# The layouts cache-bust this one with filemtime(), and Laravel promotes that
# warning to an ErrorException — so if it ever goes missing the site answers 500
# on every page, not merely unstyled. It is committed, so this only trips if
# someone forgot to `git add` it.
[ -f public/css/app.css ] || { echo "FATAL: public/css/app.css missing"; exit 1; }

# Left over from the old bundler. Nothing references them any more and they are
# no longer gitignored, so git reset --hard will not clear them on its own.
rm -rf public/build public/hot

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

