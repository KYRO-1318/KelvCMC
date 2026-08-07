#!/usr/bin/env bash
#
# KelvCMC — Production deployment script
#
# Usage:
#   ./deploy.sh                  # pull, install deps, build, migrate, optimize
#   ./deploy.sh --maintenance    # enable maintenance mode during deploy
#
set -euo pipefail

cd "$(dirname "$0")"

RED='\033[0;31m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${CYAN}[deploy]${NC} $*"; }
ok()    { echo -e "${GREEN}[deploy]${NC} $*"; }
warn()  { echo -e "${YELLOW}[deploy]${NC} $*"; }
fail()  { echo -e "${RED}[deploy]${NC} $*"; exit 1; }

MAINTENANCE=false
for arg in "$@"; do
  case "$arg" in
    --maintenance) MAINTENANCE=true ;;
  esac
done

# ------------------------------------------------------------------
# Pre-deploy checks
# ------------------------------------------------------------------
info "Starting deployment..."

if [ ! -f storage/installed.lock ]; then
  fail "KelvCMC is not installed yet. Run ./install.sh first."
fi

if [ "$MAINTENANCE" = true ]; then
  info "Enabling maintenance mode..."
  php artisan down --retry=60 --refresh=30 || true
fi

# ------------------------------------------------------------------
# Pull latest changes
# ------------------------------------------------------------------
info "Pulling latest changes..."
git pull origin main || warn "Could not pull — continuing with current code."

# ------------------------------------------------------------------
# Dependencies
# ------------------------------------------------------------------
info "Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ -f package.json ]; then
  info "Installing npm dependencies..."
  npm ci --no-audit --no-fund 2>/dev/null || npm install --no-audit --no-fund

  info "Building frontend assets..."
  npm run build
fi

# ------------------------------------------------------------------
# Database migration
# ------------------------------------------------------------------
info "Running database migrations..."
php artisan migrate --force

# ------------------------------------------------------------------
# Optimize
# ------------------------------------------------------------------
info "Optimizing Laravel..."
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize 2>/dev/null || true

# ------------------------------------------------------------------
# Permissions
# ------------------------------------------------------------------
info "Fixing storage permissions..."
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ------------------------------------------------------------------
# Restart queue workers
# ------------------------------------------------------------------
info "Restarting queue workers..."
php artisan queue:restart 2>/dev/null || warn "Could not signal queue restart."

# ------------------------------------------------------------------
# Post-deploy
# ------------------------------------------------------------------
if [ "$MAINTENANCE" = true ]; then
  info "Disabling maintenance mode..."
  php artisan up
fi

# ------------------------------------------------------------------
# Health check
# ------------------------------------------------------------------
info "Running health check..."
php artisan kelvcmc:doctor --no-interaction || warn "Some health checks failed — review output above."

echo
ok "✅ Deployment complete!"
echo
echo "  Next steps:"
echo "  - Verify the site loads: $(grep -E '^APP_URL=' .env | cut -d= -f2-)"
echo "  - Check /admin is reachable"
echo "  - Monitor logs: tail -f storage/logs/laravel.log"
echo "  - Ensure supervisor restarts the queue worker"
