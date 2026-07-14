# M09 — Tài khoản cá nhân

## 1. Mục tiêu

Cho phép người dùng đã đăng nhập tự đổi mật khẩu tài khoản của mình một cách an toàn.

## 2. Phạm vi

- Trang tài khoản cá nhân.
- Đổi mật khẩu (yêu cầu xác thực mật khẩu hiện tại).

**Ngoài phạm vi**: chỉnh sửa thông tin hồ sơ cá nhân (do Super Admin quản lý qua module Nhân viên), ảnh đại diện, cấu hình 2FA.

## 3. Tác nhân

- **Super Admin / Người dùng**: đổi mật khẩu của chính mình.

## 4. Yêu cầu chức năng

| Mã | Yêu cầu |
|----|--------|
| FR-M09-01 | `GET /tai-khoan`: hiển thị trang tài khoản với form đổi mật khẩu. |
| FR-M09-02 | `PUT /tai-khoan/mat-khau`: đổi mật khẩu, yêu cầu nhập đúng mật khẩu hiện tại và xác nhận mật khẩu mới. |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M09-01 | Phải nhập đúng **mật khẩu hiện tại** (`current_password`); sai → báo lỗi "Mật khẩu hiện tại không đúng". |
| BR-M09-02 | Mật khẩu mới tối thiểu 6 ký tự và phải khớp với ô xác nhận (`password_confirmation`). |
| BR-M09-03 | Mật khẩu mới được lưu dạng băm. |

## 6. Ràng buộc dữ liệu (validation)

| Trường | Quy tắc |
|--------|---------|
| `current_password` | bắt buộc, phải khớp mật khẩu hiện tại |
| `password` | bắt buộc, tối thiểu 6, `confirmed` |

## 7. Giao diện liên quan

- `account/edit.blade.php` — form đổi mật khẩu.

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/AccountController.php` (`edit`, `updatePassword`) |
| Model | `app/Models/User.php` |
| Route | `routes/web.php` (`account.edit`, `account.password`) |

## 9. Trường hợp kiểm thử tiêu biểu

- Nhập đúng mật khẩu hiện tại + mật khẩu mới khớp xác nhận → đổi thành công.
- Nhập sai mật khẩu hiện tại → báo lỗi, không đổi.
- Mật khẩu mới < 6 ký tự hoặc không khớp xác nhận → lỗi validation.
- Sau khi đổi, đăng nhập lại bằng mật khẩu mới → thành công.
