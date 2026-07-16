# 01 — Tổng quan sản phẩm

## 1.1. Mục đích tài liệu

Tài liệu này đặc tả yêu cầu phần mềm cho hệ thống **Dylan HRM** — ứng dụng web quản trị nhân sự nội bộ cho doanh nghiệp vừa và nhỏ. Nội dung mô tả các chức năng, quy tắc nghiệp vụ, dữ liệu và ràng buộc mà hệ thống hiện đang hiện thực, làm cơ sở cho phát triển, kiểm thử, nghiệm thu và bảo trì.

## 1.2. Phạm vi sản phẩm

Dylan HRM số hoá các hoạt động nhân sự cốt lõi trong một công ty đơn lẻ (single-company):

- Quản lý **hồ sơ nhân viên** và **tài khoản đăng nhập** gắn liền.
- Quản lý **cơ cấu phòng ban** và trưởng phòng.
- **Chấm công** hằng ngày (check-in/check-out) với chính sách đi muộn, về sớm, quên chấm và chốt công tự động.
- **Nghỉ phép**: gửi đơn, phê duyệt, quản lý quỹ phép tháng/năm, xem lịch nghỉ.
- **KPI/Hiệu suất**: đặt mục tiêu, chia giai đoạn, giao việc và theo dõi tiến độ theo quy trình.
- **Bảng điều khiển** hai chế độ: tổng quan công ty (quản trị) và cá nhân (nhân viên).
- **Cài đặt hệ thống**: thông tin công ty, chính sách chấm công/nghỉ phép, phòng ban, vai trò người dùng.
- **Quản lý tài khoản cá nhân**: đổi mật khẩu.
- **Quản lý tài chính**: quỹ tiền công ty, thu/chi, góp vốn, công nợ; báo cáo tổng nạp / tổng chi / số dư hiện có (cho phép âm).

### Ngoài phạm vi (phiên bản hiện tại)

Các hạng mục sau **chưa** được hiện thực (xem thêm phần Lộ trình ở tài liệu 05):

- Tính lương/bảng lương (payroll) — hệ thống chỉ **lưu trữ** thông tin lương/ngân hàng, không xử lý tính toán (khoản chi lương có thể ghi nhận thủ công trong module Tài chính).
- Tuyển dụng, onboarding nâng cao.
- Báo cáo/xuất dữ liệu chuyên biệt (BI, export Excel/PDF).
- Cổng API cho ứng dụng ngoài (không có `routes/api.php`).
- Tải lên tệp đính kèm (trường `attachment` của đơn nghỉ tồn tại nhưng chưa dùng).
- Nhật ký kiểm toán (audit log), đa công ty/đa chi nhánh (multi-tenant).
- Đặt lại mật khẩu qua email (hạ tầng bảng tồn tại nhưng chưa có giao diện).

## 1.3. Đối tượng người dùng (tác nhân)

| Tác nhân | Mô tả | Cách nhận diện |
|----------|-------|----------------|
| **Super Admin** | Quản trị viên toàn quyền: quản lý nhân viên, phòng ban, phê duyệt nghỉ phép, cấu hình hệ thống, phân quyền. | `users.role = 'super_admin'` |
| **Người dùng** (nhân viên) | Nhân viên thường: tự chấm công, gửi đơn nghỉ, cập nhật trạng thái KPI được giao, đổi mật khẩu, xem hồ sơ. | `users.role = 'user'` |
| **Khách** | Người chưa đăng nhập, chỉ truy cập được trang đăng nhập. | Không có phiên |
| **Hệ thống** | Tác nhân tự động thực hiện chốt công cuối ngày (lịch biểu / self-gated). | Command / Service |

Mỗi tài khoản người dùng (`users`) thường được liên kết 1–1 với một hồ sơ nhân viên (`employees`) qua `employees.user_id`. Các chức năng tự phục vụ (chấm công, nghỉ phép, KPI cá nhân) phụ thuộc vào liên kết này; tài khoản không gắn hồ sơ nhân viên sẽ bị giới hạn.

## 1.4. Bối cảnh & mô hình vận hành

- Hệ thống là ứng dụng web server-rendered (Blade), truy cập qua trình duyệt.
- Triển khai nội bộ công ty (mặc định cổng `8090` qua Docker).
- Một công ty duy nhất; toàn bộ nhân viên và dữ liệu thuộc cùng một tổ chức.
- Giao diện tiếng Việt, múi giờ `Asia/Ho_Chi_Minh`.

## 1.5. Giả định & phụ thuộc

- Người dùng truy cập bằng trình duyệt hiện đại có JavaScript (dùng Tailwind CDN + JS thuần trong Blade).
- Máy chủ có PHP 8.3+, MySQL 8.4 và kết nối Internet để tải Tailwind/Fonts/Icons từ CDN.
- Chính sách chấm công/nghỉ phép được cấu hình đúng trong `company_settings` trước khi vận hành.
- Múi giờ máy chủ được đặt là `Asia/Ho_Chi_Minh` để tính giờ chấm công chính xác.

## 1.6. Ràng buộc

- **Kỹ thuật**: Laravel 13, PHP 8.3+, MySQL. Không dùng npm/Vite (frontend qua CDN).
- **Bảo mật**: Xác thực bằng phiên (session) trên guard `web`; phân quyền dựa trên Gate `admin`.
- **Ngôn ngữ/vùng**: Toàn bộ nhãn, thông báo bằng tiếng Việt; URL dùng slug tiếng Việt.
- **Dữ liệu**: Ràng buộc duy nhất `(employee_id, work_date)` cho chấm công; mã nhân viên và email nhân viên là duy nhất.

## 1.7. Thuật ngữ

| Thuật ngữ | Giải thích |
|-----------|------------|
| **HRM** | Human Resource Management — quản trị nhân sự. |
| **KPI** | Key Performance Indicator — chỉ số/mục tiêu hiệu suất. |
| **Giai đoạn (phase)** | Đầu việc con của một KPI, có người phụ trách và quy trình trạng thái riêng. |
| **Chốt công** | Quy trình tự động đánh dấu "vắng mặt"/"quên check-out" cho các ngày làm việc đã qua. |
| **Quỹ phép tháng** | Số ngày nghỉ phép có lương được cấp mỗi tháng (`leave_days_per_month`). |
| **Ân hạn đi muộn** | Số phút cho phép đến sau giờ vào làm mà vẫn tính đúng giờ (`late_grace_minutes`). |
| **Gate `admin`** | Cổng quyền Laravel trả về `true` khi người dùng là Super Admin. |
| **Self-gated** | Cơ chế tự giới hạn tần suất chạy tác vụ nền (tối đa 1 lần/ngày qua cột mốc `attendance_closed_through`). |

## 1.8. Tổng quan chức năng theo module

```
Dylan HRM
├── M01 Xác thực & Phiên          (đăng nhập/đăng xuất)
├── M02 Bảng điều khiển           (admin / cá nhân)
├── M03 Quản lý nhân viên         (CRUD + tài khoản)
├── M04 Phòng ban                 (CRUD + trưởng phòng)
├── M05 Chấm công                 (check-in/out, chốt công)
├── M06 Nghỉ phép                 (đơn, duyệt, quỹ, lịch)
├── M07 KPI & Hiệu suất           (mục tiêu, giai đoạn, tiến độ)
├── M08 Cài đặt hệ thống          (công ty, chính sách, vai trò)
├── M09 Tài khoản cá nhân         (đổi mật khẩu)
└── M10 Quản lý tài chính         (quỹ, thu/chi, góp vốn, công nợ, số dư)
```

Chi tiết từng module xem thư mục [`modules/`](modules/).
