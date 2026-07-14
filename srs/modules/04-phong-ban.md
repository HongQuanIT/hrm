# M04 — Phòng ban

## 1. Mục tiêu

Quản lý cơ cấu phòng ban của công ty và gán trưởng phòng, phục vụ phân loại nhân viên và tổng hợp KPI theo phòng ban.

## 2. Phạm vi

Quản lý phòng ban được đặt trong trang **Cài đặt hệ thống** (`/cai-dat`), gồm: thêm, sửa, xoá phòng ban và gán trưởng phòng. Chỉ Super Admin.

## 3. Tác nhân

- **Super Admin**: toàn quyền CRUD phòng ban.
- **Người dùng**: chỉ xem gián tiếp qua hồ sơ nhân viên/KPI (không truy cập trang cài đặt).

## 4. Yêu cầu chức năng

| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M04-01 | Hiển thị danh sách phòng ban trong `/cai-dat` kèm trưởng phòng và **số nhân viên** mỗi phòng. | can:admin |
| FR-M04-02 | Thêm phòng ban tại `POST /cai-dat/phong-ban` với tên, mã, (tuỳ chọn) trưởng phòng. | can:admin |
| FR-M04-03 | Sửa phòng ban tại `PUT /cai-dat/phong-ban/{department}`. | can:admin |
| FR-M04-04 | Xoá phòng ban tại `DELETE /cai-dat/phong-ban/{department}`. | can:admin |
| FR-M04-05 | Khi gán trưởng phòng, tự động ghi `head_name` theo tên nhân viên được chọn. | can:admin |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M04-01 | Mã phòng ban (`code`) là **duy nhất**. |
| BR-M04-02 | Trưởng phòng (`head_employee_id`) phải là nhân viên tồn tại; `head_name` được đồng bộ từ hồ sơ. |
| BR-M04-03 | Xoá phòng ban → các nhân viên/KPI tham chiếu bị đặt `department_id = null` (không cascade xoá). |

## 6. Ràng buộc dữ liệu (validation)

| Trường | Quy tắc |
|--------|---------|
| `name` | bắt buộc, ≤255 |
| `code` | bắt buộc, ≤50, duy nhất (bỏ qua chính nó khi sửa) |
| `head_employee_id` | tuỳ chọn, tồn tại trong `employees` |

## 7. Giao diện liên quan

- `settings/index.blade.php` — tab "Phòng ban".

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/SettingController.php` (`storeDepartment`, `updateDepartment`, `destroyDepartment`, `headName`) |
| Model | `app/Models/Department.php` |
| Route | `routes/web.php` (`settings.departments.*`) |

## 9. Trường hợp kiểm thử tiêu biểu

- Thêm phòng ban mới với mã duy nhất → hiển thị trong danh sách, số nhân viên = 0.
- Thêm phòng ban trùng mã → lỗi validation.
- Gán trưởng phòng → `head_name` hiển thị đúng tên.
- Xoá phòng ban đang có nhân viên → nhân viên chuyển về "không phòng ban" (`department_id` null).
