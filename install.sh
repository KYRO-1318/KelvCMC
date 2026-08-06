#!/usr/bin/env bash
#
# KelvCMC — one-shot installer for Linux / macOS (and Git Bash on Windows).
#
# Usage:
#   ./install.sh                # install, run migrations, create admin
#   ./install.sh --demo         # also seed demo products and data
#   ./install.sh --no-deps      # skip composer/npm installs (e.g. already done)
#   ./install.sh --force        # proceed in production mode
#
set -euo pipefail

cd "$(dirname "$0")"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${CYAN}[kelvcmc]${NC} $*"; }
ok()    { echo -e "${GREEN}[kelvcmc]${NC} $*"; }
warn()  { echo -e "${YELLOW}[kelvcmc]${NC} $*"; }
fail()  { echo -e "${RED}[kelvcmc]${NC} $*"; exit 1; }

DEMO=false; NO_DEPS=false; FORCE=false
for arg in "$@"; do
  case "$arg" in
    --demo) DEMO=true ;;
    --no-deps) NO_DEPS=true ;;
    --force) FORCE=true ;;
  esac
done

info "KelvCMC installer"
info "-----------------"

# --- PHP & Composer checks -------------------------------------------------
if ! command -v php >/dev/null 2>&1; then
  fail "PHP 8.3+ is required but was not found. Install PHP with the pdo_mysql, mbstring, xml, curl and gd extensions."
fi
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
if [[ "${PHP_VERSION%%.*}" -lt 8 ]] || { [[ "${PHP_VERSION%%.*}" -eq 8 ]] && [[ "${PHP_VERSION##*.}" -lt 3 ]]; }; then
  fail "PHP 8.3+ is required (found $PHP_VERSION)."
fi
ok "PHP $PHP_VERSION detected"

if ! command -v composer >/dev/null 2>&1; then
  fail "Composer was not found. Install it from https://getcomposer.org"
fi
ok "Composer detected"

# --- Environment ------------------------------------------------------------
if [ ! -f .env ]; then
  cp .env.example .env
  ok ".env created from .env.example — edit your database credentials before continuing."
else
  ok ".env already exists"
fi

# --- Dependencies -----------------------------------------------------------
if [ "$NO_DEPS" = false ]; then
  info "Installing PHP dependencies (composer install)..."
  composer install --no-interaction --prefer-dist --optimize-autoloader

  info "Building frontend assets (npm install && npm run build)..."
  if command -v npm >/dev/null 2>&1; then
    npm install --no-audit --no-fund && npm run build
  else
    warn "npm not found — skipping frontend build. The client portal will be unstyled until you run 'npm install && npm run build'."
  fi
else
  ok "Skipping dependency installation (--no-deps)"
fi

# --- Application key ---------------------------------------------------------
if ! grep -q "^APP_KEY=.\+" .env 2>/dev/null; then
  info "Generating application key..."
  php artisan key:generate --force
fi

# --- Storage -----------------------------------------------------------------
info "Creating storage link..."
php artisan storage:link --force

# --- Migrations & seeds -------------------------------------------------------
info "Running migrations..."
php artisan migrate --force

info "Seeding base data (roles, permissions, settings, admin account)..."
php artisan db:seed --force

if [ "$DEMO" = true ]; then
  info "Seeding demo data..."
  php artisan db:seed --class "Database\\Seeders\\DemoDataSeeder" --force
fi

# --- Cron ---------------------------------------------------------------------
info "Adding the Laravel scheduler to cron (skipped if already present)..."
( crontab -l 2>/dev/null | grep -v "artisan schedule:run" ; echo "* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1" ) | crontab - || warn "Could not write crontab — add this line manually:"
echo "      * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"

# --- Permissions ---------------------------------------------------------------
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
ok "Storage permissions set"

# --- Done ----------------------------------------------------------------------
echo
ok "✅ KelvCMC installed successfully!"
echo
APP_URL_VALUE=$(grep -E '^APP_URL=' .env | head -1 | cut -d= -f2-)
APP_URL_VALUE=${APP_URL_VALUE:-http://localhost}
echo "  Admin panel :   ${APP_URL_VALUE}/admin"
echo "  Client portal : ${APP_URL_VALUE}"
echo "  Default admin : admin@kelvcmc.local / password   (CHANGE IT IMMEDIATELY!)"
echo
warn "Remaining steps:"
echo "  1. Edit .env (DB credentials, mail, gateways, integrations)."
echo "  2. Start the queue worker:  php artisan queue:work --daemon  (supervisor on production)"
echo "  3. Open /admin and finish configuration (Settings → General)."
echo
echo "Full documentation: docs/installation-plesk.md and docs/production.md"
