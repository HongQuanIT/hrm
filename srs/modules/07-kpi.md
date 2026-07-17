# M07 — KPI & Hiệu suất

## 1. Mục tiêu

Quản lý mục tiêu hiệu suất (KPI) của công ty/phòng ban, chia nhỏ thành các **giai đoạn** có người phụ trách, và theo dõi tiến độ qua quy trình trạng thái với tính toán tự động.

## 2. Phạm vi

- CRUD KPI (Super Admin).
- Xem KPI theo phạm vi quyền.
- Quản lý giai đoạn: thêm, đồng bộ khi sửa KPI, cập nhật trạng thái theo quy trình.
- Mỗi giai đoạn con quản lý như một "ticket/công việc": mô tả, độ ưu tiên, ngày bắt đầu, hạn chót, người phụ trách, **checklist** việc con và **bình luận** trao đổi.
- Trang chi tiết hiển thị **bảng Kanban 4 cột** (Chờ nhận / Đã nhận / Đang làm / Đã xong); **kéo–thả** thẻ để đổi trạng thái; bấm thẻ mở **drawer chi tiết**.
- Mô tả KPI soạn thảo trực quan bằng trình soạn thảo rich text (CKEditor), lưu HTML đã làm sạch.
- Đính kèm tài liệu ở **cấp KPI** (nằm trong thẻ "Thông tin KPI", tải lên khi lưu form; tải/xoá). Giai đoạn con không đính kèm tài liệu.
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
| FR-M07-03 | `GET /kpi/create`, `POST /kpi`: tạo KPI (thông tin cấp cao + tài liệu). Giai đoạn được thêm sau trên trang chi tiết. | can:admin |
| FR-M07-04 | `GET /kpi/{kpi}/edit`, `PUT/PATCH /kpi/{kpi}`: sửa **thông tin KPI cấp cao** (tên, mô tả, phòng ban, chủ trì, đo lường, ưu tiên, trạng thái, hạn, tài liệu). Form edit **không** quản lý/xoá giai đoạn — việc đó nằm ở trang chi tiết. | can:admin |
| FR-M07-05 | `DELETE /kpi/{kpi}`: xoá KPI (cascade xoá giai đoạn). | can:admin |

### 4.3. Giai đoạn
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M07-06 | `POST /kpi/{kpi}/giai-doan`: thêm giai đoạn (tên, mô tả, độ ưu tiên, người phụ trách, ngày bắt đầu, hạn), trạng thái khởi tạo `pending`. | thành viên KPI hoặc admin |
| FR-M07-07 | `PATCH /kpi/{kpi}/giai-doan/{phase}/trang-thai`: cập nhật trạng thái giai đoạn theo quy trình. Hỗ trợ **kéo–thả Kanban** (gọi bằng fetch, trả JSON). | người phụ trách hoặc admin |
| FR-M07-08 | Sau mỗi thay đổi giai đoạn, **tự tính lại** tiến độ và trạng thái KPI. | hệ thống |

### 4.4. Checklist & bình luận giai đoạn (Kanban)
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M07-13 | Trang `GET /kpi/{kpi}` hiển thị bảng **Kanban 4 cột** theo trạng thái; thẻ hiển thị ưu tiên, hạn, người phụ trách, tiến độ checklist, số bình luận. | auth (thành viên/admin) |
| FR-M07-14 | Kéo–thả thẻ giữa các cột → cập nhật trạng thái (như FR-M07-07). Thẻ không thuộc quyền thao tác không kéo được (`.nodrag`). | người phụ trách hoặc admin |
| FR-M07-15 | Bấm thẻ mở **drawer** chi tiết: mô tả, thao tác trạng thái, checklist, bình luận. | auth (thành viên/admin) |
| FR-M07-16 | `POST /kpi/{kpi}/giai-doan/{phase}/checklist`: thêm mục checklist. | người phụ trách hoặc admin |
| FR-M07-17 | `PATCH /kpi/{kpi}/giai-doan/{phase}/checklist/{item}`: tick/bỏ tick hoàn thành. | người phụ trách hoặc admin |
| FR-M07-18 | `DELETE /kpi/{kpi}/giai-doan/{phase}/checklist/{item}`: xoá mục checklist. | người phụ trách hoặc admin |
| FR-M07-19 | `POST /kpi/{kpi}/giai-doan/{phase}/binh-luan`: thêm bình luận (thuần văn bản). | thành viên KPI hoặc admin |
| FR-M07-20 | `PATCH /kpi/{kpi}/giai-doan/{phase}`: sửa thông tin giai đoạn (tên, mô tả, ưu tiên, người phụ trách, ngày) ngay trong drawer; **không** đụng tới trạng thái/mốc thời gian. | người phụ trách hoặc admin |
| FR-M07-21 | `DELETE /kpi/{kpi}/giai-doan/{phase}`: xoá (soft delete) một giai đoạn, có xác nhận trên giao diện. | can:admin |

### 4.5. Tài liệu đính kèm & mô tả trực quan
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M07-09 | Mô tả KPI và mô tả giai đoạn được soạn thảo; riêng mô tả KPI dùng CKEditor (rich text). Nội dung HTML được **làm sạch** (`mews/purifier`) trước khi lưu và khi render. | can:admin (KPI), thành viên (giai đoạn) |
| FR-M07-10 | Tài liệu đính kèm KPI tải lên qua chính form KPI (trường `attachments[]`, multipart) trong thẻ "Thông tin KPI", lưu khi `POST /kpi` hoặc `PUT /kpi/{kpi}`. | can:admin |
| FR-M07-11 | `DELETE /kpi/{kpi}/tai-lieu/{attachment}`: xoá tài liệu (phải thuộc KPI đó), xoá cả file vật lý. | thành viên KPI hoặc admin |
| FR-M07-12 | `GET /kpi/{kpi}`: hiển thị danh sách tài liệu KPI ở sidebar; bấm vào mở **modal xem trước dùng chung** (ảnh, PDF, văn bản/CSV xem trực tiếp; Office qua trình xem online khi máy chủ công khai, môi trường nội bộ thì gợi ý tải xuống) kèm nút mở tab mới / tải xuống. | auth (thành viên/admin) |

## 5. Quy tắc nghiệp vụ

### 5.1. Quyền truy cập
| Mã | Quy tắc |
|----|--------|
| BR-M07-01 | Super Admin xem/thao tác mọi KPI. |
| BR-M07-02 | Người dùng chỉ xem KPI mình **chủ trì** (`owner_employee_id`) hoặc **được giao** một giai đoạn; truy cập KPI khác → HTTP 403. |
| BR-M07-03 | Tài khoản không gắn hồ sơ nhân viên → không thấy KPI nào. |
| BR-M07-04 | Thêm giai đoạn: yêu cầu là thành viên KPI (chủ trì/phụ trách) hoặc admin. |
| BR-M07-05 | Cập nhật trạng thái giai đoạn (kể cả kéo–thả Kanban): chỉ **người phụ trách giai đoạn đó** hoặc admin; ngược lại HTTP 403. |
| BR-M07-14 | Thêm/tick/xoá **checklist**: chỉ người phụ trách giai đoạn hoặc admin. |
| BR-M07-15 | **Bình luận**: mọi thành viên KPI (chủ trì hoặc phụ trách một giai đoạn) và admin. Bình luận gắn `user_id` người đăng, không sửa/xoá (MVP). |

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

### 5.4. Quản lý giai đoạn (tách khỏi form KPI)
| Mã | Quy tắc |
|----|--------|
| BR-M07-09 | Giai đoạn được **quản lý hoàn toàn trên trang chi tiết** (thêm `storePhase`, sửa `updatePhase`, xoá `destroyPhase`, đổi trạng thái `updatePhaseStatus`). Form tạo/sửa KPI **không** đọc/ghi/xoá giai đoạn → loại bỏ hoàn toàn cơ chế đồng bộ theo lô cũ (`syncPhases`) vốn có thể xoá ngầm giai đoạn kèm checklist/bình luận. |
| BR-M07-10 | `updatePhase` chỉ sửa thông tin mô tả (tên, mô tả, ưu tiên, người phụ trách, ngày); **giữ nguyên** `status` và các mốc `received_at/started_at/completed_at`. Xoá giai đoạn dùng **soft delete** và chỉ Super Admin. |

### 5.5. Tài liệu & nội dung HTML
| Mã | Quy tắc |
|----|--------|
| BR-M07-11 | Mọi mô tả HTML (KPI & giai đoạn) được lọc bằng `mews/purifier` (`clean()`) khi lưu và khi hiển thị để chống XSS. |
| BR-M07-12 | Tài liệu đính kèm: tối đa 10MB; định dạng cho phép: pdf, doc(x), xls(x), ppt(x), csv, txt, ảnh (jpg/png/gif/webp/**svg**), zip/rar. Lưu ở disk `public` (`storage/app/public/attachments/kpi/{kpi_id}`). Lưu ý bảo mật: SVG có thể chứa mã script; modal xem trước dùng `<img>` nên không thực thi script, nhưng khi mở trực tiếp bằng trình duyệt vẫn tiềm ẩn XSS — chỉ nhận tệp từ nguồn tin cậy. |
| BR-M07-13 | Đính kèm dùng bảng đa hình `attachments` (`attachable_type`, `attachable_id`); MVP chỉ gắn ở cấp `Kpi`. Khi xoá phải kiểm tra tài liệu thuộc đúng KPI đang thao tác. |

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
| `description` | chuỗi (tuỳ chọn), làm sạch HTML |
| `priority` | ∈ {low, medium, high} (mặc định `medium`) |
| `assignee_employee_id` | tồn tại (tuỳ chọn) |
| `start_date` | ngày (tuỳ chọn) |
| `deadline` | ngày (tuỳ chọn) |

| Trường tài liệu | Quy tắc |
|-----------------|---------|
| `attachments` | mảng (tuỳ chọn) trong form KPI |
| `attachments.*` | file, ≤10MB, mimes: pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,gif,webp,svg,zip,rar |

| Trường checklist / bình luận | Quy tắc |
|------------------------------|---------|
| `title` (checklist) | bắt buộc, ≤255 |
| `body` (bình luận) | bắt buộc, ≤2000 |

## 7. Giao diện liên quan

- `kpis/index.blade.php` — danh sách + trung bình phòng ban.
- `kpis/show.blade.php` — chi tiết + **bảng Kanban kéo–thả (SortableJS)** + **drawer** chi tiết giai đoạn (mô tả, checklist, bình luận) + đính kèm KPI + render mô tả HTML.
- `kpis/create.blade.php`, `kpis/edit.blade.php`, `kpis/_form.blade.php` — form KPI cấp cao, CKEditor cho mô tả, khu tài liệu đính kèm (chỉ khi sửa). **Không** còn khối quản lý giai đoạn (chuyển sang trang chi tiết).
- `components/phase-action.blade.php` — nút chuyển trạng thái giai đoạn.
- `components/file-preview.blade.php` — modal xem trước tài liệu dùng chung (kích hoạt qua thuộc tính `data-preview` + `data-url/name/mime/ext`); hỗ trợ **phóng to/thu nhỏ** (nút −/%/+, phím Ctrl +/−/0, Ctrl + cuộn chuột) cho ảnh/PDF/văn bản bằng `transform: scale` kèm bù kích thước "stage" để cuộn xem toàn bộ (tương thích mọi trình duyệt kể cả Firefox); dùng ở trang chi tiết và form KPI.

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/KpiController.php` (`storePhase`, `updatePhase`, `destroyPhase`, `updatePhaseStatus`, `storeAttachment`, `destroyAttachment`, `saveUploadedAttachments`, `cleanKpiData`, `addChecklistItem`, `toggleChecklistItem`, `deleteChecklistItem`, `addComment`, `authorizePhaseAction`, `backToPhase`). Đã **gỡ** `syncPhases`. |
| Model | `app/Models/Kpi.php`, `app/Models/KpiPhase.php`, `app/Models/Attachment.php`, `app/Models/PhaseChecklistItem.php`, `app/Models/PhaseComment.php` |
| Route | `routes/web.php` (`kpis.*`, `kpis.phases.*`, `kpis.phases.checklist.*`, `kpis.phases.comments.*`, `kpis.attachments.*`) |
| Migration | `..._add_ticket_fields_to_kpi_phases_table.php`, `..._create_attachments_table.php`, `..._create_phase_checklist_items_table.php`, `..._create_phase_comments_table.php` |
| Thư viện | `mews/purifier` (làm sạch HTML), CKEditor 5 (CDN), **SortableJS** (CDN — kéo–thả Kanban) |
| Lưu trữ | disk `public` + `storage:link`; symlink dạng tương đối để dùng trong Docker |

## 9. Trường hợp kiểm thử tiêu biểu

- Tạo KPI với 4 giai đoạn → tiến độ 0%.
- Đánh dấu 1/4 giai đoạn `done` → tiến độ 25%.
- Hoàn thành tất cả giai đoạn → KPI `done`, tiến độ 100%.
- Giai đoạn quá hạn chưa xong → KPI `behind`.
- User được giao giai đoạn cập nhật trạng thái → thành công; cập nhật giai đoạn người khác → 403.
- User không liên quan mở KPI → 403.
- Mở lại giai đoạn đã `done` → `completed_at` bị xoá, tiến độ giảm.
