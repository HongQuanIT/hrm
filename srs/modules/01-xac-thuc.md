# M01 — Xác thực & Phiên

## 1. Mục tiêu

Cho phép người dùng đăng nhập vào hệ thống bằng email/mật khẩu, duy trì phiên làm việc và đăng xuất an toàn. Là cổng vào bắt buộc cho mọi chức năng nghiệp vụ.

## 2. Phạm vi

- Đăng nhập (email + mật khẩu, tuỳ chọn "ghi nhớ").
- Đăng xuất (huỷ phiên).
- Bảo vệ toàn bộ route nghiệp vụ bằng middleware `auth`.

**Ngoài phạm vi**: đăng ký tự do, xác minh email, quên mật khẩu qua email (hạ tầng bảng có sẵn nhưng chưa có giao diện).

## 3. Tác nhân

- **Khách**: truy cập trang đăng nhập.
- **Super Admin / Người dùng**: đăng nhập, đăng xuất.

## 4. Yêu cầu chức năng

| Mã | Yêu cầu |
|----|--------|
| FR-M01-01 | Hiển thị form đăng nhập tại `GET /login` cho khách (middleware `guest`). |
| FR-M01-02 | Xác thực đăng nhập tại `POST /login` với `email` và `password`; hỗ trợ tuỳ chọn "ghi nhớ đăng nhập" (`remember`). |
| FR-M01-03 | Đăng nhập thành công → tạo phiên, tái sinh session ID, chuyển hướng về Dashboard (`/`). |
| FR-M01-04 | Đăng nhập thất bại → trả lỗi xác thực gắn với trường email, giữ lại giá trị nhập (trừ mật khẩu). |
| FR-M01-05 | Đăng xuất tại `POST /logout` → huỷ phiên, tái sinh CSRF token, chuyển hướng về `/login`. |
| FR-M01-06 | Mọi route ngoài `/login` yêu cầu phiên hợp lệ; khách truy cập → chuyển hướng `/login`. |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M01-01 | Xác thực dùng guard `web` (phiên/cookie), model `App\Models\User`. |
| BR-M01-02 | Mật khẩu được lưu dạng băm; kiểm tra qua cơ chế `Auth::attempt`. |
| BR-M01-03 | Sau đăng nhập, session được tái sinh để chống cố định phiên (session fixation). |
| BR-M01-04 | Vai trò (`role`) quyết định trải nghiệm sau đăng nhập (dashboard admin vs cá nhân). |

## 6. Luồng xử lý

### 6.1. Đăng nhập
1. Khách mở `/login` → hiển thị form.
2. Nhập email, mật khẩu, (tuỳ chọn) tích "ghi nhớ".
3. Gửi `POST /login`.
4. Hệ thống xác thực thông tin đăng nhập.
5. Thành công → tái sinh phiên → chuyển hướng `/` (Dashboard).
6. Thất bại → hiển thị lỗi "thông tin đăng nhập không đúng", giữ email.

### 6.2. Đăng xuất
1. Người dùng bấm đăng xuất → `POST /logout`.
2. Huỷ đăng nhập, vô hiệu phiên, tái sinh token.
3. Chuyển hướng `/login`.

## 7. Giao diện liên quan

- `resources/views/auth/login.blade.php` — form đăng nhập.

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/AuthController.php` (`showLogin`, `login`, `logout`) |
| Model | `app/Models/User.php` |
| Route | `routes/web.php` (`login`, `login.attempt`, `logout`) |
| Middleware | `guest` (form), `auth` (bảo vệ toàn hệ thống) |

## 9. Trường hợp kiểm thử tiêu biểu

- Đăng nhập đúng thông tin → vào Dashboard theo vai trò.
- Đăng nhập sai mật khẩu → báo lỗi, không tạo phiên.
- Truy cập `/nhan-vien` khi chưa đăng nhập → chuyển hướng `/login`.
- Đăng xuất → không thể truy cập route `auth` cho tới khi đăng nhập lại.
- Tài khoản demo `admin@HRM.vn` / `password` → vào dashboard quản trị.
