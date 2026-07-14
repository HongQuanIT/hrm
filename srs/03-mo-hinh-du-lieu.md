# 03 — Mô hình dữ liệu

## 3.1. Sơ đồ quan hệ thực thể (ERD)

```
                         ┌───────────────┐
                         │   users       │
                         │  (tài khoản)  │
                         └──────┬────────┘
                                │ 1
                                │ 0..1  (employees.user_id)
                         ┌──────▼────────┐
        head_employee_id │  employees    │ manager_id (tự tham chiếu)
        ┌────────────────┤  (nhân viên)  │◄────────┐
        │        1        └──┬───┬───┬───┘         │
┌───────▼───────┐           │   │   │   └──────────┘
│ departments   │ 1       N │   │   │ N
│ (phòng ban)   ├───────────┘   │   └──────────────┐
└──────┬────────┘               │                  │
       │ 1                       │ N               │ N
       │ N               ┌───────▼──────┐   ┌───────▼─────────┐
┌──────▼──────┐          │ attendances  │   │ leave_requests  │
│    kpis     │          │ (chấm công)  │   │  (nghỉ phép)    │
│ (mục tiêu)  │          └──────────────┘   └─────────────────┘
└──────┬──────┘
       │ 1
       │ N        assignee_employee_id
┌──────▼────────┐   ┌──────────────► employees
│  kpi_phases   │───┘
│  (giai đoạn)  │
└───────────────┘

┌───────────────────┐
│ company_settings  │  (key/value độc lập — cấu hình toàn hệ thống)
└───────────────────┘
```

### Danh sách quan hệ

| Quan hệ | Loại | Khóa | Hành vi xóa |
|---------|------|------|-------------|
| `users` — `employees` | 1 — 0..1 | `employees.user_id` | `nullOnDelete` |
| `departments` — `employees` | 1 — N | `employees.department_id` | `nullOnDelete` |
| `employees` — `employees` (quản lý) | 1 — N | `employees.manager_id` | `nullOnDelete` |
| `departments` — `employees` (trưởng phòng) | 1 — 1 | `departments.head_employee_id` | `nullOnDelete` |
| `employees` — `attendances` | 1 — N | `attendances.employee_id` | `cascadeOnDelete` |
| `employees` — `leave_requests` | 1 — N | `leave_requests.employee_id` | `cascadeOnDelete` |
| `departments` — `kpis` | 1 — N | `kpis.department_id` | `nullOnDelete` |
| `employees` — `kpis` (chủ trì) | 1 — N | `kpis.owner_employee_id` | `nullOnDelete` |
| `kpis` — `kpi_phases` | 1 — N | `kpi_phases.kpi_id` | `cascadeOnDelete` |
| `employees` — `kpi_phases` (phụ trách) | 1 — N | `kpi_phases.assignee_employee_id` | `nullOnDelete` |

## 3.2. Từ điển dữ liệu

### Bảng `users` — Tài khoản đăng nhập

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `name` | string | not null | Tên hiển thị |
| `email` | string | unique | Email đăng nhập |
| `email_verified_at` | timestamp | nullable | Mốc xác minh (đặt sẵn khi tạo) |
| `password` | string | not null | Mật khẩu băm (bcrypt) |
| `role` | enum(`super_admin`,`user`) | default `user` | Vai trò hệ thống |
| `remember_token` | string | nullable | Token "ghi nhớ đăng nhập" |
| `timestamps` | | | `created_at`, `updated_at` |

Bảng phụ trợ do Laravel tạo: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### Bảng `employees` — Hồ sơ nhân viên

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `user_id` | bigint | FK→users, nullable | Tài khoản đăng nhập liên kết |
| `code` | string | unique | Mã nhân viên |
| `name` | string | not null | Họ tên |
| `email` | string | unique | Email công việc |
| `personal_email` | string | nullable | Email cá nhân |
| `phone` | string | nullable | Điện thoại |
| `gender` | enum(`male`,`female`,`other`) | nullable | Giới tính |
| `dob` | date | nullable | Ngày sinh |
| `national_id` | string | nullable | CMND/CCCD |
| `marital_status` | string | nullable | Tình trạng hôn nhân |
| `nationality` | string | default `Việt Nam` | Quốc tịch |
| `permanent_address` | string | nullable | Địa chỉ thường trú |
| `temporary_address` | string | nullable | Địa chỉ tạm trú |
| `department_id` | bigint | FK→departments, nullable | Phòng ban |
| `position` | string | nullable | Chức danh |
| `level` | string | nullable | Cấp bậc |
| `contract_type` | string | nullable | Loại hợp đồng |
| `join_date` | date | nullable | Ngày vào làm |
| `manager_id` | bigint | FK→employees, nullable | Quản lý trực tiếp |
| `status` | enum(`active`,`on_leave`,`resigned`) | default `active` | Trạng thái làm việc |
| `bank_name` | string | nullable | Ngân hàng |
| `bank_account` | string | nullable | Số tài khoản |
| `bank_holder` | string | nullable | Chủ tài khoản |
| `base_salary` | decimal(15,2) | nullable | Lương cơ bản (chỉ lưu trữ) |
| `lunch_allowance` | decimal(15,2) | nullable | Phụ cấp ăn trưa (chỉ lưu trữ) |
| `emergency_contact` | string | nullable | Liên hệ khẩn cấp |
| `skills` | json | nullable | Danh sách kỹ năng (mảng) |
| `timestamps` | | | |

### Bảng `departments` — Phòng ban

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `name` | string | not null | Tên phòng ban |
| `code` | string | unique | Mã phòng ban |
| `head_employee_id` | bigint | FK→employees, nullable | Trưởng phòng |
| `head_name` | string | nullable | Tên trưởng phòng (đồng bộ hiển thị) |
| `color` | string | nullable | Màu nhận diện (UI) |
| `timestamps` | | | |

### Bảng `attendances` — Chấm công

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `employee_id` | bigint | FK→employees, cascade | Nhân viên |
| `work_date` | date | not null | Ngày làm việc |
| `check_in` | time | nullable | Giờ vào |
| `check_out` | time | nullable | Giờ ra |
| `total_minutes` | int | default 0 | Tổng phút làm việc |
| `late_minutes` | smallint | default 0 | Số phút đi muộn |
| `status` | enum | default `on_time` | `on_time`,`late`,`absent`,`leave`,`working`,`missing_checkout` |
| `note` | string | nullable | Ghi chú tự động/thủ công |
| `timestamps` | | | |
| — | | **unique(`employee_id`,`work_date`)** | Mỗi nhân viên 1 bản ghi/ngày |

Thuộc tính tính toán (model): `total_hours` (giờ = phút/60), `late_level` (1–3 dựa trên ngưỡng cấu hình), `late_level_label`.

### Bảng `leave_requests` — Đơn nghỉ phép

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `employee_id` | bigint | FK→employees, cascade | Người xin nghỉ |
| `type` | enum | default `monthly` | `monthly`,`annual`,`sick`,`unpaid`,`maternity`,`remote` |
| `start_date` | date | not null | Ngày bắt đầu |
| `end_date` | date | not null | Ngày kết thúc |
| `days` | decimal(4,1) | default 1 | Số ngày nghỉ |
| `reason` | string | nullable | Lý do |
| `status` | enum | default `pending` | `pending`,`approved`,`rejected`,`cancelled` |
| `approver_name` | string | nullable | Tên người duyệt |
| `attachment` | string | nullable | Đính kèm (chưa sử dụng) |
| `timestamps` | | | |

### Bảng `kpis` — Mục tiêu KPI

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `name` | string | not null | Tên mục tiêu |
| `description` | text | nullable | Mô tả |
| `department_id` | bigint | FK→departments, nullable | Phòng ban liên quan |
| `owner_employee_id` | bigint | FK→employees, nullable | Người chủ trì |
| `measure_type` | enum(`percent`,`count`,`milestone`) | default `percent` | Kiểu đo lường |
| `unit` | string | nullable | Đơn vị |
| `target_value` | decimal(15,2) | nullable | Giá trị mục tiêu |
| `current_value` | decimal(15,2) | default 0 | Giá trị hiện tại |
| `progress` | tinyint(0–100) | default 0 | Tiến độ % (tự tính theo giai đoạn) |
| `priority` | enum(`low`,`medium`,`high`) | default `medium` | Độ ưu tiên |
| `status` | enum(`on_track`,`in_progress`,`behind`,`done`) | default `in_progress` | Trạng thái |
| `deadline` | date | nullable | Hạn hoàn thành |
| `timestamps` | | | |

### Bảng `kpi_phases` — Giai đoạn của KPI

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `kpi_id` | bigint | FK→kpis, cascade | KPI cha |
| `name` | string | not null | Tên giai đoạn |
| `assignee_employee_id` | bigint | FK→employees, nullable | Người phụ trách |
| `deadline` | date | nullable | Hạn giai đoạn |
| `status` | enum(`pending`,`received`,`in_progress`,`done`) | default `pending` | Trạng thái quy trình |
| `received_at` | timestamp | nullable | Mốc nhận việc |
| `started_at` | timestamp | nullable | Mốc bắt đầu |
| `completed_at` | timestamp | nullable | Mốc hoàn thành |
| `timestamps` | | | |

> Trạng thái `received` và 3 cột mốc thời gian được bổ sung bởi migration `2024_05_02_000001_add_workflow_to_kpi_phases_table`.

Thuộc tính tính toán (model): `is_overdue`, `completed_late`.

### Bảng `company_settings` — Cấu hình hệ thống (key/value)

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `key` | string | unique | Khóa cấu hình |
| `value` | text/string | nullable | Giá trị |
| `timestamps` | | | |

Model cung cấp `CompanySetting::get($key, $default)`, `put($key, $value)`, `pairs()` với cache theo vòng đời request.

#### Các khóa cấu hình đã dùng

| Khóa | Ý nghĩa | Mặc định tham chiếu |
|------|---------|---------------------|
| `company_name` | Tên công ty | — |
| `tax_code` | Mã số thuế | — |
| `website` | Website | — |
| `address` | Địa chỉ | — |
| `leave_days_per_month` | Quỹ phép/tháng | 1 |
| `leave_days_per_year` | Quỹ phép/năm | 12 |
| `work_start_time` | Giờ bắt đầu làm | 08:00 |
| `work_end_time` | Giờ kết thúc làm | 17:30 |
| `checkin_open_time` | Giờ mở check-in | 07:00 |
| `checkin_deadline` | Hạn chót check-in (muộn hơn = vắng) | 10:00 |
| `checkout_deadline` | Hạn chốt check-out | 22:00 |
| `late_grace_minutes` | Ân hạn đi muộn (phút) | 5 |
| `late_level1_minutes` | Ngưỡng đi muộn mức 1 | — |
| `late_level2_minutes` | Ngưỡng đi muộn mức 2 | — |
| `attendance_closed_through` | (nội bộ) ngày đã chốt công gần nhất | — |

## 3.3. Danh mục giá trị enum (data domain)

| Thực thể.Trường | Giá trị | Nhãn tiếng Việt (tham chiếu) |
|-----------------|---------|------------------------------|
| users.role | `super_admin` / `user` | Super Admin / Người dùng |
| employees.status | `active`/`on_leave`/`resigned` | Đang làm / Nghỉ phép / Đã nghỉ việc |
| employees.gender | `male`/`female`/`other` | Nam / Nữ / Khác |
| attendances.status | `on_time`/`late`/`absent`/`leave`/`working`/`missing_checkout` | Đúng giờ / Đi muộn / Vắng mặt / Nghỉ phép / Đang làm việc / Quên check-out |
| leave_requests.type | `monthly`/`annual`/`sick`/`unpaid`/`maternity`/`remote` | Nghỉ phép tháng / Phép năm / Nghỉ ốm / Không lương / Thai sản / Làm từ xa |
| leave_requests.status | `pending`/`approved`/`rejected`/`cancelled` | Chờ duyệt / Đã duyệt / Từ chối / Đã huỷ |
| kpis.measure_type | `percent`/`count`/`milestone` | Phần trăm / Số lượng / Cột mốc |
| kpis.priority | `low`/`medium`/`high` | Thấp / Trung bình / Cao |
| kpis.status | `on_track`/`in_progress`/`behind`/`done` | Đúng tiến độ / Đang làm / Chậm / Hoàn thành |
| kpi_phases.status | `pending`/`received`/`in_progress`/`done` | Chờ / Đã nhận / Đang làm / Hoàn thành |

## 3.4. Dữ liệu khởi tạo (Seeder)

`DatabaseSeeder` tạo bộ dữ liệu mẫu đầy đủ theo thứ tự:

1. **Cấu hình công ty** — thông tin + chính sách chấm công/nghỉ phép.
2. **5 phòng ban** — `DEV-01`, `HRM-02`, `SAL-05`, `FIN-03`, `DES-04`.
3. **13 nhân viên** — 1 admin (`admin@HRM.vn`) + 12 nhân viên với hồ sơ đầy đủ.
4. **13 tài khoản** — mỗi nhân viên một tài khoản; admin có vai trò `super_admin`; mật khẩu mặc định `password`.
5. **Trưởng phòng** — gán qua `head_employee_id`.
6. **Chấm công 30 ngày** — cho từng nhân viên (bỏ cuối tuần, có mẫu đi muộn/vắng/nghỉ).
7. **Đơn nghỉ phép** — 1–3 đơn/nhân viên với trạng thái/loại đa dạng.
8. **5 KPI** — kèm giai đoạn và người phụ trách.

## 3.5. Toàn vẹn dữ liệu — ghi chú

- Xoá **nhân viên** sẽ **cascade** xoá `attendances` và `leave_requests` của họ; đồng thời controller xoá luôn tài khoản `users` liên kết (trong transaction) với các ràng buộc an toàn (không xoá chính mình, không xoá Super Admin cuối cùng).
- Xoá **phòng ban** đặt `department_id`/`head_employee_id` liên quan về `null` (không cascade).
- Xoá **KPI** sẽ **cascade** xoá toàn bộ `kpi_phases`.
- Ràng buộc `unique(employee_id, work_date)` đảm bảo idempotency khi chốt công.
