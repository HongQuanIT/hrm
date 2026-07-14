# M07 — KPI & Hiệu suất

## 1. Mục tiêu

Quản lý mục tiêu hiệu suất (KPI) của công ty/phòng ban, chia nhỏ thành các **giai đoạn** có người phụ trách, và theo dõi tiến độ qua quy trình trạng thái với tính toán tự động.

## 2. Phạm vi

- CRUD KPI (Super Admin).
- Xem KPI theo phạm vi quyền.
- Quản lý giai đoạn: thêm, đồng bộ khi sửa KPI, cập nhật trạng thái theo quy trình.
- Tự tính tiến độ KPI theo tỷ lệ giai đoạn hoàn thành.

## 3. Tác nhân

- **Super Admin**: toàn quyền CRUD, xem tất cả KPI, thao tác mọi giai đoạn.
- **Người dùng**: xem KPI mình chủ trì hoặc được giao giai đoạn; thêm giai đoạn và cập nhật trạng thái giai đoạn mình phụ trách.

## 4. Yêu cầu chức năng

### 4.1. Danh sách & xem
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M07-01 | `GET /kpi`: liệt kê KPI theo quyền xem; hiển thị KPI trung bình công ty, trung bình theo phòng ban (tối đa 4), KPI dẫn đầu, biểu đồ xu hướng. | auth (theo phạm vi) |
| FR-M07-02 | `GET /kpi/{kpi}`: xem chi tiết KPI kèm phòng ban, chủ trì, các giai đoạn và người phụ trách. | auth (thành viên/admin) |

### 4.2. CRUD (Super Admin)
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M07-03 | `GET /kpi/create`, `POST /kpi`: tạo KPI kèm các giai đoạn ban đầu. | can:admin |
| FR-M07-04 | `GET /kpi/{kpi}/edit`, `PUT/PATCH /kpi/{kpi}`: sửa KPI và đồng bộ giai đoạn. | can:admin |
| FR-M07-05 | `DELETE /kpi/{kpi}`: xoá KPI (cascade xoá giai đoạn). | can:admin |

### 4.3. Giai đoạn
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M07-06 | `POST /kpi/{kpi}/giai-doan`: thêm giai đoạn (tên, người phụ trách, hạn), trạng thái khởi tạo `pending`. | thành viên KPI hoặc admin |
| FR-M07-07 | `PATCH /kpi/{kpi}/giai-doan/{phase}/trang-thai`: cập nhật trạng thái giai đoạn theo quy trình. | người phụ trách hoặc admin |
| FR-M07-08 | Sau mỗi thay đổi giai đoạn, **tự tính lại** tiến độ và trạng thái KPI. | hệ thống |

## 5. Quy tắc nghiệp vụ

### 5.1. Quyền truy cập
| Mã | Quy tắc |
|----|--------|
| BR-M07-01 | Super Admin xem/thao tác mọi KPI. |
| BR-M07-02 | Người dùng chỉ xem KPI mình **chủ trì** (`owner_employee_id`) hoặc **được giao** một giai đoạn; truy cập KPI khác → HTTP 403. |
| BR-M07-03 | Tài khoản không gắn hồ sơ nhân viên → không thấy KPI nào. |
| BR-M07-04 | Thêm giai đoạn: yêu cầu là thành viên KPI (chủ trì/phụ trách) hoặc admin. |
| BR-M07-05 | Cập nhật trạng thái giai đoạn: chỉ **người phụ trách giai đoạn đó** hoặc admin; ngược lại HTTP 403. |

### 5.2. Quy trình trạng thái giai đoạn
`pending` → `received` → `in_progress` → `done` (có thể lùi về trạng thái trước).

| Chuyển sang | Ghi mốc thời gian |
|-------------|-------------------|
| `received` | ghi `received_at` (nếu chưa có) |
| `in_progress` | đảm bảo `received_at`, `started_at` |
| `done` | đảm bảo `received_at`, `started_at`; ghi `completed_at` |
| khác `done` | xoá `completed_at` (mở lại giai đoạn đã xong) |

### 5.3. Tự tính tiến độ KPI (`refreshKpiProgress`)
| Mã | Quy tắc |
|----|--------|
| BR-M07-06 | `progress = round(số_giai_đoạn_done / tổng_giai_đoạn × 100)`. Nếu không có giai đoạn nào thì không đổi tiến độ. |
| BR-M07-07 | Nếu tất cả giai đoạn `done` → trạng thái KPI = `done`. |
| BR-M07-08 | Nếu tồn tại giai đoạn chưa `done` mà quá hạn (`deadline < hôm nay`) → trạng thái KPI = `behind`. |

### 5.4. Đồng bộ giai đoạn khi sửa KPI (`syncPhases`)
| Mã | Quy tắc |
|----|--------|
| BR-M07-09 | Giai đoạn có sẵn được cập nhật (giữ nguyên trạng thái & mốc thời gian mà người phụ trách đã đặt); giai đoạn mới được tạo với trạng thái `pending`. |
| BR-M07-10 | Giai đoạn bị gỡ khỏi form sẽ bị **xoá**. |

## 6. Ràng buộc dữ liệu (validation)

| Trường KPI | Quy tắc |
|-----------|---------|
| `name` | bắt buộc, ≤255 |
| `measure_type` | bắt buộc, ∈ {percent, count, milestone} |
| `priority` | bắt buộc, ∈ {low, medium, high} |
| `status` | bắt buộc, ∈ {on_track, in_progress, behind, done} |
| `progress` | 0–100 |
| `department_id`, `owner_employee_id` | tồn tại |
| `target_value`, `current_value` | số |

| Trường giai đoạn | Quy tắc |
|------------------|---------|
| `name` | bắt buộc, ≤255 |
| `assignee_employee_id` | tồn tại (tuỳ chọn) |
| `deadline` | ngày (tuỳ chọn) |

## 7. Giao diện liên quan

- `kpis/index.blade.php` — danh sách + trung bình phòng ban.
- `kpis/show.blade.php` — chi tiết + thao tác quy trình giai đoạn.
- `kpis/create.blade.php`, `kpis/edit.blade.php`, `kpis/_form.blade.php`.
- `components/phase-action.blade.php` — nút chuyển trạng thái giai đoạn.

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/KpiController.php` |
| Model | `app/Models/Kpi.php`, `app/Models/KpiPhase.php` |
| Route | `routes/web.php` (`kpis.*`, `kpis.phases.*`) |

## 9. Trường hợp kiểm thử tiêu biểu

- Tạo KPI với 4 giai đoạn → tiến độ 0%.
- Đánh dấu 1/4 giai đoạn `done` → tiến độ 25%.
- Hoàn thành tất cả giai đoạn → KPI `done`, tiến độ 100%.
- Giai đoạn quá hạn chưa xong → KPI `behind`.
- User được giao giai đoạn cập nhật trạng thái → thành công; cập nhật giai đoạn người khác → 403.
- User không liên quan mở KPI → 403.
- Mở lại giai đoạn đã `done` → `completed_at` bị xoá, tiến độ giảm.
