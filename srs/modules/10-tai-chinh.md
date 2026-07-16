# M10 — Quản lý tài chính

> **Trạng thái:** Đã hiện thực trong mã nguồn (controllers `App\Http\Controllers\Finance\*`, models `Finance*`, service `FinanceService`, 4 migration `finance_*`). Tài liệu phản ánh đúng hành vi hệ thống đang chạy.

## 1. Mục tiêu

Cung cấp cho doanh nghiệp một công cụ **quản lý dòng tiền nội bộ** ở mức gọn nhẹ, gồm:

- Quản lý **tiền công ty** theo nhiều **quỹ** (tiền mặt, tài khoản ngân hàng); cho phép **đặt và chỉnh sửa số dư**.
- Ghi nhận **tiền nạp vào công ty** (góp vốn/nạp quỹ) tách biệt với các khoản thu khác.
- Ghi nhận **thu / chi** (chi phí) theo danh mục.
- Theo dõi **công nợ** phải thu và phải trả, kèm hạn thanh toán.
- **Báo cáo dòng vốn**: **tổng tiền đã nạp vào công ty**, **tổng tiền đã chi**, và **số dư hiện tại** (đối chiếu nạp − chi). Số dư **được phép âm** (khi chi vượt nạp, ví dụ nhân viên ứng tiền cá nhân).
- **Tổng quan tài chính** trên Dashboard: hiển thị **số dư hiện có**, tổng nạp/tổng chi, công nợ, dòng tiền theo thời gian.

Module độc lập với nghiệp vụ nhân sự; là bước đệm cho module Lương/Bảng lương ở lộ trình sau.

## 2. Phạm vi

- **A. Quỹ tiền:** CRUD quỹ; đặt số dư đầu kỳ; điều chỉnh số dư (giữ lịch sử); **nạp tiền vào công ty**; xem tổng tiền công ty.
- **B. Thu / Chi:** CRUD danh mục thu/chi; CRUD giao dịch thu/chi gắn quỹ + danh mục.
- **C. Công nợ:** CRUD khoản phải thu/phải trả; ghi nhận thanh toán (sinh giao dịch); tự cập nhật trạng thái & cảnh báo quá hạn.
- **D. Tổng quan & báo cáo tài chính:** tổng nạp vào công ty, tổng đã chi, **số dư hiện tại (cho phép âm)**, công nợ, biểu đồ dòng tiền; thẻ số dư hiển thị trên Dashboard.

### Ngoài phạm vi (phiên bản này)

- Tính lương/bảng lương (module riêng ở lộ trình sau; khi có sẽ *đẩy* giao dịch chi vào module này).
- Đa tiền tệ (giữ cột `currency` mặc định `VND` để mở rộng, nhưng không quy đổi tỷ giá).
- Hoá đơn điện tử, kết nối ngân hàng/kế toán ngoài, xuất báo cáo thuế.
- Xuất Excel/PDF (đề xuất bổ sung ở Phase 4+).

## 3. Tác nhân

- **Super Admin**: toàn quyền trên module (xem, tạo, sửa, xoá quỹ/giao dịch/công nợ/danh mục).
- **Người dùng** (nhân viên thường): **không truy cập** module tài chính (dữ liệu nhạy cảm).
- **Hệ thống**: (tuỳ chọn Phase 3) tác vụ nền đánh dấu công nợ **quá hạn** hằng ngày.

> Ghi chú phân quyền: giai đoạn đầu dùng lại Gate `admin` sẵn có (chỉ Super Admin). Vai trò "Kế toán" riêng thuộc hạng mục mở rộng RBAC (phụ lục 5.5), chưa nằm trong phạm vi này.

## 4. Yêu cầu chức năng

### 4.1. Quỹ tiền (A)

| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M10-01 | `GET /tai-chinh/quy`: liệt kê các quỹ kèm **số dư hiện tại** (tính động) và **tổng tiền công ty** = tổng số dư mọi quỹ đang hoạt động. | can:admin |
| FR-M10-02 | `POST /tai-chinh/quy`: tạo quỹ mới với tên, loại (`cash`/`bank`), số dư đầu kỳ, (tuỳ chọn) ngân hàng/số tài khoản, ghi chú. | can:admin |
| FR-M10-03 | `PUT /tai-chinh/quy/{account}`: sửa thông tin quỹ; cho phép chỉnh **số dư đầu kỳ** trực tiếp. | can:admin |
| FR-M10-04 | `POST /tai-chinh/quy/{account}/dieu-chinh`: **điều chỉnh số dư** bằng cách sinh một giao dịch điều chỉnh (chênh lệch giữa số dư mong muốn và số dư hiện tại) — giữ nguyên lịch sử. | can:admin |
| FR-M10-05 | `POST /tai-chinh/quy/{account}/nap-tien`: **nạp tiền vào công ty** — sinh một giao dịch `income` được đánh dấu **là khoản nạp vốn** (`is_contribution = true`), có thể ghi người/nguồn nạp (VD nhân viên ứng tiền). | can:admin |
| FR-M10-06 | `DELETE /tai-chinh/quy/{account}`: xoá mềm quỹ. Chặn xoá nếu quỹ còn giao dịch (gợi ý "ngừng hoạt động" thay vì xoá). | can:admin |

### 4.2. Danh mục & Giao dịch thu/chi (B)

| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M10-07 | `GET /tai-chinh/danh-muc` + CRUD danh mục thu/chi (tên, chiều `income`/`expense`, màu). | can:admin |
| FR-M10-08 | `GET /tai-chinh/giao-dich`: sổ giao dịch, **lọc** theo quỹ, danh mục, chiều (thu/chi/nạp vốn), khoảng ngày; **phân trang 15/trang**. | can:admin |
| FR-M10-09 | `POST /tai-chinh/giao-dich`: tạo giao dịch với quỹ, chiều, số tiền, ngày phát sinh, danh mục (tuỳ chọn), mô tả, tham chiếu, cờ **nạp vốn** (`is_contribution`). Ghi `created_by`. | can:admin |
| FR-M10-10 | `PUT /tai-chinh/giao-dich/{transaction}`: sửa giao dịch. | can:admin |
| FR-M10-11 | `DELETE /tai-chinh/giao-dich/{transaction}`: xoá mềm giao dịch. Nếu giao dịch gắn công nợ → cập nhật lại số đã trả/trạng thái của công nợ. | can:admin |

### 4.3. Công nợ (C)

| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M10-12 | `GET /tai-chinh/cong-no`: danh sách công nợ, lọc theo loại (`receivable`/`payable`) và trạng thái; hiển thị **số còn lại** = tổng − đã thanh toán; **phân trang 15/trang**. | can:admin |
| FR-M10-13 | `POST /tai-chinh/cong-no`: tạo công nợ với loại, đối tác, số tiền, hạn thanh toán, mô tả. Trạng thái khởi tạo `open`. | can:admin |
| FR-M10-14 | `PUT /tai-chinh/cong-no/{debt}`: sửa công nợ (khi chưa `paid`/`cancelled`). | can:admin |
| FR-M10-15 | `POST /tai-chinh/cong-no/{debt}/thanh-toan`: ghi nhận một lần thanh toán → **sinh giao dịch** gắn quỹ (`payable` → chi; `receivable` → thu) và cập nhật số đã trả + trạng thái công nợ. | can:admin |
| FR-M10-16 | `DELETE /tai-chinh/cong-no/{debt}`: xoá mềm công nợ (chỉ khi chưa phát sinh thanh toán, hoặc chuyển `cancelled`). | can:admin |

### 4.4. Tổng quan & báo cáo tài chính (D)

| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M10-17 | `GET /tai-chinh`: trang tổng quan hiển thị 3 chỉ số cốt lõi — **Tổng tiền đã nạp vào công ty**, **Tổng tiền đã chi**, **Số dư hiện tại** (nạp − chi + thu khác; **có thể âm**) — cùng công nợ phải thu/phải trả đang mở và **N khoản nợ sắp đến/đã quá hạn**. | can:admin |
| FR-M10-18 | Trang tổng quan hiển thị thêm **tổng thu khác** (thu không phải nạp vốn, VD thu nợ/doanh thu) và tổng chi **tháng hiện tại** để đối chiếu. | can:admin |
| FR-M10-19 | Biểu đồ **dòng tiền theo tháng** (nạp/thu vs chi) trong 6 tháng gần nhất, tính từ sổ giao dịch (một truy vấn gộp, tránh N+1 — theo NFR-PERF). | can:admin |
| FR-M10-20 | **Dashboard quản trị (M02)** bổ sung thẻ **"Số dư hiện có"** (kèm tổng nạp / tổng chi) liên kết sang `/tai-chinh`. Khi số dư **âm**, hiển thị cảnh báo trực quan (VD màu đỏ + nhãn "Đang âm quỹ / ứng tiền"). | can:admin |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M10-01 | **Số dư một quỹ** = `opening_balance` + Σ(giao dịch `income`) − Σ(giao dịch `expense`) của quỹ đó (chỉ tính giao dịch chưa xoá mềm). |
| BR-M10-02 | **Số dư hiện tại (tổng tiền công ty)** = Σ số dư của các quỹ có `is_active = true`. |
| BR-M10-03 | **Số dư hiện tại ĐƯỢC PHÉP ÂM**: khi tổng chi vượt tổng nạp/thu (ví dụ nhân viên **ứng tiền cá nhân** chi hộ công ty), hệ thống **không chặn** và hiển thị số dư âm. |
| BR-M10-04 | **Tổng tiền đã nạp vào công ty** = Σ(`opening_balance` các quỹ) + Σ(giao dịch `income` có `is_contribution = true`). Số dư đầu kỳ được coi là khoản nạp ban đầu. |
| BR-M10-05 | **Tổng tiền đã chi** = Σ(giao dịch `expense`, chưa xoá mềm). |
| BR-M10-06 | **Tổng thu khác** = Σ(giao dịch `income` có `is_contribution = false`) — không tính vào "tiền nạp" nhưng vẫn cộng vào số dư. |
| BR-M10-07 | Quan hệ đối chiếu: **Số dư hiện tại = Tổng nạp + Tổng thu khác − Tổng chi**. |
| BR-M10-08 | Mọi giá trị tiền lưu kiểu `decimal(15,2)`, đơn vị mặc định **VND**; **không dùng float**. |
| BR-M10-09 | Điều chỉnh số dư (FR-M10-04): hệ thống tạo giao dịch chiều phù hợp với chênh lệch `mục_tiêu − hiện_tại` (dương → `income`, âm → `expense`), danh mục hệ thống "Điều chỉnh số dư"; giao dịch điều chỉnh **không** tính là nạp vốn (`is_contribution = false`). |
| BR-M10-10 | **Số còn lại của công nợ** = `amount` − Σ(giao dịch thanh toán gắn `debt_id`). |
| BR-M10-11 | Trạng thái công nợ tự suy ra: `paid` khi còn lại = 0; `partially_paid` khi 0 < đã trả < tổng; `open` khi chưa trả; `overdue` khi quá `due_date` mà chưa `paid`; `cancelled` khi bị huỷ. |
| BR-M10-12 | Thanh toán không được vượt số còn lại của công nợ. |
| BR-M10-13 | Thanh toán `payable` sinh giao dịch **chi**; thanh toán `receivable` sinh giao dịch **thu**, trên quỹ được chọn. |
| BR-M10-14 | Xoá quỹ dùng **soft delete**; **chặn** nếu quỹ còn giao dịch (bảo toàn lịch sử) — khuyến nghị đặt `is_active = false`. |
| BR-M10-15 | Xoá giao dịch/công nợ dùng **soft delete**; xoá giao dịch gắn nợ phải tính lại số đã trả và trạng thái công nợ. |
| BR-M10-16 | Số tiền giao dịch và công nợ phải **> 0**. |
| BR-M10-17 | Mỗi giao dịch ghi `created_by` (kiểm toán tối thiểu, do hệ thống chưa có audit log). |
| BR-M10-18 | Chiều (`direction`) của giao dịch phải khớp chiều của danh mục nếu có gán danh mục. |
| BR-M10-19 | `is_contribution = true` chỉ áp dụng cho giao dịch chiều `income`; giao dịch `expense` luôn có `is_contribution = false`. |

## 6. Ràng buộc dữ liệu (validation)

### Quỹ (`finance_accounts`)
| Trường | Quy tắc |
|--------|---------|
| `name` | bắt buộc, ≤255, duy nhất |
| `type` | bắt buộc, ∈ {`cash`,`bank`} |
| `opening_balance` | bắt buộc, số, ≥ 0 |
| `bank_name`, `account_number` | tuỳ chọn, ≤255 / ≤50 |
| `note` | tuỳ chọn, ≤500 |

### Giao dịch (`finance_transactions`)
| Trường | Quy tắc |
|--------|---------|
| `account_id` | bắt buộc, tồn tại trong `finance_accounts` |
| `direction` | bắt buộc, ∈ {`income`,`expense`} |
| `amount` | bắt buộc, số, > 0 |
| `occurred_on` | bắt buộc, ngày hợp lệ |
| `category_id` | tuỳ chọn, tồn tại; chiều phải khớp `direction` |
| `is_contribution` | boolean; chỉ được `true` khi `direction = income` |
| `contributor_name` | tuỳ chọn, ≤255 (người/nguồn nạp — VD nhân viên ứng tiền) |
| `description` | tuỳ chọn, ≤500 |
| `reference` | tuỳ chọn, ≤100 |

### Nạp tiền vào công ty (form — FR-M10-05)
| Trường | Quy tắc |
|--------|---------|
| `account_id` | bắt buộc, tồn tại (quỹ nhận tiền) |
| `amount` | bắt buộc, > 0 |
| `occurred_on` | bắt buộc, ngày hợp lệ |
| `contributor_name` | tuỳ chọn, ≤255 |
| *(hệ thống tự đặt `direction=income`, `is_contribution=true`)* | |

### Công nợ (`finance_debts`)
| Trường | Quy tắc |
|--------|---------|
| `type` | bắt buộc, ∈ {`receivable`,`payable`} |
| `partner_name` | bắt buộc, ≤255 |
| `amount` | bắt buộc, số, > 0 |
| `due_date` | tuỳ chọn, ngày hợp lệ |
| `description` | tuỳ chọn, ≤500 |

### Thanh toán công nợ (form)
| Trường | Quy tắc |
|--------|---------|
| `account_id` | bắt buộc, tồn tại |
| `amount` | bắt buộc, > 0, ≤ số còn lại |
| `occurred_on` | bắt buộc, ngày hợp lệ |

## 7. Mô hình dữ liệu (bảng mới)

### `finance_accounts` — Quỹ / Tài khoản tiền
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `name` | string | unique, not null | Tên quỹ (VD: "Tiền mặt", "VCB — 012...") |
| `type` | enum(`cash`,`bank`) | default `cash` | Loại quỹ |
| `bank_name` | string | nullable | Ngân hàng (nếu `bank`) |
| `account_number` | string | nullable | Số tài khoản |
| `opening_balance` | decimal(15,2) | default 0 | Số dư đầu kỳ (admin đặt/sửa) |
| `currency` | string(3) | default `VND` | Tiền tệ |
| `is_active` | boolean | default true | Đang sử dụng |
| `note` | string | nullable | Ghi chú |
| `timestamps` + `deleted_at` | | softDeletes | |

### `finance_categories` — Danh mục thu/chi
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | |
| `name` | string | not null | Tên danh mục (VD: "Lương", "Thuê văn phòng") |
| `direction` | enum(`income`,`expense`) | not null | Chiều tiền |
| `color` | string | nullable | Màu nhận diện (UI) |
| `timestamps` | | | |

### `finance_transactions` — Sổ giao dịch thu/chi
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | |
| `account_id` | bigint | FK→finance_accounts, cascade | Quỹ chịu tác động |
| `category_id` | bigint | FK→finance_categories, nullOnDelete | Danh mục (tuỳ chọn) |
| `debt_id` | bigint | FK→finance_debts, nullOnDelete | Gắn công nợ (nếu là thanh toán) |
| `direction` | enum(`income`,`expense`) | not null | Thu / Chi |
| `amount` | decimal(15,2) | not null | Số tiền (>0) |
| `is_contribution` | boolean | default false | Đánh dấu khoản **nạp vốn vào công ty** (chỉ với `income`) |
| `contributor_name` | string | nullable | Người/nguồn nạp (VD nhân viên ứng tiền) |
| `occurred_on` | date | not null | Ngày phát sinh |
| `description` | string | nullable | Diễn giải |
| `reference` | string | nullable | Số chứng từ/tham chiếu |
| `created_by` | bigint | FK→users, nullOnDelete | Người tạo (kiểm toán) |
| `timestamps` + `deleted_at` | | softDeletes | |

### `finance_debts` — Công nợ
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | |
| `type` | enum(`receivable`,`payable`) | not null | Phải thu / Phải trả |
| `partner_name` | string | not null | Đối tác (khách hàng/nhà cung cấp) |
| `partner_contact` | string | nullable | Liên hệ đối tác |
| `amount` | decimal(15,2) | not null | Tổng công nợ (>0) |
| `due_date` | date | nullable | Hạn thanh toán |
| `status` | enum(`open`,`partially_paid`,`paid`,`overdue`,`cancelled`) | default `open` | Trạng thái |
| `description` | string | nullable | Diễn giải |
| `timestamps` + `deleted_at` | | softDeletes | |

### Quan hệ
| Quan hệ | Loại | Khoá | Hành vi xoá |
|---------|------|------|-------------|
| `finance_accounts` — `finance_transactions` | 1 — N | `transactions.account_id` | cascade |
| `finance_categories` — `finance_transactions` | 1 — N | `transactions.category_id` | nullOnDelete |
| `finance_debts` — `finance_transactions` | 1 — N | `transactions.debt_id` | nullOnDelete |
| `users` — `finance_transactions` | 1 — N | `transactions.created_by` | nullOnDelete |

## 8. Danh mục giá trị enum

| Thực thể.Trường | Giá trị | Nhãn tiếng Việt |
|-----------------|---------|-----------------|
| finance_accounts.type | `cash` / `bank` | Tiền mặt / Ngân hàng |
| finance_categories.direction | `income` / `expense` | Thu / Chi |
| finance_transactions.direction | `income` / `expense` | Thu / Chi |
| finance_transactions.is_contribution | `true` / `false` | Nạp vốn vào công ty / Không |
| finance_debts.type | `receivable` / `payable` | Phải thu / Phải trả |
| finance_debts.status | `open`/`partially_paid`/`paid`/`overdue`/`cancelled` | Mở / Trả một phần / Đã trả / Quá hạn / Đã huỷ |

## 9. Luồng xử lý tiêu biểu

### 9.1. Nạp tiền vào công ty & theo dõi số dư
1. Super Admin vào `/tai-chinh/quy` → "Thêm quỹ" (VD: Tiền mặt, số dư đầu kỳ 50.000.000) — số dư đầu kỳ được tính là **khoản nạp ban đầu**.
2. Khi công ty được góp thêm tiền, admin bấm "Nạp tiền", chọn quỹ, nhập số tiền, người nạp → sinh giao dịch `income` với `is_contribution = true`.
3. Báo cáo cập nhật: **Tổng đã nạp** tăng, **Số dư hiện tại** tăng.

### 9.2. Ghi một khoản chi phí
1. Admin vào `/tai-chinh/giao-dich` → "Thêm giao dịch", chọn quỹ, chiều = Chi, danh mục "Thuê văn phòng", số tiền, ngày.
2. Hệ thống lưu giao dịch với `created_by`.
3. **Tổng đã chi** tăng; **Số dư hiện tại** giảm tương ứng (tính động).

### 9.3. Ứng tiền cá nhân → số dư âm
1. Quỹ đang còn 2.000.000. Nhân viên A ứng tiền túi chi hộ khoản mua thiết bị 5.000.000.
2. Admin ghi giao dịch **chi** 5.000.000 (mô tả: "A ứng tiền mua thiết bị").
3. **Số dư hiện tại = −3.000.000** (hệ thống **cho phép âm**, BR-M10-03) — thể hiện công ty đang nợ A 3.000.000.
4. (Tuỳ chọn) tạo công nợ `payable` cho A để theo dõi hoàn ứng; khi hoàn tiền cho A → giao dịch chi tiếp / hoặc khi nạp bù → `income` nạp vốn.
5. Dashboard hiển thị số dư âm với cảnh báo màu đỏ (FR-M10-20).

### 9.4. Thanh toán công nợ phải trả
1. Admin mở công nợ `payable` (nợ nhà cung cấp) còn lại 10.000.000.
2. Bấm "Thanh toán", chọn quỹ, nhập 4.000.000 (≤ số còn lại).
3. Hệ thống sinh giao dịch **chi** 4.000.000 gắn `debt_id`, trừ quỹ.
4. Số đã trả = 4.000.000 → trạng thái `partially_paid`.
5. Khi trả đủ → `paid`.

## 10. Giao diện liên quan

- `finance/overview.blade.php` — tổng quan tài chính (chỉ số + biểu đồ dòng tiền + công nợ sắp đến hạn).
- `finance/accounts.blade.php` — danh sách quỹ, form tạo/sửa, nạp tiền, điều chỉnh số dư.
- `finance/transactions.blade.php` — sổ giao dịch có bộ lọc + phân trang.
- `finance/debts.blade.php` — danh sách công nợ + form thanh toán/huỷ.
- `finance/categories.blade.php` — danh mục thu/chi.
- Partial dùng chung: `finance/_nav.blade.php`, `finance/_flash.blade.php`, `finance/_account-fields`, `finance/_transaction-fields`, `finance/_debt-fields`; component `<x-finance-modal>`.
- Sidebar: mục **"Tài chính"** (icon `account_balance_wallet`) trong nhóm Hệ thống, bọc `@can('admin')`.

## 11. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controllers | `App\Http\Controllers\Finance\{FinanceOverviewController, FinanceAccountController, FinanceTransactionController, FinanceDebtController, FinanceCategoryController}` |
| Models | `FinanceAccount`, `FinanceCategory`, `FinanceTransaction`, `FinanceDebt` |
| Service | `App\Services\FinanceService` (`summary()` — tổng đã nạp / thu khác / đã chi / số dư (cho phép âm); `monthlyCashflow()`; `refreshDebtStatus()`) |
| Form Requests | `StoreFinanceAccountRequest`, `DepositRequest`, `AdjustBalanceRequest`, `StoreFinanceTransactionRequest`, `StoreFinanceDebtRequest`, `PayDebtRequest`, `StoreFinanceCategoryRequest` |
| Migrations | `2024_05_04_000001..000004_create_finance_*` |
| Routes | nhóm `finance.*` dưới prefix `/tai-chinh`, middleware `auth`, `can:admin` |
| Dashboard | `DashboardController@adminDashboard` truyền `finance` (summary) ra thẻ "Số dư hiện có" |
| (Tuỳ chọn, chưa làm) Command | `finance:mark-overdue` — đánh dấu công nợ quá hạn theo lịch |

## 12. Trường hợp kiểm thử tiêu biểu

- Tạo quỹ số dư đầu kỳ X → tổng tiền công ty tăng X, **Tổng đã nạp** tăng X.
- Nạp tiền N (is_contribution) → **Tổng đã nạp** tăng N, **Số dư hiện tại** tăng N.
- Ghi giao dịch chi Y → **Tổng đã chi** tăng Y, số dư quỹ & số dư hiện tại giảm Y.
- **Chi vượt tổng nạp/thu → Số dư hiện tại ÂM (không bị chặn)**; Dashboard hiển thị cảnh báo.
- Đối chiếu: Số dư hiện tại = Tổng nạp + Tổng thu khác − Tổng chi.
- Thu khác (income, is_contribution=false, VD thu nợ) → cộng vào số dư nhưng **không** cộng vào "Tổng đã nạp".
- Điều chỉnh số dư về Z → sinh giao dịch chênh lệch (is_contribution=false), số dư = Z.
- Xoá quỹ còn giao dịch → bị chặn (gợi ý ngừng hoạt động).
- Tạo công nợ `payable` A, thanh toán B < A → `partially_paid`, sinh giao dịch chi B.
- Thanh toán vượt số còn lại → lỗi validation.
- Xoá giao dịch thanh toán → số đã trả & trạng thái công nợ được tính lại.
- `amount ≤ 0` (giao dịch/công nợ) → lỗi validation.
- Đặt `is_contribution = true` cho giao dịch chi → lỗi validation.
- Người dùng thường truy cập `/tai-chinh` → HTTP 403.

## 13. Đồng bộ với các tài liệu SRS khác (đã cập nhật)

- **01 — Tổng quan:** M10 đã thêm vào bản đồ module; "tài chính cơ bản" đã đưa ra khỏi mục ngoài phạm vi.
- **02 — Dashboard (M02):** thẻ **"Số dư hiện có"** (kèm tổng nạp / tổng chi, cảnh báo khi âm) hiển thị trên dashboard quản trị.
- **03 — Mô hình dữ liệu:** đã bổ sung 4 bảng `finance_*` + quan hệ + enum tài chính (gồm `is_contribution`) vào ERD/từ điển.
- **04 — Phân quyền:** nhóm `finance.*` chỉ Super Admin (Gate `admin`).
- **05 — Route map:** đã thêm các route `finance.*`.
