1. Stack yêu cầu
Laravel (latest stable)
Blade template engine
Tailwind CSS hoặc giữ nguyên CSS hiện có (ưu tiên không phá UI)
Alpine.js hoặc vanilla JS (nếu UI cần tương tác nhẹ)
MySQL
Vite build system
2. Mục tiêu chuyển đổi
Giữ nguyên 100% UI/UX từ HTML hiện có (PC + Mobile responsive)
Không redesign
Tách layout thành hệ thống Blade chuẩn Laravel
3. Cấu trúc cần tạo
Layout system
resources/views/layouts/app.blade.php (layout chính)
resources/views/layouts/partials/sidebar.blade.php
resources/views/layouts/partials/navbar.blade.php
resources/views/layouts/partials/header.blade.php
4. Important rules
Không phá layout HTML gốc
Không viết lại UI từ đầu, nếu cần sửa phải để tôi confirm
Chỉ refactor thành Blade + Laravel structure
Code phải clean, tách component rõ ràng
Ưu tiên maintainability
5. Output mong muốn
Project Laravel chạy được ngay
UI giống 100% bản HTML gốc
CRUD đầy đủ cho HRM modules
Có seed data demo
