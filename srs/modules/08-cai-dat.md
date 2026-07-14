# M08 — Cài đặt hệ thống

## 1. Mục tiêu

Cho phép Super Admin cấu hình toàn bộ tham số vận hành: thông tin công ty, chính sách chấm công/nghỉ phép, quản lý phòng ban và phân vai trò người dùng.

## 2. Phạm vi

Trang cài đặt (`/cai-dat`) gồm 4 nhóm:
1. Thông tin công ty.
2. Chính sách giờ làm & chấm công, quỹ phép.
3. Quản lý phòng ban (chi tiết ở [M04](04-phong-ban.md)).
4. Phân vai trò người dùng.

Toàn bộ chỉ dành cho **Super Admin** (`can:admin`).

## 3. Tác nhân

- **Super Admin**: xem và chỉnh sửa tất cả cài đặt.

## 4. Yêu cầu chức năng

| Mã | Yêu cầu |
|----|--------|
| FR-M08-01 | `GET /cai-dat`: hiển thị cấu hình hiện tại (dạng key/value), danh sách phòng ban kèm số nhân viên, danh sách người dùng (Super Admin xếp trước), danh sách nhân viên để chọn trưởng phòng. |
| FR-M08-02 | `PUT /cai-dat`: lưu thông tin công ty và chính sách chấm công/nghỉ phép vào `company_settings`. |
| FR-M08-03 | Quản lý phòng ban: thêm/sửa/xoá (xem M04). |
| FR-M08-04 | `PUT /cai-dat/nguoi-dung/{user}/vai-tro`: gán vai trò `super_admin` hoặc `user` cho một tài khoản. |

### Các tham số cấu hình được lưu

| Nhóm | Khóa |
|------|------|
| Công ty | `company_name`, `tax_code`, `website`, `address` |
| Quỹ phép | `leave_days_per_month` (0–31), `leave_days_per_year` (0–365) |
| Giờ làm | `work_start_time`, `work_end_time` (H:i) |
| Chấm công | `checkin_open_time`, `checkin_deadline`, `checkout_deadline` (H:i) |
| Đi muộn | `late_grace_minutes` (0–120), `late_level1_minutes` (1–240), `late_level2_minutes` (1–480) |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M08-01 | `checkin_open_time` phải **sớm hơn** `work_start_time` (thông báo lỗi tiếng Việt nếu vi phạm). |
| BR-M08-02 | Giá trị thời gian định dạng `H:i`; các ngưỡng phút nằm trong khoảng cho phép. |
| BR-M08-03 | Không cho phép **tự hạ** vai trò Super Admin của chính mình. |
| BR-M08-04 | Hệ thống luôn còn **ít nhất một** Super Admin — không hạ quyền Super Admin cuối cùng. |
| BR-M08-05 | Cấu hình lưu dạng key/value trong `company_settings`; các module chấm công/nghỉ phép đọc trực tiếp qua `CompanySetting::get()`. |

## 6. Ràng buộc dữ liệu (validation)

| Trường | Quy tắc |
|--------|---------|
| `company_name`, `address` | tuỳ chọn, ≤255 |
| `tax_code` | tuỳ chọn, ≤50 |
| `leave_days_per_month` | số nguyên 0–31 |
| `leave_days_per_year` | số nguyên 0–365 |
| `work_start_time`, `work_end_time`, `checkin_deadline`, `checkout_deadline` | `H:i` |
| `checkin_open_time` | `H:i`, before `work_start_time` |
| `late_grace_minutes` | 0–120 |
| `late_level1_minutes` | 1–240 |
| `late_level2_minutes` | 1–480 |
| `role` (phân quyền) | ∈ {super_admin, user} |

## 7. Giao diện liên quan

- `settings/index.blade.php` — giao diện dạng tab: Thông tin công ty, Chính sách chấm công, Phòng ban, Phân quyền người dùng.

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/SettingController.php` (`index`, `update`, `storeDepartment`, `updateDepartment`, `destroyDepartment`, `updateUserRole`) |
| Model | `app/Models/CompanySetting.php`, `Department.php`, `User.php` |
| Route | `routes/web.php` (`settings.*`) |

## 9. Trường hợp kiểm thử tiêu biểu

- Lưu `checkin_open_time = 07:00` với `work_start_time = 08:00` → hợp lệ.
- Lưu `checkin_open_time = 08:30` với `work_start_time = 08:00` → lỗi (phải sớm hơn).
- Thay đổi `leave_days_per_month` → ảnh hưởng số dư phép và ngày công chuẩn.
- Gán vai trò Super Admin cho một nhân viên → nhân viên đó có quyền quản trị.
- Tự hạ quyền chính mình → bị chặn.
- Hạ quyền Super Admin cuối cùng → bị chặn.
