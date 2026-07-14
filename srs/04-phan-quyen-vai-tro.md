# 04 — Phân quyền & vai trò

## 4.1. Mô hình vai trò

Hệ thống dùng mô hình **hai vai trò** đơn giản, lưu ở cột `users.role`:

| Vai trò | Giá trị DB | Nhãn | Phạm vi |
|---------|-----------|------|---------|
| Super Admin | `super_admin` | "Super Admin" | Toàn quyền quản trị: nhân viên, phòng ban, phê duyệt, cấu hình, phân quyền |
| Người dùng | `user` | "Người dùng" | Tự phục vụ: chấm công, nghỉ phép, KPI được giao, đổi mật khẩu, xem hồ sơ |

Hằng số trong model: `User::ROLE_SUPER_ADMIN`, `User::ROLE_USER`. Kiểm tra qua `User::isSuperAdmin()`.

## 4.2. Cổng quyền (Gate)

Định nghĩa duy nhất trong `AppServiceProvider::boot()`:

```php
Gate::define('admin', fn (User $user) => $user->isSuperAdmin());
```

Không có Policy class, không có middleware tuỳ biến. Quyền được áp dụng ở 3 tầng:

1. **Middleware route** `can:admin` — chặn truy cập các thao tác ghi quản trị ở tầng định tuyến.
2. **Kiểm tra trong controller** — `isSuperAdmin()` để phân tách phạm vi dữ liệu (ví dụ admin thấy toàn công ty, user chỉ thấy dữ liệu của mình) và `abort_unless(...)` để chặn thao tác trái phép.
3. **Chỉ thị Blade** `@can('admin')` — ẩn/hiện nút và mục menu trên giao diện.

## 4.3. Ma trận quyền theo chức năng

Ký hiệu: ✅ được phép · 🔒 chỉ dữ liệu của bản thân/được giao · ❌ không được phép.

| Chức năng | Super Admin | Người dùng |
|-----------|:-----------:|:----------:|
| Đăng nhập / đăng xuất | ✅ | ✅ |
| Xem dashboard | ✅ (toàn công ty) | ✅ (cá nhân) |
| Xem danh sách/hồ sơ nhân viên | ✅ | ✅ (chỉ xem) |
| Thêm/sửa/xoá nhân viên | ✅ | ❌ |
| Check-in / check-out | ✅ 🔒 | ✅ 🔒 |
| Xem lịch sử chấm công | ✅ (tất cả) | 🔒 (của mình) |
| Gửi đơn nghỉ phép | ✅ | ✅ |
| Huỷ đơn nghỉ của mình (khi chờ duyệt) | ✅ 🔒 | ✅ 🔒 |
| Duyệt/từ chối/huỷ đơn (bất kỳ) | ✅ | ❌ |
| Xoá đơn nghỉ | ✅ | ❌ |
| Xem lịch nghỉ | ✅ (tất cả) | 🔒 (của mình) |
| Xem danh sách KPI | ✅ (tất cả) | 🔒 (chủ trì/được giao) |
| Tạo/sửa/xoá KPI | ✅ | ❌ |
| Thêm giai đoạn KPI | ✅ | 🔒 (KPI mình tham gia) |
| Cập nhật trạng thái giai đoạn | ✅ | 🔒 (giai đoạn mình phụ trách) |
| Cài đặt hệ thống (công ty, chính sách) | ✅ | ❌ |
| Quản lý phòng ban | ✅ | ❌ |
| Phân vai trò người dùng | ✅ | ❌ |
| Đổi mật khẩu bản thân | ✅ | ✅ |

## 4.4. Bản đồ route ↔ middleware

| Nhóm | Route tiêu biểu | Bảo vệ |
|------|-----------------|--------|
| Khách | `GET/POST /login` | `guest` |
| Công khai | `POST /logout` | — |
| Đăng nhập | `/`, `/tai-khoan`, `/cham-cong*`, `/nghi-phep*`, `/kpi*`, `/nhan-vien` (index/show) | `auth` |
| Quản trị (ghi) | `nhan-vien` create/store/edit/update/destroy; `nghi-phep` status/destroy; `kpi` create/store/edit/update/destroy; toàn bộ `cai-dat*` | `auth` + `can:admin` |
| Quản trị (trong controller) | `kpi/giai-doan*` (kiểm tra `abort_unless` theo phụ trách) | `auth` + kiểm tra thủ công |

## 4.5. Quy tắc an toàn phân quyền (Business Rules)

| Mã | Quy tắc |
|----|--------|
| BR-SEC-01 | Không cho phép người dùng **tự hạ** vai trò Super Admin của chính mình. |
| BR-SEC-02 | Hệ thống **luôn còn ít nhất một** Super Admin — không thể hạ quyền/xoá Super Admin cuối cùng. |
| BR-SEC-03 | Không thể xoá nhân viên gắn với **tài khoản đang đăng nhập**. |
| BR-SEC-04 | User thường chỉ truy cập KPI mà họ **chủ trì** hoặc **được giao giai đoạn**; truy cập KPI khác → HTTP 403. |
| BR-SEC-05 | User chỉ huỷ được **đơn nghỉ của chính mình** và khi đơn ở trạng thái `pending`; ngược lại → HTTP 403. |
| BR-SEC-06 | Chỉ **người phụ trách giai đoạn** hoặc Super Admin được cập nhật trạng thái giai đoạn KPI; ngược lại → HTTP 403. |
| BR-SEC-07 | Tài khoản **không gắn hồ sơ nhân viên** không thấy dữ liệu người khác (dùng `employee_id = 0` để cô lập) và không thực hiện được chấm công. |

## 4.6. Liên kết Tài khoản ↔ Nhân viên

- Mỗi hồ sơ nhân viên có thể gắn một tài khoản qua `employees.user_id`.
- Các chức năng tự phục vụ xác định nhân viên hiện tại theo thứ tự: `auth()->user()->employee` → nếu không có thì tìm `Employee` khớp `email`.
- Khi tạo/sửa nhân viên, hệ thống tự tạo/đồng bộ tài khoản `users` tương ứng (xem module Nhân viên).
