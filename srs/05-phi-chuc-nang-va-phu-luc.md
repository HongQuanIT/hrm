# 05 — Yêu cầu phi chức năng & Phụ lục

## 5.1. Yêu cầu phi chức năng (NFR)

### Hiệu năng
| Mã | Yêu cầu |
|----|--------|
| NFR-PERF-01 | Danh sách dùng phân trang (nhân viên 10/trang, chấm công 15/trang, lịch sử nghỉ 10/trang) để giới hạn dữ liệu tải. |
| NFR-PERF-02 | Tác vụ chốt công self-gated tối đa 1 lần/ngày, truy hồi tối đa 31 ngày để tránh xử lý toàn bộ lịch sử. |
| NFR-PERF-03 | `CompanySetting` cache theo vòng đời request để giảm truy vấn lặp. |

### Bảo mật
| Mã | Yêu cầu |
|----|--------|
| NFR-SEC-01 | Mật khẩu lưu dạng băm (bcrypt); không lưu mật khẩu thô. |
| NFR-SEC-02 | Chống CSRF cho mọi form POST/PUT/PATCH/DELETE (cơ chế mặc định Laravel). |
| NFR-SEC-03 | Tái sinh phiên khi đăng nhập, huỷ phiên khi đăng xuất (chống session fixation). |
| NFR-SEC-04 | Phân quyền nhiều tầng (middleware `can:admin`, kiểm tra controller, chỉ thị Blade). |
| NFR-SEC-05 | Cô lập dữ liệu: người dùng chỉ truy cập dữ liệu của mình/được giao; vi phạm → HTTP 403. |

### Khả dụng & giao diện
| Mã | Yêu cầu |
|----|--------|
| NFR-UX-01 | Giao diện tiếng Việt, responsive (có bản mobile với thanh điều hướng dưới). |
| NFR-UX-02 | Thông báo kết quả thao tác qua flash message (`status` / `error`). |
| NFR-UX-03 | URL slug tiếng Việt, dễ đọc (`/nhan-vien`, `/cham-cong`, `/nghi-phep`, `/kpi`, `/cai-dat`). |

### Bảo trì & vận hành
| Mã | Yêu cầu |
|----|--------|
| NFR-OPS-01 | Chuẩn hoá code style bằng Laravel Pint. |
| NFR-OPS-02 | Triển khai qua Docker Compose hoặc `script/deploy.sh`. |
| NFR-OPS-03 | Múi giờ máy chủ `Asia/Ho_Chi_Minh` để tính giờ chấm công chính xác. |
| NFR-OPS-04 | Health check tại `/up`. |

### Quốc tế hoá
| Mã | Yêu cầu |
|----|--------|
| NFR-I18N-01 | Locale `vi`, fallback `en`; Faker `vi_VN` cho dữ liệu mẫu. |

## 5.2. Phụ lục A — Bản đồ route đầy đủ

| Method | URI | Route name | Middleware |
|--------|-----|------------|------------|
| GET | `/login` | `login` | guest |
| POST | `/login` | `login.attempt` | guest |
| POST | `/logout` | `logout` | — |
| GET | `/` | `dashboard` | auth |
| GET | `/tai-khoan` | `account.edit` | auth |
| PUT | `/tai-khoan/mat-khau` | `account.password` | auth |
| GET | `/nhan-vien` | `employees.index` | auth |
| GET | `/nhan-vien/create` | `employees.create` | auth, can:admin |
| POST | `/nhan-vien` | `employees.store` | auth, can:admin |
| GET | `/nhan-vien/{employee}` | `employees.show` | auth |
| GET | `/nhan-vien/{employee}/edit` | `employees.edit` | auth, can:admin |
| PUT/PATCH | `/nhan-vien/{employee}` | `employees.update` | auth, can:admin |
| DELETE | `/nhan-vien/{employee}` | `employees.destroy` | auth, can:admin |
| GET | `/cham-cong` | `attendance.index` | auth |
| POST | `/cham-cong/check-in` | `attendance.checkin` | auth |
| POST | `/cham-cong/check-out` | `attendance.checkout` | auth |
| GET | `/nghi-phep` | `leaves.index` | auth |
| GET | `/nghi-phep/lich` | `leaves.calendar` | auth |
| POST | `/nghi-phep` | `leaves.store` | auth |
| PATCH | `/nghi-phep/{leave}/huy` | `leaves.cancel` | auth |
| PATCH | `/nghi-phep/{leave}/trang-thai` | `leaves.status` | auth, can:admin |
| DELETE | `/nghi-phep/{leave}` | `leaves.destroy` | auth, can:admin |
| GET | `/kpi` | `kpis.index` | auth |
| GET | `/kpi/create` | `kpis.create` | auth, can:admin |
| POST | `/kpi` | `kpis.store` | auth, can:admin |
| GET | `/kpi/{kpi}` | `kpis.show` | auth |
| GET | `/kpi/{kpi}/edit` | `kpis.edit` | auth, can:admin |
| PUT/PATCH | `/kpi/{kpi}` | `kpis.update` | auth, can:admin |
| DELETE | `/kpi/{kpi}` | `kpis.destroy` | auth, can:admin |
| POST | `/kpi/{kpi}/giai-doan` | `kpis.phases.store` | auth (kiểm tra trong controller) |
| PATCH | `/kpi/{kpi}/giai-doan/{phase}/trang-thai` | `kpis.phases.status` | auth (kiểm tra trong controller) |
| GET | `/cai-dat` | `settings.index` | auth, can:admin |
| PUT | `/cai-dat` | `settings.update` | auth, can:admin |
| POST | `/cai-dat/phong-ban` | `settings.departments.store` | auth, can:admin |
| PUT | `/cai-dat/phong-ban/{department}` | `settings.departments.update` | auth, can:admin |
| DELETE | `/cai-dat/phong-ban/{department}` | `settings.departments.destroy` | auth, can:admin |
| PUT | `/cai-dat/nguoi-dung/{user}/vai-tro` | `settings.users.role` | auth, can:admin |
| GET | `/tai-chinh` | `finance.overview` | auth, can:admin |
| GET | `/tai-chinh/quy` | `finance.accounts.index` | auth, can:admin |
| POST | `/tai-chinh/quy` | `finance.accounts.store` | auth, can:admin |
| PUT | `/tai-chinh/quy/{account}` | `finance.accounts.update` | auth, can:admin |
| DELETE | `/tai-chinh/quy/{account}` | `finance.accounts.destroy` | auth, can:admin |
| POST | `/tai-chinh/quy/{account}/nap-tien` | `finance.accounts.deposit` | auth, can:admin |
| POST | `/tai-chinh/quy/{account}/dieu-chinh` | `finance.accounts.adjust` | auth, can:admin |
| GET | `/tai-chinh/danh-muc` | `finance.categories.index` | auth, can:admin |
| POST | `/tai-chinh/danh-muc` | `finance.categories.store` | auth, can:admin |
| PUT | `/tai-chinh/danh-muc/{category}` | `finance.categories.update` | auth, can:admin |
| DELETE | `/tai-chinh/danh-muc/{category}` | `finance.categories.destroy` | auth, can:admin |
| GET | `/tai-chinh/giao-dich` | `finance.transactions.index` | auth, can:admin |
| POST | `/tai-chinh/giao-dich` | `finance.transactions.store` | auth, can:admin |
| PUT | `/tai-chinh/giao-dich/{transaction}` | `finance.transactions.update` | auth, can:admin |
| DELETE | `/tai-chinh/giao-dich/{transaction}` | `finance.transactions.destroy` | auth, can:admin |
| GET | `/tai-chinh/cong-no` | `finance.debts.index` | auth, can:admin |
| POST | `/tai-chinh/cong-no` | `finance.debts.store` | auth, can:admin |
| PUT | `/tai-chinh/cong-no/{debt}` | `finance.debts.update` | auth, can:admin |
| POST | `/tai-chinh/cong-no/{debt}/thanh-toan` | `finance.debts.pay` | auth, can:admin |
| PATCH | `/tai-chinh/cong-no/{debt}/huy` | `finance.debts.cancel` | auth, can:admin |
| DELETE | `/tai-chinh/cong-no/{debt}` | `finance.debts.destroy` | auth, can:admin |

## 5.3. Phụ lục B — Tác vụ nền / lệnh Artisan

| Lệnh / lịch | Tần suất | Chức năng |
|-------------|----------|-----------|
| `attendance:close-day` | daily 23:30 | Chốt công cuối ngày (vắng mặt / quên check-out) |

## 5.4. Phụ lục C — Giới hạn hiện tại

- Không có tầng API (không `routes/api.php`).
- Không xử lý lương/bảng lương; chỉ lưu trữ dữ liệu lương/ngân hàng.
- Không có module tuyển dụng, báo cáo/xuất dữ liệu.
- Trường `attachment` của đơn nghỉ tồn tại nhưng chưa có luồng tải tệp.
- Không có đặt lại mật khẩu qua email (chỉ có hạ tầng bảng).
- Không có nhật ký kiểm toán (audit log), không đa công ty (multi-tenant).
- Phần lớn validation nằm trong controller; riêng module Tài chính (M10) đã dùng Form Request.
- Phụ thuộc CDN cho CSS/Font/Icon (cần Internet phía client).
- `README.md` và một số test tham chiếu route cũ (`/nhan-su`, `/luong-thuong`…) không còn tồn tại.

## 5.5. Phụ lục D — Lộ trình mở rộng đề xuất

| Ưu tiên | Hạng mục | Ghi chú |
|---------|----------|---------|
| Cao | Module Lương/Bảng lương | Tận dụng `base_salary`, `lunch_allowance`, chấm công, nghỉ phép sẵn có |
| Cao | Tải tệp đính kèm đơn nghỉ | Kích hoạt trường `attachment` |
| Trung bình | Báo cáo & xuất Excel/PDF | Chấm công, nghỉ phép, KPI |
| Trung bình | Đặt lại mật khẩu qua email | Dùng `password_reset_tokens` + cấu hình SMTP |
| Trung bình | Nhật ký kiểm toán | Ghi lại thao tác quản trị nhạy cảm |
| Thấp | Tầng API / ứng dụng mobile | Bổ sung `routes/api.php`, token auth |
| Thấp | Vai trò/quyền chi tiết hơn | Thay mô hình 2 vai trò bằng RBAC linh hoạt |

## 5.6. Phụ lục E — Tài khoản & dữ liệu demo

- Đăng nhập quản trị: `admin@HRM.vn` / `password`.
- Seeder tạo 5 phòng ban, 13 nhân viên (kèm 13 tài khoản), 30 ngày chấm công, đơn nghỉ, 5 KPI và dữ liệu tài chính mẫu (2 quỹ, danh mục, giao dịch, công nợ).
- Khởi tạo dữ liệu: `php artisan migrate --seed`.
