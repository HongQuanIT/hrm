# Dylan HRM — Bộ tài liệu Đặc tả Yêu cầu Phần mềm (SRS)

Bộ tài liệu này mô tả đầy đủ, chi tiết hệ thống **Dylan HRM** — phần mềm quản trị nhân sự nội bộ được xây dựng trên Laravel 13. Tài liệu được biên soạn dựa trên mã nguồn hiện có (reverse-engineered), phản ánh đúng hành vi thực tế của hệ thống chứ không phải mong muốn lý thuyết.

## Mục đích

- Làm tài liệu tham chiếu cho đội phát triển, kiểm thử (QA) và bảo trì.
- Chuẩn hoá cách hiểu về nghiệp vụ, dữ liệu, phân quyền giữa các thành viên.
- Là nền tảng để lập kế hoạch mở rộng (lương, tuyển dụng, báo cáo…).

## Cấu trúc bộ tài liệu

| # | Tài liệu | Nội dung |
|---|----------|----------|
| 01 | [Tổng quan sản phẩm](01-tong-quan-san-pham.md) | Mục tiêu, phạm vi, đối tượng người dùng, thuật ngữ, giả định & ràng buộc |
| 02 | [Kiến trúc hệ thống](02-kien-truc-he-thong.md) | Công nghệ, mô hình phân lớp, luồng request, hạ tầng triển khai, tác vụ nền |
| 03 | [Mô hình dữ liệu](03-mo-hinh-du-lieu.md) | Sơ đồ ERD, mô tả bảng, từ điển dữ liệu, ràng buộc, danh mục enum |
| 04 | [Phân quyền & vai trò](04-phan-quyen-vai-tro.md) | Mô hình 2 vai trò, cổng quyền (Gate), ma trận quyền theo chức năng |
| 05 | [Yêu cầu phi chức năng & Phụ lục](05-phi-chuc-nang-va-phu-luc.md) | Hiệu năng, bảo mật, khả dụng, bản đồ route, giới hạn hiện tại, lộ trình |

## Đặc tả theo module

Mỗi module được đặc tả riêng trong thư mục [`modules/`](modules/), gồm: mục tiêu, phạm vi, tác nhân, yêu cầu chức năng (FR), quy tắc nghiệp vụ (BR), luồng xử lý, ràng buộc dữ liệu và ánh xạ mã nguồn.

| # | Module | Tài liệu | Mô tả ngắn |
|---|--------|----------|------------|
| M01 | Xác thực & Phiên | [modules/01-xac-thuc.md](modules/01-xac-thuc.md) | Đăng nhập, đăng xuất, quản lý phiên |
| M02 | Bảng điều khiển | [modules/02-dashboard.md](modules/02-dashboard.md) | Dashboard quản trị và dashboard cá nhân |
| M03 | Quản lý nhân viên | [modules/03-nhan-vien.md](modules/03-nhan-vien.md) | CRUD hồ sơ nhân viên gắn tài khoản đăng nhập |
| M04 | Phòng ban | [modules/04-phong-ban.md](modules/04-phong-ban.md) | CRUD phòng ban, gán trưởng phòng |
| M05 | Chấm công | [modules/05-cham-cong.md](modules/05-cham-cong.md) | Check-in/out, tính đi muộn, chốt công tự động |
| M06 | Nghỉ phép | [modules/06-nghi-phep.md](modules/06-nghi-phep.md) | Đơn nghỉ, phê duyệt, quỹ phép, lịch nghỉ |
| M07 | KPI & Hiệu suất | [modules/07-kpi.md](modules/07-kpi.md) | Mục tiêu KPI, giai đoạn, quy trình trạng thái |
| M08 | Cài đặt hệ thống | [modules/08-cai-dat.md](modules/08-cai-dat.md) | Thông tin công ty, chính sách chấm công, vai trò |
| M09 | Tài khoản cá nhân | [modules/09-tai-khoan.md](modules/09-tai-khoan.md) | Đổi mật khẩu |
| M10 | Quản lý tài chính | [modules/10-tai-chinh.md](modules/10-tai-chinh.md) | Quỹ tiền, thu/chi, góp vốn, công nợ, số dư (cho phép âm) |
| M11 | Lương / Bảng lương | [modules/11-luong.md](modules/11-luong.md) | Kỳ lương tháng, prorate theo ngày công + quota phép, phiếu lương, chi qua Tài chính, self-service |

## Quy ước ký hiệu

- **FR-Mxx-nn**: Yêu cầu chức năng (Functional Requirement) thứ `nn` của module `Mxx`.
- **BR-Mxx-nn**: Quy tắc nghiệp vụ (Business Rule).
- **Tác nhân**: `Super Admin` (quản trị viên toàn quyền), `Người dùng` (nhân viên thường), `Khách` (chưa đăng nhập), `Hệ thống` (tác vụ nền).
- Đường dẫn URL dùng slug tiếng Việt (ví dụ `/nhan-vien`, `/cham-cong`).

## Thông tin phiên bản

- Sản phẩm: **Dylan HRM**
- Nền tảng: Laravel `^13.8`, PHP `^8.3`, MySQL `8.4`
- Ngôn ngữ giao diện: Tiếng Việt (`vi`), múi giờ `Asia/Ho_Chi_Minh`
- Tài khoản demo: `admin@HRM.vn` / `password` (Super Admin)
- Phiên bản tài liệu: 1.0 — biên soạn từ mã nguồn hiện tại.
