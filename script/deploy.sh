#!/usr/bin/env bash
#
# Deploy script cho Dylan HRM (Laravel 13) - chạy NATIVE, không dùng Docker.
# Lấy code mới nhất từ nhánh main, cài dependency, chạy migration và tối ưu cache.
# KHÔNG chạy seeding (db:seed) để tránh ghi đè dữ liệu thật.
#
# Yêu cầu trên máy chủ: git, php (>=8.3), composer.
#
# Cách dùng:
#   ./script/deploy.sh                # deploy nhánh main (mặc định)
#   BRANCH=main ./script/deploy.sh    # deploy nhánh tùy chỉnh qua biến môi trường
#
set -Eeuo pipefail

# --- Cấu hình -----------------------------------------------------------------
BRANCH="${BRANCH:-main}"
REMOTE="${REMOTE:-origin}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PROJECT_ROOT="/www/wwwroot/hrm.wpops.io"

# Đường dẫn thư mục gốc dự án.
# Thứ tự ưu tiên xác định thư mục dự án (để chạy được cả khi panel như aaPanel/BT
# thực thi script từ thư mục khác):
#   1) Biến môi trường PROJECT_ROOT (hoặc DEPLOY_PATH)
#   2) Tham số dòng lệnh đầu tiên:  ./deploy.sh /www/wwwroot/hrm.wpops.io
#   3) Thư mục cha của script (khi chạy trực tiếp ./script/deploy.sh)
#   4) Thư mục làm việc hiện tại nếu có file 'artisan'
resolve_project_root() {
  if [ -n "${PROJECT_ROOT:-}" ]; then
    printf '%s' "${PROJECT_ROOT}"; return
  fi
  if [ -n "${DEPLOY_PATH:-}" ]; then
    printf '%s' "${DEPLOY_PATH}"; return
  fi
  if [ -n "${1:-}" ]; then
    printf '%s' "$1"; return
  fi
  local script_dir parent
  script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" 2>/dev/null && pwd || true)"
  parent="$(cd "${script_dir}/.." 2>/dev/null && pwd || true)"
  if [ -n "${parent}" ] && [ -f "${parent}/artisan" ]; then
    printf '%s' "${parent}"; return
  fi
  printf '%s' "$(pwd)"
}

PROJECT_ROOT="$(resolve_project_root "${1:-}")"

# --- Helper -------------------------------------------------------------------
log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[ ok ]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[warn]\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31m[fail]\033[0m %s\n' "$*" >&2; exit 1; }

on_error() { die "Deploy thất bại ở dòng ${1}. Đã dừng lại."; }
trap 'on_error "${LINENO}"' ERR

# Chạy lệnh artisan trực tiếp trên máy chủ.
artisan() {
  "${PHP_BIN}" artisan "$@"
}

# --- Bắt đầu ------------------------------------------------------------------
cd "${PROJECT_ROOT}" 2>/dev/null || die "Không vào được thư mục dự án: ${PROJECT_ROOT}"
log "Thư mục dự án: ${PROJECT_ROOT}"

command -v git >/dev/null 2>&1 || die "Chưa cài git."
command -v "${PHP_BIN}" >/dev/null 2>&1 || die "Không tìm thấy PHP ('${PHP_BIN}')."
command -v "${COMPOSER_BIN}" >/dev/null 2>&1 || die "Không tìm thấy Composer ('${COMPOSER_BIN}')."
[ -f "artisan" ] || die "Không thấy file 'artisan' trong '${PROJECT_ROOT}'. Đặt đúng đường dẫn dự án qua biến PROJECT_ROOT, ví dụ: PROJECT_ROOT=/www/wwwroot/hrm.wpops.io ./script/deploy.sh"
[ -f ".env" ] || die "Thiếu file .env. Copy từ .env.example và cấu hình trước khi deploy."

# 1. Lấy code mới nhất từ nhánh main.
log "Lấy code mới nhất từ ${REMOTE}/${BRANCH}..."
git fetch "${REMOTE}" "${BRANCH}"
git checkout "${BRANCH}"
git reset --hard "${REMOTE}/${BRANCH}"
ok "Đã cập nhật code lên $(git rev-parse --short HEAD)."

# 2. Cài dependency production.
log "Cài đặt dependency (production, no-dev)..."
"${COMPOSER_BIN}" install \
  --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
ok "Dependency đã sẵn sàng."

# 3. Đảm bảo APP_KEY tồn tại.
if ! grep -qE '^APP_KEY=base64:.+' .env; then
  log "Chưa có APP_KEY, đang tạo..."
  artisan key:generate --force
  ok "Đã tạo APP_KEY."
fi

# 4. Kiểm tra kết nối database trước khi migrate.
log "Kiểm tra kết nối database..."
artisan db:show >/dev/null 2>&1 || die "Không kết nối được database. Kiểm tra cấu hình trong .env."
ok "Kết nối database thành công."

# 5. Bật maintenance mode trong lúc migrate.
log "Bật maintenance mode..."
artisan down --render="errors::503" || warn "Không bật được maintenance mode (bỏ qua)."

# 6. Chạy migration (KHÔNG seeding).
log "Chạy database migration (--force)..."
artisan migrate --force
ok "Migration hoàn tất."

# 7. Storage link + tối ưu cache.
log "Tạo storage link và tối ưu cache..."
artisan storage:link || warn "storage:link đã tồn tại (bỏ qua)."
artisan config:cache
artisan route:cache
artisan view:cache
artisan event:cache || true
artisan optimize
ok "Đã tối ưu ứng dụng."

# 8. Tắt maintenance mode.
log "Tắt maintenance mode..."
artisan up
ok "Ứng dụng đã hoạt động trở lại."

# --- Kết thúc -----------------------------------------------------------------
warn "Đã BỎ QUA seeding (db:seed) theo yêu cầu."
APP_URL="$(grep -E '^APP_URL=' .env | cut -d= -f2- | tr -d '"' || true)"
ok "Deploy hoàn tất! ${APP_URL:+Ứng dụng đang chạy tại ${APP_URL}}"
