#!/usr/bin/env bash
#
# Post-deploy script for the Hostinger shared hosting account.
#
# Hostinger's auto deploy only pulls the latest commit from the main branch.
# It does not run migrations and it does not refresh Laravel's caches, so a
# commit that adds a route or a migration will look deployed while the live
# site still runs on the old cached route table. That is what causes the
# "Route [x] not defined" error even though the file is clearly in the repo.
#
# Run this once over SSH after every deploy:
#
#   cd ~/domains/carpoolhub.prsdntworldwide.com/public_html && bash deploy.sh
#
# Composer is not usable on this host because proc_open sits in the PHP
# disable_functions list, so vendor/ has to stay committed and this script
# never tries to install dependencies.

set -euo pipefail

echo "==> Running database migrations"
php artisan migrate --force

# optimize:clear wipes the stale config, route, view and compiled class caches
# that the auto deploy leaves behind. optimize then rebuilds them, which is
# what production wants anyway because reading a cached route table is much
# faster than parsing routes/web.php on every request.
echo "==> Clearing stale caches"
php artisan optimize:clear

echo "==> Rebuilding caches for production"
php artisan optimize

echo "==> Done, now serving commit $(git rev-parse --short HEAD)"
