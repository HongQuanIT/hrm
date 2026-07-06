#!/usr/bin/env bash
#
# Deploy script cho Dylan HRM (Laravel 13 + Docker).
# Lấy code mới nhất từ nhánh main, build lại container, chạy migration và
# tối ưu cache. KHÔNG chạy seeding (db:seed) để tránh ghi đè dữ liệu thật.
#
# Cách dùng:
#   ./script/deploy.sh                # deploy nhánh main (mặc định)
#   BRANCH=main ./script/deploy.sh    # deploy nhánh tùy chỉnh qua biến môi trường
#
set -Eeuo pipefail

# --- Cấu hình -----------------------------------------------------------------
BRANCH="${BRANCH:-main}"
REMOTE="${REMOTE:-origin}"
APP_SERVICE="${APP_SERVICE:-app}"

# Đường dẫn tới thư mục gốc dự án (thư mục cha của script/).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

# --- Helper -------------------------------------------------------------------
log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[ ok ]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[warn]\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31m[fail]\033[0m %s\n' "$*" >&2; exit 1; }

on_error() { die "Deploy thất bại ở dòng ${1}. Đã dừng lại, hệ thống không bị thay đổi thêm."; }
trap 'on_error "${LINENO}"' ERR

# Chọn lệnh docker compose phù hợp (plugin v2 hoặc binary cũ).
if docker compose version >/dev/null 2>&1; then
  DC=(docker compose)
elif command -v docker-compose >/dev/null 2>&1; then
  DC=(docker-compose)
else
  die "Không tìm thấy 'docker compose' hoặc 'docker-compose'. Vui lòng cài Docker."
fi

# Chạy lệnh artisan bên trong container app.
artisan() {
  "${DC[@]}" exec -T "${APP_SERVICE}" php artisan "$@"
}

# --- Bắt đầu ------------------------------------------------------------------
cd "${PROJECT_ROOT}"
log "Thư mục dự án: ${PROJECT_ROOT}"

command -v git >/dev/null 2>&1 || die "Chưa cài git."
command -v docker >/dev/null 2>&1 || die "Chưa cài docker."
[ -f ".env" ] || die "Thiếu file .env. Copy từ .env.example và cấu hình trước khi deploy."

# 1. Lấy code mới nhất từ nhánh main.
log "Lấy code mới nhất từ ${REMOTE}/${BRANCH}..."
git fetch "${REMOTE}" "${BRANCH}"
git checkout "${BRANCH}"
git reset --hard "${REMOTE}/${BRANCH}"
ok "Đã cập nhật code lên $(git rev-parse --short HEAD)."

# 2. Build lại image & khởi động container.
log "Build image và khởi động container..."
"${DC[@]}" up -d --build
ok "Container đã chạy."

# 3. Đợi MySQL sẵn sàng rồi kiểm tra kết nối DB.
log "Chờ ứng dụng và database sẵn sàng..."
for i in $(seq 1 30); do
  if artisan db:show >/dev/null 2>&1; then
    ok "Kết nối database thành công."
    break
  fi
  [ "${i}" -eq 30 ] && die "Database không phản hồi sau 30 lần thử."
  sleep 2
done

# 4. Cài dependency production (phòng khi vendor thay đổi).
log "Cài đặt dependency (production, no-dev)..."
"${DC[@]}" exec -T "${APP_SERVICE}" composer install \
  --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
ok "Dependency đã sẵn sàng."

# 5. Đảm bảo APP_KEY tồn tại.
if ! grep -qE '^APP_KEY=base64:.+' .env; then
  log "Chưa có APP_KEY, đang tạo..."
  artisan key:generate --force
  ok "Đã tạo APP_KEY."
fi

# 6. Bật maintenance mode trong lúc migrate.
log "Bật maintenance mode..."
artisan down --render="errors::503" || warn "Không bật được maintenance mode (bỏ qua)."

# 7. Chạy migration (KHÔNG seeding).
log "Chạy database migration (--force)..."
artisan migrate --force
ok "Migration hoàn tất."

# 8. Storage link + tối ưu cache.
log "Tạo storage link và tối ưu cache..."
artisan storage:link || warn "storage:link đã tồn tại (bỏ qua)."
artisan config:cache
artisan route:cache
artisan view:cache
artisan event:cache || true
artisan optimize
ok "Đã tối ưu ứng dụng."

# 9. Tắt maintenance mode.
log "Tắt maintenance mode..."
artisan up
ok "Ứng dụng đã hoạt động trở lại."

# --- Kết thúc -----------------------------------------------------------------
warn "Đã BỎ QUA seeding (db:seed) theo yêu cầu."
APP_URL="$(grep -E '^APP_URL=' .env | cut -d= -f2- | tr -d '"' || true)"
ok "Deploy hoàn tất! ${APP_URL:+Ứng dụng đang chạy tại ${APP_URL}}"
