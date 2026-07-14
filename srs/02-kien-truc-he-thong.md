# 02 — Kiến trúc hệ thống

## 2.1. Ngăn xếp công nghệ

| Lớp | Công nghệ | Phiên bản / Ghi chú |
|-----|-----------|---------------------|
| Ngôn ngữ | PHP | `^8.3` |
| Framework | Laravel | `^13.8` |
| Cơ sở dữ liệu | MySQL | `8.4` |
| Giao diện | Blade + Tailwind CSS (CDN) | `cdn.tailwindcss.com` |
| Icon / Font | Material Symbols Outlined, Google Fonts (Inter) | qua CDN |
| JavaScript | Vanilla JS nhúng trong Blade | Không build bundler (không npm/Vite) |
| Máy chủ web | Nginx + PHP-FPM | Cấu hình trong `docker/` |
| Đóng gói | Docker Compose | app (PHP-FPM), nginx, mysql |
| Phiên & Cache | Database driver | `SESSION_DRIVER=database`, `CACHE_STORE=database` |
| Hàng đợi | Database driver | `QUEUE_CONNECTION=database` |
| Mail | Log driver | `MAIL_MAILER=log` (email chỉ ghi log, không gửi) |

Công cụ dev: Laravel Pint (format), PHPUnit 12, Mockery, Faker (`vi_VN`).

## 2.2. Mô hình phân lớp

Hệ thống theo kiến trúc **MVC** chuẩn của Laravel, bổ sung lớp **Service** cho nghiệp vụ nền:

```
┌──────────────────────────────────────────────────────────────┐
│  Trình duyệt (Blade views + Tailwind CDN + JS thuần)           │
└───────────────┬────────────────────────────────────────────────┘
                │ HTTP (session cookie)
┌───────────────▼────────────────────────────────────────────────┐
│  Routing  (routes/web.php)                                      │
│   - middleware: guest / auth / can:admin                        │
└───────────────┬────────────────────────────────────────────────┘
┌───────────────▼────────────────────────────────────────────────┐
│  Controllers (app/Http/Controllers)                             │
│   Auth, Dashboard, Employee, Attendance, Leave, Kpi,            │
│   Setting, Account                                              │
│   - Validate request                                            │
│   - Kiểm tra quyền (isSuperAdmin / abort_unless)                │
│   - Điều phối nghiệp vụ                                         │
└───────┬───────────────────────────────────┬────────────────────┘
        │                                   │
┌───────▼──────────────┐        ┌───────────▼─────────────────────┐
│  Services            │        │  Eloquent Models (app/Models)    │
│  AttendanceCloser    │◄──────►│  User, Employee, Department,      │
│  (chốt công cuối     │        │  Attendance, LeaveRequest, Kpi,   │
│   ngày)              │        │  KpiPhase, CompanySetting         │
└──────────────────────┘        └───────────┬─────────────────────┘
                                             │
                                 ┌───────────▼─────────────────────┐
                                 │  MySQL (migrations/seeders)      │
                                 └──────────────────────────────────┘
```

### Vai trò từng lớp

- **Routes** (`routes/web.php`): định tuyến URL slug tiếng Việt, gắn middleware `auth`/`guest`/`can:admin`.
- **Controllers**: mỏng, chịu trách nhiệm xác thực dữ liệu vào (`$request->validate`), kiểm soát quyền, gọi model/service, trả về view. Không có Form Request riêng — validation đặt trực tiếp trong controller.
- **Models (Eloquent)**: chứa quan hệ, casts, hằng số enum, accessor tính toán (ví dụ `Attendance::late_level`, `KpiPhase::is_overdue`), và một số logic tiện ích (`CompanySetting::get/put/pairs`).
- **Services** (`app/Services/AttendanceCloser.php`): nghiệp vụ chốt công cuối ngày dùng chung giữa controller và command.
- **Views** (`resources/views`): Blade templates + component tái sử dụng.

## 2.3. Cấu trúc thư mục dự án

| Thư mục | Vai trò |
|---------|---------|
| `app/Http/Controllers` | 8 controller nghiệp vụ + `Controller` cơ sở |
| `app/Models` | 8 model Eloquent |
| `app/Services` | `AttendanceCloser` — dịch vụ chốt công |
| `app/Console/Commands` | `CloseAttendanceDay` — lệnh Artisan chốt công |
| `app/Providers` | `AppServiceProvider` — định nghĩa Gate `admin` |
| `bootstrap/app.php` | Cấu hình middleware, routing, scheduling |
| `config/` | Cấu hình chuẩn Laravel (không có config tuỳ biến) |
| `database/migrations` | 16 migration định nghĩa schema |
| `database/seeders` | `DatabaseSeeder` — dữ liệu mẫu đầy đủ |
| `resources/views` | 28 Blade templates theo module + components |
| `routes/web.php` | Toàn bộ route ứng dụng |
| `routes/console.php` | Lịch biểu tác vụ (`attendance:close-day`) |
| `docker/`, `docker-compose.yml` | Hạ tầng container |
| `script/deploy.sh` | Kịch bản triển khai máy chủ vật lý |
| `html/` | Bản mẫu HTML tĩnh (PC + mobile) dùng tham chiếu thiết kế |

## 2.4. Luồng xử lý một request (điển hình)

Ví dụ: Người dùng bấm **Check-in** tại `/cham-cong/check-in`.

1. Trình duyệt gửi `POST /cham-cong/check-in` kèm CSRF token và session cookie.
2. Middleware `auth` xác thực phiên; nếu chưa đăng nhập → chuyển hướng `/login`.
3. Router ánh xạ tới `AttendanceController@checkin`.
4. Controller xác định hồ sơ nhân viên hiện tại (`user->employee` hoặc khớp email).
5. Áp dụng các quy tắc nghiệp vụ (đang nghỉ phép? đã tới giờ mở? đã check-in chưa? muộn/quá hạn?).
6. Ghi/ cập nhật bản ghi `attendances` (unique theo `employee_id + work_date`).
7. Trả về `redirect()->back()` kèm thông báo `status`/`error` (flash session).
8. View hiển thị lại trang chấm công với thông báo tương ứng.

## 2.5. Xác thực & phân quyền (tóm tắt kiến trúc)

- **Guard**: `web` (phiên, cookie), model `App\Models\User`.
- **Gate**: định nghĩa trong `AppServiceProvider`:

```php
Gate::define('admin', fn (User $user) => $user->isSuperAdmin());
```

- **Áp dụng quyền** theo nhiều tầng:
  1. Middleware route `can:admin` cho các thao tác ghi quản trị.
  2. Kiểm tra trong controller (`isSuperAdmin()`, `abort_unless(...)`) để phân tách phạm vi dữ liệu.
  3. Chỉ thị Blade `@can('admin')` để ẩn/hiện phần tử giao diện.
- **Không** có Policy class và **không** có middleware tuỳ biến trong `app/Http/Middleware`.

Chi tiết đầy đủ xem [04 — Phân quyền & vai trò](04-phan-quyen-vai-tro.md).

## 2.6. Tác vụ nền & lịch biểu

| Thành phần | Kích hoạt | Chức năng |
|------------|-----------|-----------|
| `AttendanceCloser` (Service) | Gọi tự động ở đầu `DashboardController@index` và `AttendanceController@index` (self-gated ~1 lần/ngày qua `attendance_closed_through`) | Đánh dấu "vắng mặt" cho nhân viên không chấm công; xử lý "quên check-out" cho các ngày đã qua |
| `attendance:close-day` (Command) | Lịch biểu `daily at 23:30` (`routes/console.php`) | Bọc `AttendanceCloser::run()` để chạy chủ động cuối ngày |

Cơ chế **self-gated**: mốc `attendance_closed_through` lưu ngày đã chốt gần nhất; mỗi lần chạy chỉ xử lý các ngày còn thiếu, giới hạn truy hồi tối đa 31 ngày, bỏ qua cuối tuần và người đang nghỉ phép đã duyệt.

## 2.7. Hạ tầng & triển khai

### Docker (khuyến nghị)

```bash
docker compose up -d --build   # dựng app (PHP-FPM) + nginx + mysql
# Ứng dụng chạy tại http://localhost:8090
```

### Triển khai máy chủ vật lý (`script/deploy.sh`)

Kịch bản thực hiện: `git pull` → `composer install` → `php artisan migrate` → tối ưu cache (config/route/view). **Không** tự động seed dữ liệu.

### Biến môi trường quan trọng (`.env.example`)

```
APP_NAME="Dylan HRM"
APP_URL=http://localhost:8090
APP_LOCALE=vi
APP_TIMEZONE=Asia/Ho_Chi_Minh
DB_CONNECTION=mysql   DB_HOST=mysql   DB_DATABASE=hrm
SESSION_DRIVER=database   CACHE_STORE=database   QUEUE_CONNECTION=database
MAIL_MAILER=log
```

## 2.8. Ràng buộc kiến trúc

- Không có tầng API tách biệt; toàn bộ tương tác qua web routes trả HTML.
- Validation nằm trong controller (chưa dùng Form Request) — cần lưu ý khi mở rộng để tái sử dụng quy tắc.
- Phụ thuộc CDN cho CSS/Font/Icon → cần Internet phía client; môi trường ngoại tuyến cần thay bằng asset cục bộ.
- Email cấu hình `log` → mọi luồng cần gửi mail (nếu có) chỉ ghi vào log.
