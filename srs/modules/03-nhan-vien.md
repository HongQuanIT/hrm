# M03 — Quản lý nhân viên

## 1. Mục tiêu

Quản lý toàn bộ vòng đời hồ sơ nhân viên (thông tin cá nhân, công việc, ngân hàng/lương) và **tài khoản đăng nhập gắn liền**, đảm bảo mỗi nhân viên có một tài khoản để tự phục vụ.

## 2. Phạm vi

- Danh sách nhân viên có tìm kiếm và lọc.
- Xem hồ sơ chi tiết.
- Thêm / sửa / xoá nhân viên (chỉ Super Admin).
- Tự động tạo/đồng bộ tài khoản `users` khi tạo/sửa nhân viên.

## 3. Tác nhân

- **Super Admin**: toàn quyền CRUD.
- **Người dùng**: chỉ xem danh sách và hồ sơ (không thấy nút thêm/sửa/xoá).

## 4. Yêu cầu chức năng

| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M03-01 | Liệt kê nhân viên tại `GET /nhan-vien`, phân trang 10/trang, kèm phòng ban và tổng số. | auth |
| FR-M03-02 | Tìm kiếm theo `q` khớp tên/mã/email. | auth |
| FR-M03-03 | Lọc theo phòng ban (`department`) và trạng thái (`status`). | auth |
| FR-M03-04 | Xem hồ sơ chi tiết tại `GET /nhan-vien/{employee}` kèm phòng ban và quản lý trực tiếp. | auth |
| FR-M03-05 | Hiển thị form thêm tại `GET /nhan-vien/create`, gợi ý mã kế tiếp dạng `PP-XXXX`. | can:admin |
| FR-M03-06 | Tạo nhân viên tại `POST /nhan-vien`; đồng thời tạo/lấy tài khoản `users` theo email. | can:admin |
| FR-M03-07 | Hiển thị form sửa tại `GET /nhan-vien/{employee}/edit` (loại chính nhân viên khỏi danh sách quản lý). | can:admin |
| FR-M03-08 | Cập nhật nhân viên tại `PUT/PATCH /nhan-vien/{employee}`; đồng bộ tài khoản liên kết (tên, email, tuỳ chọn đặt lại mật khẩu). | can:admin |
| FR-M03-09 | Xoá nhân viên tại `DELETE /nhan-vien/{employee}`; xoá luôn tài khoản liên kết (trong transaction). | can:admin |
| FR-M03-10 | Trường `skills` nhận vào dạng mảng hoặc chuỗi phân tách dấu phẩy, lưu dạng JSON mảng. | can:admin |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M03-01 | Mã nhân viên (`code`) và email công việc (`email`) là **duy nhất**. |
| BR-M03-02 | Khi tạo nhân viên: tìm hoặc tạo `users` theo email; nếu tạo mới, mật khẩu = mật khẩu nhập vào hoặc mặc định `"password"`, vai trò `user`, đánh dấu email đã xác minh. |
| BR-M03-03 | Khi sửa nhân viên: đồng bộ `name`, `email` sang tài khoản; nếu nhân viên chưa có tài khoản thì tạo mới; nếu nhập mật khẩu mới thì Super Admin đặt lại mật khẩu tài khoản đó. |
| BR-M03-04 | Mật khẩu (nếu nhập) tối thiểu 6 ký tự. |
| BR-M03-05 | **Không** xoá nhân viên gắn với tài khoản đang đăng nhập (tránh tự khoá). |
| BR-M03-06 | **Không** xoá nhân viên nếu tài khoản đó là Super Admin **duy nhất** còn lại. |
| BR-M03-07 | Xoá nhân viên → cascade xoá chấm công và đơn nghỉ liên quan; đặt các tham chiếu khác về null theo ràng buộc khóa ngoại. |
| BR-M03-08 | Trạng thái nhân viên thuộc {`active`, `on_leave`, `resigned`}; nhân viên `resigned` bị bỏ qua khi chốt công. |

## 6. Ràng buộc dữ liệu (validation)

| Trường | Quy tắc |
|--------|---------|
| `code` | bắt buộc, ≤50, duy nhất |
| `name` | bắt buộc, ≤255 |
| `email` | bắt buộc, định dạng email, ≤255, duy nhất |
| `personal_email` | tuỳ chọn, email |
| `gender` | ∈ {male, female, other} |
| `dob`, `join_date` | ngày hợp lệ |
| `department_id`, `manager_id` | tồn tại trong bảng tương ứng |
| `status` | bắt buộc, ∈ {active, on_leave, resigned} |
| `base_salary`, `lunch_allowance` | số ≥ 0 |
| `password` | tuỳ chọn, ≥6 |

## 7. Luồng xử lý (Tạo nhân viên)

1. Super Admin mở `/nhan-vien/create` → form với mã gợi ý.
2. Nhập thông tin cá nhân, công việc, ngân hàng, (tuỳ chọn) mật khẩu.
3. Gửi `POST /nhan-vien`.
4. Validate dữ liệu; parse `skills`.
5. Trong transaction: `firstOrCreate` tài khoản theo email → gán `user_id` → tạo `employees`.
6. Chuyển hướng danh sách kèm thông báo (nêu rõ mật khẩu mặc định hay tự đặt).

## 8. Giao diện liên quan

- `employees/index.blade.php` — danh sách + tìm kiếm/lọc.
- `employees/show.blade.php` — hồ sơ chi tiết.
- `employees/create.blade.php`, `employees/edit.blade.php`.
- `employees/_form.blade.php` — form dùng chung (thông tin cá nhân, công việc, ngân hàng/lương).

## 9. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/EmployeeController.php` |
| Model | `app/Models/Employee.php`, `app/Models/User.php` |
| Route | `routes/web.php` (`employees.*`) |

## 10. Trường hợp kiểm thử tiêu biểu

- Tạo nhân viên mới → có bản ghi `employees` và tài khoản `users` liên kết, đăng nhập được với mật khẩu mặc định.
- Tạo trùng `code` hoặc `email` → lỗi validation.
- Sửa email nhân viên → email tài khoản liên kết cập nhật theo.
- Đặt lại mật khẩu khi sửa → tài khoản đăng nhập bằng mật khẩu mới.
- Xoá chính mình → bị chặn.
- Xoá Super Admin cuối cùng → bị chặn.
