# Báo cáo sửa lỗi & cải tiến chức năng (F01–F16) — Dylan HRM

Tài liệu này liệt kê chi tiết từng vấn đề đã phát hiện trong bản rà soát mã nguồn và cách xử lý tương ứng, kèm danh sách file thay đổi để bạn review lại.

> Sau khi review, cần chạy migration (và seed lại nếu muốn) — xem mục [Hướng dẫn áp dụng](#hướng-dẫn-áp-dụng) ở cuối.

---

## Tổng quan mức độ

| Mã | Mức độ | Module | Tình trạng |
|----|--------|--------|-----------|
| F01 | 🔴 Nghiêm trọng | Nghỉ phép | ✅ Đã sửa |
| F02 | 🟠 Cao | Dashboard | ✅ Đã sửa |
| F03 | 🟠 Cao | KPI | ✅ Đã sửa |
| F04 | 🟠 Cao | Dashboard/chung | ✅ Đã sửa |
| F05 | 🟠 Cao | Xác thực | ✅ Đã sửa |
| F06 | 🟠 Cao | Nhân viên/Bảo mật | ✅ Đã sửa |
| F07 | 🟡 Trung bình | Nghỉ phép | ✅ Đã sửa |
| F08 | 🟡 Trung bình | KPI | ✅ Đã sửa |
| F09 | 🟡 Trung bình | Chấm công | ✅ Đã sửa |
| F10 | 🟡 Trung bình | Toàn hệ thống | ✅ Đã sửa |
| F11 | 🟡 Trung bình | Bảo mật | ✅ Đã sửa |
| F12 | 🟡 Trung bình | KPI | ✅ Đã sửa |
| F13 | 🟡 Trung bình | Dashboard | ✅ Đã sửa |
| F14 | 🟢 Thấp | Tài khoản | ✅ Đã sửa |
| F15 | 🟢 Thấp | Chấm công | ✅ Đã sửa |
| F16 | 🟢 Thấp | Nghỉ phép | ✅ Đã sửa |

---

## F01 — Đơn nghỉ bị gán nhầm nhân viên (Nghiêm trọng)

**Vấn đề:** Trong `LeaveController::store`, nếu tài khoản đăng nhập không gắn hồ sơ nhân viên, hệ thống fallback về `Employee::first()` hoặc `employee_id = 1`. Hệ quả: đơn nghỉ chui vào hồ sơ người khác — sai dữ liệu và rủi ro bảo mật.

**Cách sửa:** Bỏ hoàn toàn fallback. Nếu không xác định được nhân viên, trả lỗi và dừng (giống cơ chế chấm công).

```php
$employee = $this->currentEmployee();
if (! $employee) {
    return back()->with('error', 'Tài khoản chưa gắn với hồ sơ nhân viên nên không thể tạo đơn nghỉ phép.')->withInput();
}
$employeeId = $employee->id;
```

**File:** `app/Http/Controllers/LeaveController.php`

---

## F02 — Dashboard đếm "đang nghỉ hôm nay" không nhất quán (Cao)

**Vấn đề:** `adminDashboard` đếm số người nghỉ dựa trên `employee.status = on_leave` (trạng thái tĩnh, phải cập nhật thủ công), không khớp với dữ liệu đơn nghỉ thực tế.

**Cách sửa:** Đếm số nhân viên **khác nhau** có đơn nghỉ đã duyệt bao phủ ngày hôm nay — đồng bộ với logic ở module nghỉ phép.

```php
$onLeaveToday = LeaveRequest::where('status', 'approved')
    ->whereDate('start_date', '<=', today())
    ->whereDate('end_date', '>=', today())
    ->distinct('employee_id')->count('employee_id');
```

**File:** `app/Http/Controllers/DashboardController.php`

---

## F03 — Biểu đồ xu hướng KPI dùng dữ liệu giả (Cao)

**Vấn đề:** Trend 6 tháng của KPI là số liệu hardcode, không phản ánh dữ liệu thật.

**Cách sửa:** Truy vấn số **giai đoạn (phase) hoàn thành** theo từng tháng trong 6 tháng gần nhất, chỉ tính các KPI mà người dùng được phép xem (`accessibleKpiIds`).

**File:** `app/Http/Controllers/KpiController.php`

---

## F04 — Cách xác định "nhân viên hiện tại" không nhất quán (Cao)

**Vấn đề:** Mỗi controller tự tìm nhân viên theo một cách khác nhau (`Employee::where('email', ...)`, quan hệ `user->employee`, hoặc fallback), dễ trả về nhân viên sai.

**Cách sửa:** Chuẩn hoá bằng một helper `currentEmployee()` dùng chung logic: ưu tiên quan hệ `user->employee`, sau đó fallback theo email.

```php
private function currentEmployee(): ?Employee
{
    $user = auth()->user();
    return $user->employee ?? Employee::where('email', $user->email)->first();
}
```

**File:** `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/LeaveController.php`, `app/Http/Controllers/AccountController.php`

---

## F05 — Không giới hạn số lần đăng nhập sai (Cao)

**Vấn đề:** Route đăng nhập không có throttle → dễ bị dò mật khẩu (brute force).

**Cách sửa:** Thêm middleware `throttle:6,1` (tối đa 6 lần/phút) cho route xử lý đăng nhập.

```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('login.attempt');
```

**File:** `routes/web.php`

---

## F06 — Mật khẩu mặc định & không buộc đổi lần đầu (Cao)

**Vấn đề:** Khi tạo nhân viên không nhập mật khẩu, hệ thống đặt cứng `"password"` và không buộc đổi → tài khoản mở toang.

**Cách sửa:**
- Sinh **mật khẩu tạm ngẫu nhiên** (`Str::password(12)`) khi admin không đặt mật khẩu, hiển thị 1 lần cho admin bàn giao.
- Thêm cột `must_change_password` (mặc định `false`) cho tài khoản tạo bằng mật khẩu tạm.
- Middleware `EnsurePasswordChanged` chặn mọi thao tác (trừ trang đổi mật khẩu & logout) cho tới khi người dùng đổi mật khẩu.
- Sau khi đổi mật khẩu thành công, cờ được gỡ tự động.

**File:**
- Migration `2024_05_03_000001_add_must_change_password_to_users_table.php`
- `app/Models/User.php` (cast `must_change_password`)
- `app/Http/Middleware/EnsurePasswordChanged.php` (mới) + đăng ký trong `bootstrap/app.php`
- `app/Http/Controllers/EmployeeController.php` (sinh mật khẩu tạm + set cờ)
- `app/Http/Controllers/AccountController.php` (gỡ cờ khi đổi mật khẩu)

---

## F07 — Kiểm tra đơn nghỉ chưa đầy đủ (Trung bình)

**Vấn đề:** Không kiểm tra trùng khoảng ngày với đơn khác, không kiểm tra quỹ phép năm.

**Cách sửa:** Trong `store`:
- Chặn nếu khoảng ngày **trùng** một đơn `pending`/`approved` của cùng nhân viên.
- Với loại `annual`, kiểm tra tổng ngày đã dùng/đang chờ + đơn mới không vượt `leave_days_per_year`.

**File:** `app/Http/Controllers/LeaveController.php`

---

## F08 — Đồng bộ phase KPI xoá cứng & tiến độ không cập nhật (Trung bình)

**Vấn đề:** Khi sync phase, phase bị **xoá cứng** (mất lịch sử); tiến độ KPI không được tính lại sau khi thay đổi phase.

**Cách sửa:**
- Thêm **soft delete** cho `KpiPhase` (giữ lịch sử qua `deleted_at`).
- Gọi `refreshKpiProgress($kpi)` sau khi `syncPhases` ở cả `store` và `update`.

**File:**
- Migration `2024_05_03_000002_add_soft_deletes_to_kpi_phases_table.php`
- `app/Models/KpiPhase.php` (trait `SoftDeletes`)
- `app/Http/Controllers/KpiController.php`

---

## F09 — `AttendanceCloser::run()` gọi ở mỗi request (Trung bình)

**Vấn đề:** Chốt công tự động chạy mỗi lần tải Dashboard/Chấm công → truy vấn lặp lại tốn kém.

**Cách sửa:** Bổ sung `runThrottled()` dùng khoá cache (`Cache::add`, 5 phút) để chỉ chạy 1 lần trong khoảng thời gian ngắn; các controller gọi `runThrottled()` thay vì `run()`. Scheduler (nếu có) vẫn gọi trực tiếp `run()`.

**File:** `app/Services/AttendanceCloser.php`, `app/Http/Controllers/AttendanceController.php`, `app/Http/Controllers/DashboardController.php`

---

## F10 — Logic kiểm tra dữ liệu nằm rải trong controller (Trung bình)

**Vấn đề:** Rule validation viết trực tiếp trong controller → khó tái sử dụng, khó test, dễ trùng lặp.

**Cách sửa:** Tách sang các **Form Request** riêng, kèm `authorize()`:
- `StoreEmployeeRequest`, `UpdateEmployeeRequest`
- `KpiRequest`
- `StoreLeaveRequest`
- `UpdateCompanySettingsRequest`

**File:** `app/Http/Requests/*` (mới) + các controller tương ứng.

---

## F11 — Lỗ hổng mass assignment cho `role` (Trung bình)

**Vấn đề:** `role` nằm trong `fillable` của `User` → có thể bị gán qua mass assignment để tự nâng quyền.

**Cách sửa:** Bỏ `role` khỏi `fillable`; mọi nơi gán role đều phải set **tường minh qua property** (controller tạo nhân viên, cập nhật vai trò, seeder).

**File:** `app/Models/User.php`, `app/Http/Controllers/EmployeeController.php`, `app/Http/Controllers/SettingController.php`, `database/seeders/DatabaseSeeder.php`

---

## F12 — Danh sách KPI không phân trang (Trung bình)

**Vấn đề:** `KpiController::index` dùng `->get()` → tải toàn bộ KPI, chậm khi dữ liệu lớn.

**Cách sửa:** Chuyển sang `->paginate(10)` và thêm liên kết phân trang ở view.

**File:** `app/Http/Controllers/KpiController.php`, `resources/views/kpis/index.blade.php`

---

## F13 — Truy vấn N+1 trên Dashboard (Trung bình)

**Vấn đề:** Biểu đồ chấm công 7 ngày thực hiện 7 truy vấn riêng lẻ (mỗi ngày một query).

**Cách sửa:** Gộp thành **một truy vấn group-by ngày** rồi map kết quả ra 7 ngày, áp dụng cho cả `adminDashboard` và `personalDashboard`.

**File:** `app/Http/Controllers/DashboardController.php`

---

## F14 — Nhân viên không tự sửa được thông tin cá nhân (Thấp)

**Vấn đề:** Người dùng thường không có nơi tự cập nhật thông tin liên hệ; mọi thay đổi phải qua Super Admin.

**Cách sửa:** Thêm trang **Thông tin cá nhân** cho phép nhân viên tự sửa các trường an toàn (SĐT, email cá nhân, địa chỉ, liên hệ khẩn cấp). Các trường nhạy cảm (lương, phòng ban, chức danh, vai trò) vẫn do Super Admin quản lý.

**File:** `app/Http/Controllers/AccountController.php`, route `account.profile` trong `routes/web.php`, `resources/views/account/profile.blade.php`, liên kết ở `resources/views/account/edit.blade.php`

---

## F15 — Không xét ngày nghỉ lễ (Thấp)

**Vấn đề:** Chốt công tự động chỉ bỏ qua cuối tuần, không có khái niệm ngày lễ → nhân viên bị đánh vắng vào ngày lễ; ngày công chuẩn tính sai.

**Cách sửa:**
- Thêm bảng `holidays` + model `Holiday`.
- `AttendanceCloser` bỏ qua ngày nằm trong danh sách lễ.
- Ngày công chuẩn (Dashboard & Chấm công) trừ thêm số ngày lễ trong tháng.
- Thêm CRUD ngày lễ cho Super Admin ở trang Cài đặt (tab "Ngày nghỉ lễ").

**File:**
- Migration `2024_05_03_000003_create_holidays_table.php`
- `app/Models/Holiday.php` (mới)
- `app/Services/AttendanceCloser.php`
- `app/Http/Controllers/AttendanceController.php`, `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/SettingController.php` (store/destroy), routes `settings.holidays.*`
- `resources/views/settings/index.blade.php` (tab + section)

---

## F16 — Chưa hỗ trợ nghỉ nửa ngày (Thấp)

**Vấn đề:** Đơn nghỉ chỉ tính theo ngày nguyên, không thể xin nghỉ buổi sáng/chiều (0.5 công).

**Cách sửa:**
- Thêm cột `half_day` (`morning`/`afternoon`) cho `leave_requests`.
- Khi chọn nghỉ nửa ngày: buộc cùng 1 ngày, `days = 0.5`.
- UI: checkbox + chọn buổi trong form tạo đơn; hiển thị nhãn buổi trong danh sách.

**File:**
- Migration `2024_05_03_000004_add_half_day_to_leave_requests_table.php`
- `app/Models/LeaveRequest.php` (`half_day` + nhãn)
- `app/Http/Controllers/LeaveController.php`
- `app/Http/Requests/StoreLeaveRequest.php`
- `resources/views/leaves/index.blade.php`

---

## Hướng dẫn áp dụng

```bash
# 1. Chạy migration bổ sung các cột/bảng mới
php artisan migrate

# 2. (Tuỳ chọn) Seed lại dữ liệu mẫu — LƯU Ý: sẽ reset dữ liệu
php artisan migrate:fresh --seed

# 3. Xoá cache cấu hình nếu cần
php artisan optimize:clear
```

### Migration mới được thêm
- `2024_05_03_000001_add_must_change_password_to_users_table.php` (F06)
- `2024_05_03_000002_add_soft_deletes_to_kpi_phases_table.php` (F08)
- `2024_05_03_000003_create_holidays_table.php` (F15)
- `2024_05_03_000004_add_half_day_to_leave_requests_table.php` (F16)

### Lưu ý vận hành
- **F06:** Tài khoản nhân viên tạo mới bằng mật khẩu tạm sẽ bị buộc đổi mật khẩu ở lần đăng nhập đầu tiên. Admin cần bàn giao mật khẩu tạm hiển thị sau khi tạo.
- **F11:** Vì `role` không còn mass-assignable, mọi seeder/script tự viết cần set `->role` qua property.
- **F15:** Sau khi thêm ngày lễ, ngày công chuẩn của tháng tương ứng sẽ giảm; các ngày lễ đã "chốt vắng" trước đó (nếu có) không tự huỷ — cần xử lý thủ công nếu muốn.
