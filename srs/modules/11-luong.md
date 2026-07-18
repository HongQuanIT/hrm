# M11 — Lương / Bảng lương

> **Trạng thái:** Phase 1 (MVP) **đã hiện thực** trong mã nguồn — controllers `App\Http\Controllers\Payroll\*` (`PayrollPeriodController`, `PayslipController`, `MyPayslipController`), models `PayrollPeriod`/`Payslip`/`PayslipItem`, service `App\Services\PayrollService`, 3 migration `payroll_*`, views `resources/views/payroll/*`, routes `payroll.*`. Xây dựng trên dữ liệu sẵn có của M03 (nhân viên), M05 (chấm công), M06 (nghỉ phép) và tích hợp chi tiền với M10 (tài chính). Các phần Phase 2/3 được đánh dấu rõ là mở rộng sau.
>
> **Lưu ý cấu hình:** quota phép có lương/tháng lấy từ `company_settings.leave_days_per_month` (mặc định **6**, dùng chung với M06).

## 1. Mục tiêu

Tính lương theo **kỳ (tháng)** cho từng nhân viên dựa trên **công thực tế + nghỉ phép**, sinh **phiếu lương** gồm các khoản cộng/trừ, quản lý quy trình **nháp → tính → duyệt → chi**, cho nhân viên **tự xem phiếu lương của mình**, và **đẩy khoản chi lương sang module Tài chính (M10)** để dòng tiền công ty phản ánh đúng.

## 2. Phạm vi

### Trong phạm vi (Phase 1 — MVP)
- Tạo và quản lý **kỳ lương** theo tháng (mỗi tháng một kỳ).
- **Tính lương tự động** theo phương pháp **prorate ngày công thực tế**: lương ngày × số ngày công (nghỉ không lương/vắng mặt bị trừ; nghỉ có lương được tính công).
- **Lương tháng = lương cơ bản + phụ cấp**; lương ngày = lương tháng ÷ số ngày trong tháng. Phụ cấp là một phần của lương tháng (không phải khoản cộng ngoài) — nghỉ trong quota vẫn hưởng, chỉ ngày không lương/vắng bị trừ.
- **Phiếu lương** cho từng nhân viên với các dòng cộng/trừ (`payslip_items`); admin **thêm/xoá khoản tay** (thưởng, phạt, tạm ứng…).
- Quy trình trạng thái: `draft` → `calculated` → `approved` → `paid`.
- **Chi lương**: sinh giao dịch **chi** trong Tài chính (danh mục "Lương"), trừ quỹ được chọn.
- **Self-service**: nhân viên xem phiếu lương **của mình** cho các kỳ đã duyệt/đã chi.

### Ngoài phạm vi (Phase 2/3 — mở rộng sau)
- **Phase 2:** tự động tính **phạt đi muộn**, **tăng ca**, **trừ tạm ứng**; bảng cấu hình khoản cộng/trừ dùng lại (`payroll_components`).
- **Phase 3:** **BHXH/BHYT/BHTN** và **thuế TNCN** theo luật VN; **xuất PDF/Excel** phiếu lương; **đóng kỳ** (`closed`) khoá vĩnh viễn.
- Đa kỳ trong tháng (lương 2 kỳ), lương theo giờ/ca, đa tiền tệ.

## 3. Tác nhân

- **Super Admin**: toàn quyền — tạo kỳ, tính, điều chỉnh phiếu, duyệt, chi lương, xem mọi phiếu.
- **Người dùng** (nhân viên): **chỉ xem phiếu lương của chính mình** cho các kỳ `approved`/`paid`; không thấy bản nháp, không thấy phiếu người khác.
- **Hệ thống**: (Phase 2+) tuỳ chọn tác vụ nền nhắc kỳ lương chưa chốt.

> Phân quyền: giai đoạn đầu dùng lại Gate `admin` sẵn có cho các thao tác quản trị (giống M10). Self-service của nhân viên chỉ cần `auth` + kiểm tra chủ sở hữu trong controller.

## 4. Yêu cầu chức năng

### 4.1. Kỳ lương (Super Admin)
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M11-01 | `GET /luong`: liệt kê các kỳ lương (tháng/năm, trạng thái, tổng chi net, số phiếu), mới nhất trước; phân trang. | can:admin |
| FR-M11-02 | `POST /luong`: tạo kỳ lương cho `month`+`year` (duy nhất theo tháng/năm); có thể nhập/ghi đè **số ngày trong kỳ** `days_in_month` (mặc định = số ngày lịch của tháng: 28/29/30/31). Trạng thái khởi tạo `draft`. | can:admin |
| FR-M11-03 | `GET /luong/{period}`: chi tiết kỳ — danh sách phiếu lương của toàn bộ nhân viên đủ điều kiện, kèm tổng hợp (tổng gross/deduction/net). | can:admin |
| FR-M11-04 | `POST /luong/{period}/tinh`: **tính (hoặc tính lại)** toàn bộ phiếu lương của kỳ từ chấm công + nghỉ phép; chuyển `draft`→`calculated`. Chỉ chạy khi kỳ `draft`/`calculated`. | can:admin |
| FR-M11-05 | `PATCH /luong/{period}/duyet`: **duyệt** kỳ (`calculated`→`approved`); ghi `approved_by`, `approved_at`; khoá chỉnh sửa phiếu. | can:admin |
| FR-M11-06 | `PATCH /luong/{period}/mo-lai`: mở lại kỳ `approved`→`calculated` để chỉnh (chỉ khi **chưa chi**). | can:admin |
| FR-M11-07 | `POST /luong/{period}/chi`: **chi lương** — chọn quỹ + ngày chi; sinh giao dịch **chi** trong Tài chính (danh mục "Lương"), gắn `finance_transaction_id`; kỳ → `paid`. Chỉ khi kỳ `approved`. | can:admin |
| FR-M11-08 | `DELETE /luong/{period}`: xoá mềm kỳ lương (chỉ khi **chưa `paid`**). | can:admin |

### 4.2. Phiếu lương & khoản cộng/trừ (Super Admin)
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M11-09 | `GET /luong/{period}/phieu/{payslip}`: xem chi tiết một phiếu (thông số công, các dòng cộng/trừ, gross/net). | can:admin hoặc **chủ phiếu** (khi kỳ `approved`/`paid`) |
| FR-M11-10 | `POST /luong/{period}/phieu/{payslip}/khoan`: thêm một khoản **cộng** (earning) hoặc **trừ** (deduction) thủ công vào phiếu; tính lại gross/net của phiếu. Chỉ khi kỳ chưa `approved`. | can:admin |
| FR-M11-11 | `DELETE /luong/{period}/phieu/{payslip}/khoan/{item}`: xoá một khoản thủ công; không cho xoá dòng hệ thống (`base`, `lunch`). Chỉ khi kỳ chưa `approved`. | can:admin |

### 4.3. Self-service nhân viên
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M11-12 | `GET /luong/cua-toi`: nhân viên xem danh sách phiếu lương **của mình** cho các kỳ `approved`/`paid` (ẩn kỳ nháp/đang tính). | auth (có hồ sơ NV) |
| FR-M11-13 | Nhân viên mở phiếu của mình dùng chung FR-M11-09; truy cập phiếu người khác hoặc kỳ chưa duyệt → HTTP 403. | auth (chủ phiếu) |

## 5. Quy tắc nghiệp vụ

### 5.1. Đối tượng tính lương
| Mã | Quy tắc |
|----|--------|
| BR-M11-01 | Kỳ lương tính cho nhân viên `status ∈ {active, on_leave}` tại thời điểm tính; bỏ qua `resigned`. |
| BR-M11-02 | Mỗi cặp (`payroll_period_id`, `employee_id`) chỉ có **một** phiếu lương (unique). Tính lại sẽ **ghi đè** phiếu và các dòng **hệ thống**; **giữ nguyên** các khoản **thủ công** admin đã thêm. |
| BR-M11-03 | `days_in_month` (số ngày trong kỳ) = số **ngày lịch** thực tế của tháng (28/29/30/31). Đây là mẫu số chia lương ngày; **không** dùng số ngày thường. Admin có thể ghi đè trên kỳ (VD chốt cứng 30) nếu công ty muốn. |

### 5.2. Tính công (nguồn từ M05 + M06)
| Mã | Quy tắc |
|----|--------|
| BR-M11-04 | **Lương tháng & đơn giá ngày công**: `monthly_salary = base_salary + lunch_allowance` (lương cơ bản + phụ cấp = lương của cả tháng). `daily_rate = monthly_salary / days_in_month`. Mỗi ngày lịch (kể cả cuối tuần) đều mang giá trị `daily_rate`; nhân viên đủ công cả tháng nhận đủ `monthly_salary`. |
| BR-M11-05 | **Ngày thực đi làm** (`present_days`): số bản ghi chấm công trong kỳ có `status ∈ {on_time, late, working, missing_checkout}` (`missing_checkout` vẫn tính 1 công nhưng **gắn cờ** để admin rà). |
| BR-M11-06 | **Quota phép có lương co theo tỷ lệ đi làm**: `paid_leave_quota = round(leave_days_per_month × present_days / days_in_month)` — `leave_days_per_month` lấy từ cấu hình M09 (mặc định **6**); nghỉ càng nhiều → quota càng giảm. Làm tròn **gần nhất** (0.5 làm tròn lên). |
| BR-M11-07 | **Ngày phép có lương** (`paid_leave_days`) `= min(tổng ngày nghỉ approved trong kỳ, paid_leave_quota)` — **không trừ lương**; số dư phép giảm dần và **được phép âm** (thống nhất BR-M06-09). |
| BR-M11-08 | **Ngày nghỉ không lương** (`unpaid_leave_days`) `= max(tổng ngày nghỉ − paid_leave_days, 0)` (phần vượt quota + đơn loại `unpaid`) — **bị trừ**. **Ngày vắng** (`absent_days`): chấm công `status = absent` không có đơn phép hợp lệ — **bị trừ**. |
| BR-M11-09 | `unpaid_days = unpaid_leave_days + absent_days`. `paid_days = days_in_month − unpaid_days`. Nghỉ phép **trong quota** và cuối tuần **không** làm giảm `paid_days`. |

### 5.3. Công thức lương (Phase 1 — prorate theo ngày lịch)
| Mã | Quy tắc |
|----|--------|
| BR-M11-10 | `daily_rate = (base_salary + lunch_allowance) / days_in_month` (nếu `days_in_month = 0` → coi lương công = 0, cảnh báo). |
| BR-M11-11 | **Tổng lương theo công** (gồm cả phụ cấp) `= round(daily_rate × paid_days)`. Khi không có ngày bị trừ → đúng bằng `monthly_salary`. Trên phiếu tách thành 2 dòng để minh bạch: **Phụ cấp** `= round((lunch_allowance / days_in_month) × paid_days)` và **Lương cơ bản theo công** = tổng − phụ cấp (đảm bảo cộng khớp tuyệt đối). |
| BR-M11-12 | **Khấu trừ nghỉ/vắng** thể hiện gián tiếp qua `paid_days` (đã trừ `unpaid_days`); có thể xuất **dòng thông tin** ghi rõ `unpaid_days × daily_rate` để minh bạch trên phiếu. |
| BR-M11-13 | Phụ cấp là **một phần của lương tháng** (không phải khoản cộng thêm ngoài lương ngày). Nghỉ **trong quota** vẫn hưởng đủ phần phụ cấp tương ứng `paid_days`; chỉ ngày **không lương/vắng** mới làm giảm cả lương lẫn phụ cấp. |
| BR-M11-14 | `gross_amount = Σ(earning items)`; `deduction_total = Σ(deduction items)`; `net_amount = gross_amount − deduction_total`. Tiền lưu `decimal(15,2)`, làm tròn đến đồng, **VND**, không dùng float (BR-M10-08). `net_amount` có thể = 0 nhưng **không âm** (khấu trừ > gross → cảnh báo, chốt 0, ghi công nợ phần vượt ở Phase sau). |

### 5.4. Trạng thái & khoá chỉnh sửa
| Mã | Quy tắc |
|----|--------|
| BR-M11-15 | Chuyển trạng thái hợp lệ: `draft → calculated → approved → paid`. `approved → calculated` chỉ khi chưa `paid` (FR-M11-06). |
| BR-M11-16 | Chỉ **tính/tính lại** và **sửa khoản tay** khi kỳ ở `draft`/`calculated`. Kỳ `approved`/`paid` **khoá** mọi thay đổi phiếu. |
| BR-M11-17 | Dòng **hệ thống** (`base`, `lunch`) không cho xoá tay; chỉ thay đổi khi tính lại. |

### 5.5. Tích hợp Tài chính (M10)
| Mã | Quy tắc |
|----|--------|
| BR-M11-18 | Khi **chi lương** (FR-M11-07): sinh **một giao dịch `expense`** tổng cho cả kỳ (số tiền = Σ `net_amount`), gắn quỹ được chọn, danh mục hệ thống **"Lương"** (tự tạo nếu chưa có, `direction = expense`), `occurred_on` = ngày chi, `description = "Lương tháng MM/YYYY"`, `created_by`. Lưu `finance_transaction_id` vào kỳ. |
| BR-M11-19 | Chi lương làm **giảm số dư quỹ** đúng bằng tổng net; số dư công ty **được phép âm** (thống nhất BR-M10-03). |
| BR-M11-20 | Nếu **xoá/mở lại** kỳ đã `paid` (chỉ Phase sau, mặc định chặn ở Phase 1) → phải xoá mềm giao dịch chi tương ứng và cập nhật lại số dư. Ở Phase 1: kỳ `paid` **không** cho xoá/mở lại. |
| BR-M11-21 | (Phase 2 tuỳ chọn) Chi lương **tách theo từng phiếu** (mỗi NV một giao dịch, lưu `finance_transaction_id` trên `payslips`) để đối soát chi tiết. |

## 6. Ràng buộc dữ liệu (validation)

### Tạo kỳ lương (`payroll_periods`)
| Trường | Quy tắc |
|--------|---------|
| `month` | bắt buộc, số nguyên 1–12 |
| `year` | bắt buộc, số nguyên (VD 2020–2100) |
| (month, year) | **duy nhất** (không tạo trùng kỳ) |
| `days_in_month` | tuỳ chọn, số nguyên 28–31 (mặc định = số ngày lịch của tháng, tính tự động) |
| `note` | tuỳ chọn, ≤500 |

### Khoản cộng/trừ tay (`payslip_items`)
| Trường | Quy tắc |
|--------|---------|
| `type` | bắt buộc, ∈ {`earning`,`deduction`} |
| `label` | bắt buộc, ≤255 |
| `amount` | bắt buộc, số, > 0 |
| `code` | tuỳ chọn, ≤50 (dòng tay để trống/tự sinh; dòng hệ thống dùng `base`/`lunch`) |

### Chi lương (form — FR-M11-07)
| Trường | Quy tắc |
|--------|---------|
| `account_id` | bắt buộc, tồn tại trong `finance_accounts` |
| `occurred_on` | bắt buộc, ngày hợp lệ |

## 7. Mô hình dữ liệu (bảng mới)

### `payroll_periods` — Kỳ lương
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | Định danh |
| `name` | string | nullable | Tên hiển thị (VD "Lương tháng 07/2026"); tự sinh nếu trống |
| `month` | tinyint | not null | Tháng 1–12 |
| `year` | smallint | not null | Năm |
| `days_in_month` | tinyint | not null | Số ngày lịch của kỳ = mẫu số chia lương ngày (snapshot khi tính) |
| `status` | enum(`draft`,`calculated`,`approved`,`paid`) | default `draft` | Trạng thái kỳ |
| `finance_transaction_id` | bigint | FK→finance_transactions, nullOnDelete | Giao dịch chi lương (khi đã chi) |
| `note` | string | nullable | Ghi chú |
| `created_by` | bigint | FK→users, nullOnDelete | Người tạo |
| `approved_by` | bigint | FK→users, nullOnDelete | Người duyệt |
| `approved_at` | timestamp | nullable | Thời điểm duyệt |
| `timestamps` + `deleted_at` | | softDeletes | |

> Ràng buộc duy nhất `(month, year)` (loại trừ bản ghi đã xoá mềm).

### `payslips` — Phiếu lương từng nhân viên/kỳ
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | |
| `payroll_period_id` | bigint | FK→payroll_periods, cascade | Kỳ lương |
| `employee_id` | bigint | FK→employees, cascade | Nhân viên |
| `base_salary` | decimal(15,2) | not null | Lương cơ bản (snapshot) |
| `lunch_allowance` | decimal(15,2) | default 0 | Phụ cấp gộp (ăn trưa, đi lại, chỗ ở...) — snapshot |
| `days_in_month` | tinyint | not null | Số ngày lịch của kỳ (mẫu số chia lương ngày) |
| `present_days` | decimal(5,1) | default 0 | Ngày thực đi làm (dùng tính quota phép) |
| `paid_leave_days` | decimal(5,1) | default 0 | Ngày nghỉ có lương (trong quota, không trừ) |
| `unpaid_leave_days` | decimal(5,1) | default 0 | Ngày nghỉ không lương (gồm phần vượt quota) — bị trừ |
| `absent_days` | decimal(5,1) | default 0 | Ngày vắng — bị trừ |
| `unpaid_days` | decimal(5,1) | default 0 | Tổng ngày bị trừ = unpaid_leave + absent |
| `paid_days` | decimal(5,1) | default 0 | Ngày được tính lương = days_in_month − unpaid_days |
| `late_count` | int | default 0 | Số lần đi muộn (tham chiếu) |
| `late_minutes` | int | default 0 | Tổng phút muộn (tham chiếu) |
| `overtime_minutes` | int | default 0 | Phút tăng ca (Phase 2; mặc định 0) |
| `gross_amount` | decimal(15,2) | default 0 | Tổng thu nhập (Σ earning) |
| `deduction_total` | decimal(15,2) | default 0 | Tổng khấu trừ (Σ deduction) |
| `net_amount` | decimal(15,2) | default 0 | Thực nhận = gross − deduction |
| `bank_snapshot` | string | nullable | Thông tin TK ngân hàng lúc chi (đối soát) |
| `note` | string | nullable | Ghi chú/cờ cảnh báo (VD "thiếu check-out N ngày") |
| `timestamps` | | | |

> Ràng buộc duy nhất `(payroll_period_id, employee_id)`.

### `payslip_items` — Dòng cộng/trừ của phiếu
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | bigint | PK | |
| `payslip_id` | bigint | FK→payslips, cascade | Phiếu lương |
| `type` | enum(`earning`,`deduction`) | not null | Cộng / Trừ |
| `code` | string | nullable | Mã dòng (`base`,`lunch` = hệ thống; NULL/tự do = thủ công) |
| `label` | string | not null | Diễn giải (VD "Lương theo công", "Thưởng", "Tạm ứng") |
| `amount` | decimal(15,2) | not null | Số tiền (>0) |
| `is_system` | boolean | default false | Dòng do hệ thống sinh (không cho xoá tay) |
| `meta` | json | nullable | Dữ liệu phụ (VD số ngày, đơn giá) |
| `timestamps` | | | |

### Quan hệ
| Quan hệ | Loại | Khoá | Hành vi xoá |
|---------|------|------|-------------|
| `payroll_periods` — `payslips` | 1 — N | `payslips.payroll_period_id` | cascade |
| `employees` — `payslips` | 1 — N | `payslips.employee_id` | cascade |
| `payslips` — `payslip_items` | 1 — N | `payslip_items.payslip_id` | cascade |
| `payroll_periods` — `finance_transactions` | 1 — 1 | `payroll_periods.finance_transaction_id` | nullOnDelete |
| `users` — `payroll_periods` | 1 — N | `created_by`, `approved_by` | nullOnDelete |

## 8. Danh mục giá trị enum

| Thực thể.Trường | Giá trị | Nhãn tiếng Việt |
|-----------------|---------|-----------------|
| payroll_periods.status | `draft`/`calculated`/`approved`/`paid` | Nháp / Đã tính / Đã duyệt / Đã chi |
| payslip_items.type | `earning`/`deduction` | Khoản cộng / Khoản trừ |

## 9. Luồng xử lý tiêu biểu

### 9.1. Chạy lương một tháng
1. Admin vào `/luong` → "Tạo kỳ lương" tháng 04/2026 (`days_in_month` tự tính = 30) → `draft`.
2. Bấm **Tính lương** → hệ thống duyệt từng nhân viên đủ điều kiện: đọc chấm công + nghỉ phép trong tháng, tính `present_days`, `paid_leave_days`, `unpaid_leave_days`/`absent_days`, suy ra `unpaid_days` và `paid_days`, sinh phiếu với dòng `base` + `lunch` → `calculated`.
3. Admin rà soát, thêm khoản tay (VD Thưởng dự án +2.000.000; Tạm ứng −1.000.000) trên một số phiếu.
4. **Duyệt** kỳ → `approved` (khoá).
5. **Chi lương**: chọn quỹ "Tiền mặt", ngày chi 30/04 → sinh 1 giao dịch **chi** = Σ net, danh mục "Lương"; kỳ → `paid`; số dư quỹ giảm tương ứng.
6. Nhân viên vào `/luong/cua-toi` xem phiếu tháng 04 của mình.

### 9.2. Ví dụ tính một phiếu (quota phép co theo tỷ lệ đi làm)

Chung: lương cơ bản `24.000.000` + phụ cấp `6.000.000` → **lương tháng = 30.000.000**, tháng 4 → `days_in_month = 30`, `leave_days_per_month = 6`.
→ **lương ngày `daily_rate = 30.000.000 / 30 = 1.000.000`**.

**Ví dụ A — nghỉ ít (3 ngày):** đi làm 27 ngày.
- `paid_leave_quota = round(6 × 27/30) = round(5.4) = 5`; nghỉ 3 ≤ 5 → `paid_leave_days = 3`, `unpaid_days = 0`.
- `paid_days = 30` → **Lương theo công** = 1.000.000 × 30 = **30.000.000** (đủ lương tháng).
- `gross = net = 30.000.000` (tách phiếu: phụ cấp 6.000.000 + lương cơ bản 24.000.000).

**Ví dụ B — nghỉ nhiều (10 ngày, đúng tình huống bạn nêu):** đi làm 20 ngày.
- `paid_leave_quota = round(6 × 20/30) = round(4.0) = 4` → **phép có lương = 4**.
- `unpaid_days = 10 − 4 = 6` → **vượt 6 ngày không lương**; `paid_days = 30 − 6 = 24`.
- **Lương theo công** = 1.000.000 × 24 = **24.000.000** (trừ 6 ngày = 6.000.000).
- `gross = net = 24.000.000` (phụ cấp phần công = 6.000.000×24/30 = 4.800.000; lương cơ bản = 19.200.000).
- Số dư phép giảm 4 ngày (dùng hết quota tháng, không còn 6).

**Ví dụ C — nghỉ đúng 6 ngày:** đi làm 24 ngày.
- `paid_leave_quota = round(6 × 24/30) = round(4.8) = 5`; nghỉ 6 > 5 → `paid_leave_days = 5`, `unpaid_days = 1`.
- `paid_days = 29` → **Lương theo công** = 1.000.000 × 29 = **29.000.000**.

## 10. Giao diện liên quan (đề xuất)

- `payroll/periods/index.blade.php` — danh sách kỳ lương + nút tạo kỳ.
- `payroll/periods/show.blade.php` — chi tiết kỳ: bảng phiếu lương, nút Tính lại / Duyệt / Chi lương, tổng hợp.
- `payroll/payslips/show.blade.php` — chi tiết phiếu (dùng chung cho admin & self-service), form thêm khoản tay (chỉ admin, khi chưa duyệt).
- `payroll/my.blade.php` — danh sách phiếu lương của nhân viên đăng nhập.
- Component `<x-payroll-modal>` (tạo kỳ / chi lương / thêm khoản) tương tự `<x-finance-modal>`.
- Sidebar: mục **"Lương"** (icon `payments`) — bản admin trong nhóm Hệ thống (`@can('admin')`); bản nhân viên trỏ tới `/luong/cua-toi`.

## 11. Ánh xạ mã nguồn (đề xuất)

| Thành phần | Vị trí |
|------------|--------|
| Controllers | `App\Http\Controllers\Payroll\{PayrollPeriodController, PayslipController, MyPayslipController}` |
| Models | `PayrollPeriod`, `Payslip`, `PayslipItem` |
| Service | `App\Services\PayrollService` (`calculatePeriod()`, `buildPayslip()`, `daysInMonth()`, `paidLeaveQuota()`, `unpaidDaysFor()`, `payViaFinance()`) |
| Form Requests | `StorePayrollPeriodRequest`, `StorePayslipItemRequest`, `PayPayrollRequest` |
| Migrations | `create_payroll_periods`, `create_payslips`, `create_payslip_items` |
| Routes | nhóm `payroll.*` dưới prefix `/luong`; thao tác quản trị `can:admin`, self-service `auth` |
| Tích hợp | tái dùng `FinanceService`/`FinanceTransaction` để sinh giao dịch chi (danh mục "Lương") |

## 12. Route đề xuất

| Method | URI | Route name | Middleware |
|--------|-----|------------|------------|
| GET | `/luong` | `payroll.periods.index` | auth, can:admin |
| POST | `/luong` | `payroll.periods.store` | auth, can:admin |
| GET | `/luong/cua-toi` | `payroll.my` | auth |
| GET | `/luong/{period}` | `payroll.periods.show` | auth, can:admin |
| POST | `/luong/{period}/tinh` | `payroll.periods.calculate` | auth, can:admin |
| PATCH | `/luong/{period}/duyet` | `payroll.periods.approve` | auth, can:admin |
| PATCH | `/luong/{period}/mo-lai` | `payroll.periods.reopen` | auth, can:admin |
| POST | `/luong/{period}/chi` | `payroll.periods.pay` | auth, can:admin |
| DELETE | `/luong/{period}` | `payroll.periods.destroy` | auth, can:admin |
| GET | `/luong/{period}/phieu/{payslip}` | `payroll.payslips.show` | auth (admin hoặc chủ phiếu) |
| POST | `/luong/{period}/phieu/{payslip}/khoan` | `payroll.payslips.items.store` | auth, can:admin |
| DELETE | `/luong/{period}/phieu/{payslip}/khoan/{item}` | `payroll.payslips.items.destroy` | auth, can:admin |

## 13. Phân kỳ triển khai

- **Phase 1 (MVP — tài liệu này):** kỳ lương tháng, prorate theo công + nghỉ phép, phiếu lương + khoản tay, duyệt, chi qua Finance, self-service xem phiếu.
- **Phase 2:** tự động phạt đi muộn, tăng ca, trừ tạm ứng; bảng `payroll_components` cấu hình khoản dùng lại; chi tách theo từng phiếu (BR-M11-21).
- **Phase 3:** BHXH/BHYT/BHTN + thuế TNCN theo luật VN; xuất PDF/Excel; đóng kỳ (`closed`); tác vụ nền nhắc kỳ chưa chốt.

## 14. Trường hợp kiểm thử tiêu biểu

- Tạo kỳ trùng (month, year) → lỗi validation "kỳ đã tồn tại".
- Tính lương: NV đủ công cả tháng (không nghỉ/vắng) → Lương theo công = `base_salary`.
- Quota phép co theo tỷ lệ đi làm: `round(6 × present_days / days_in_month)`; nghỉ ≤ quota → không trừ, nghỉ vượt quota → phần vượt tính `unpaid` và bị trừ `daily_rate`/ngày.
- Ví dụ B mục 9.2: tháng 30 ngày, nghỉ 10, đi làm 20 → quota 4 → phép 4 + không lương 6 → `paid_days = 24`, lương = base × 24/30.
- Kiểm tra làm tròn quota: đi làm 24/30 → `round(4.8)=5` (làm tròn gần nhất).
- `days_in_month` ghi đè thủ công → công thức dùng giá trị ghi đè.
- Thêm khoản trừ tay → net giảm; net không âm (nếu khấu trừ > gross → chốt 0 + cảnh báo).
- Duyệt kỳ → không cho sửa phiếu/thêm khoản (403/chặn).
- Chi lương → sinh 1 giao dịch chi = Σ net, danh mục "Lương", số dư quỹ giảm đúng; kỳ `paid`.
- Kỳ `paid` → chặn xoá & mở lại.
- Nhân viên xem `/luong/cua-toi` → chỉ thấy phiếu của mình ở kỳ approved/paid; mở phiếu người khác → 403.
- Nhân viên `resigned` → không được đưa vào kỳ khi tính.

## 15. Đồng bộ với các tài liệu SRS khác (sẽ cập nhật sau khi duyệt spec)

- **README / 01 — Tổng quan:** thêm M11 vào bản đồ module.
- **03 — Mô hình dữ liệu:** bổ sung 3 bảng `payroll_*` + quan hệ + enum vào ERD/từ điển.
- **04 — Phân quyền:** nhóm `payroll.*` quản trị = `can:admin`; self-service `/luong/cua-toi` = `auth` + kiểm tra chủ sở hữu.
- **05 — Route map & lộ trình:** thêm route `payroll.*`; chuyển hạng mục "Module Lương" từ lộ trình sang "đang hiện thực".
- **10 — Tài chính:** ghi rõ Payroll là nguồn sinh giao dịch chi (danh mục "Lương").
