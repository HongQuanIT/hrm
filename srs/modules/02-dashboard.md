# M02 — Bảng điều khiển (Dashboard)

## 1. Mục tiêu

Cung cấp trang tổng quan ngay sau đăng nhập, hiển thị số liệu và tác vụ quan trọng phù hợp vai trò: **quản trị viên** thấy bức tranh toàn công ty; **nhân viên** thấy dữ liệu cá nhân.

## 2. Phạm vi

- Dashboard quản trị (Super Admin).
- Dashboard cá nhân (Người dùng / nhân viên).
- Kích hoạt chốt công tự động trước khi hiển thị số liệu.

## 3. Tác nhân

- **Super Admin** → dashboard toàn công ty.
- **Người dùng** → dashboard cá nhân.

## 4. Yêu cầu chức năng

### 4.1. Chung
| Mã | Yêu cầu |
|----|--------|
| FR-M02-01 | Truy cập `GET /` sau đăng nhập; tự động phân nhánh giao diện theo vai trò. |
| FR-M02-02 | Trước khi tính số liệu, gọi `AttendanceCloser::run()` để đồng bộ trạng thái vắng mặt/quên check-out. |

### 4.2. Dashboard quản trị (`adminDashboard`)
| Mã | Yêu cầu |
|----|--------|
| FR-M02-03 | Hiển thị tổng số nhân viên, số đang làm việc, số đang nghỉ phép hôm nay, số đi muộn hôm nay, KPI trung bình toàn công ty. |
| FR-M02-04 | Biểu đồ 7 ngày gần nhất (T2–CN): số nhân viên có mặt mỗi ngày (tính các trạng thái `on_time`, `late`, `working`). |
| FR-M02-05 | Tỷ lệ nghỉ phép tháng hiện tại: số ngày phép đã duyệt / quỹ ước tính (số nhân viên × 12). |
| FR-M02-06 | Danh sách 3 nhân viên mới nhất (theo `join_date`) kèm phòng ban. |
| FR-M02-07 | Số đơn nghỉ đang chờ duyệt và 3 đơn nghỉ gần nhất. |

### 4.3. Dashboard cá nhân (`personalDashboard`)
| Mã | Yêu cầu |
|----|--------|
| FR-M02-08 | Xác định nhân viên hiện tại theo email tài khoản. |
| FR-M02-09 | Hiển thị bản ghi chấm công hôm nay, số ngày công trong tháng, ngày công chuẩn, số lần đi muộn trong tháng. |
| FR-M02-10 | Hiển thị quỹ phép tháng: đã dùng (loại `monthly`, trạng thái đã duyệt + chờ duyệt) và số dư còn lại (cho phép âm). |
| FR-M02-11 | Danh sách KPI của cá nhân (chủ trì hoặc được giao giai đoạn) và tiến độ trung bình. |
| FR-M02-12 | Biểu đồ giờ làm 7 ngày gần nhất của cá nhân. |
| FR-M02-13 | Đơn nghỉ gần nhất (tối đa 4) và số đơn đang chờ duyệt của cá nhân. |
| FR-M02-14 | Xử lý an toàn khi tài khoản chưa gắn hồ sơ nhân viên (hiển thị giá trị mặc định, không lỗi). |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M02-01 | "Ngày công chuẩn" = số ngày trong tháng − `leave_days_per_month`. |
| BR-M02-02 | Quỹ phép tháng cá nhân chỉ tính đơn loại `monthly` ở trạng thái `approved` + `pending`; số dư có thể âm nếu nghỉ vượt quỹ. |
| BR-M02-03 | Ngày "có mặt" gồm các trạng thái chấm công `on_time`, `late`, `working`. |
| BR-M02-04 | KPI cá nhân = KPI mà nhân viên chủ trì (`owner_employee_id`) hoặc được giao ít nhất một giai đoạn. |

## 6. Giao diện liên quan

- `resources/views/dashboard.blade.php` — dashboard quản trị.
- `resources/views/dashboard-personal.blade.php` — dashboard cá nhân.

## 7. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/DashboardController.php` (`index`, `adminDashboard`, `personalDashboard`) |
| Service | `app/Services/AttendanceCloser.php` |
| Route | `routes/web.php` (`dashboard`) |

## 8. Trường hợp kiểm thử tiêu biểu

- Super Admin đăng nhập → thấy số liệu toàn công ty, biểu đồ 7 ngày.
- Nhân viên đăng nhập → thấy số liệu cá nhân, KPI của mình.
- Vào Dashboard sau 23:30 (hoặc qua hạn check-in) → các ngày thiếu chấm công được chốt "vắng mặt".
- Tài khoản không gắn nhân viên → dashboard cá nhân hiển thị giá trị 0/mặc định, không lỗi.
