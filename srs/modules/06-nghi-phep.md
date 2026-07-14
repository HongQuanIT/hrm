# M06 — Nghỉ phép

## 1. Mục tiêu

Cho phép nhân viên gửi đơn xin nghỉ theo nhiều loại, quản trị viên phê duyệt/từ chối; quản lý quỹ phép tháng/năm và cung cấp lịch nghỉ trực quan.

## 2. Phạm vi

- Gửi đơn nghỉ (6 loại).
- Xử lý quỹ phép tháng và tự tách phần vượt quỹ thành nghỉ không lương.
- Phê duyệt / từ chối / huỷ / xoá đơn.
- Lịch nghỉ theo tháng.
- Số dư phép tháng/năm.

## 3. Tác nhân

- **Người dùng**: gửi đơn, huỷ đơn của mình (khi chờ duyệt), xem đơn và số dư của mình.
- **Super Admin**: xem toàn công ty, phê duyệt/từ chối/huỷ/xoá.

## 4. Yêu cầu chức năng

### 4.1. Danh sách & số dư
| Mã | Yêu cầu |
|----|--------|
| FR-M06-01 | `GET /nghi-phep`: Super Admin thấy danh sách chờ duyệt (toàn công ty) + lịch sử; các chỉ số: phép đã duyệt trong tháng, số người nghỉ hôm nay, phép đã dùng trong năm. |
| FR-M06-02 | Người dùng thấy đơn chờ duyệt + lịch sử **của mình**, kèm số dư phép tháng (quỹ − đã dùng, cho phép âm). |
| FR-M06-03 | Lịch sử phân trang 10/trang; đơn chờ duyệt hiển thị đầy đủ. |

### 4.2. Gửi đơn
| Mã | Yêu cầu |
|----|--------|
| FR-M06-04 | `POST /nghi-phep`: tạo đơn với loại, ngày bắt đầu/kết thúc, lý do; số ngày tính tự động (bao gồm cả 2 đầu mút). |
| FR-M06-05 | Đơn tạo ra ở trạng thái `pending`. |
| FR-M06-06 | Loại `monthly` áp dụng logic tách quỹ (xem BR-M06-03..05). |

### 4.3. Lịch nghỉ
| Mã | Yêu cầu |
|----|--------|
| FR-M06-07 | `GET /nghi-phep/lich`: hiển thị lưới lịch tháng (bắt đầu Thứ Hai) với các đơn `approved` + `pending` chồng lên từng ngày. |
| FR-M06-08 | Super Admin thấy lịch toàn công ty; người dùng chỉ thấy của mình. |
| FR-M06-09 | Điều hướng tháng trước/sau. |

### 4.4. Phê duyệt & thao tác
| Mã | Yêu cầu | Quyền |
|----|--------|-------|
| FR-M06-10 | `PATCH /nghi-phep/{leave}/trang-thai`: đổi trạng thái sang `approved`/`rejected`/`cancelled`; ghi `approver_name`. | can:admin |
| FR-M06-11 | `PATCH /nghi-phep/{leave}/huy`: người dùng huỷ **đơn của mình** khi đang `pending`. | auth (chủ đơn) |
| FR-M06-12 | `DELETE /nghi-phep/{leave}`: Super Admin xoá đơn. | can:admin |

## 5. Quy tắc nghiệp vụ

| Mã | Quy tắc |
|----|--------|
| BR-M06-01 | Số ngày = `end_date − start_date + 1`. `end_date ≥ start_date`. |
| BR-M06-02 | Quỹ phép tháng đã dùng chỉ tính loại `monthly`, trạng thái `approved` + `pending`, trong tháng của ngày bắt đầu. |
| BR-M06-03 | **Đơn `monthly` còn đủ quỹ** (remaining ≥ days) → ghi nhận toàn bộ là `monthly`. |
| BR-M06-04 | **Đơn `monthly` hết quỹ** (remaining ≤ 0) → toàn bộ chuyển thành **`unpaid`** (nghỉ không lương). |
| BR-M06-05 | **Đơn `monthly` vượt một phần** → **tách 2 đơn**: phần còn quỹ là `monthly`, phần vượt (những ngày sau) là `unpaid`. |
| BR-M06-06 | Người dùng chỉ huỷ được đơn **của chính mình** và khi đơn `pending`; ngược lại HTTP 403. |
| BR-M06-07 | Khi duyệt/từ chối, hệ thống ghi tên người duyệt vào `approver_name`. |
| BR-M06-08 | Nghỉ phép đã duyệt ảnh hưởng chấm công: ngày đó không cần chấm công và bị bỏ qua khi chốt "vắng mặt". |
| BR-M06-09 | Số dư phép có thể **âm** (nghỉ vượt quỹ quy định). |

### Các loại nghỉ
`monthly` (Nghỉ phép tháng), `annual` (Phép năm), `sick` (Nghỉ ốm), `unpaid` (Không lương), `maternity` (Thai sản), `remote` (Làm từ xa).

## 6. Ràng buộc dữ liệu (validation)

| Trường | Quy tắc |
|--------|---------|
| `type` | bắt buộc, ∈ danh mục loại nghỉ |
| `start_date` | bắt buộc, ngày hợp lệ |
| `end_date` | bắt buộc, ngày, ≥ `start_date` |
| `reason` | tuỳ chọn, ≤500 |
| `status` (khi duyệt) | ∈ {approved, rejected, cancelled} |

## 7. Luồng xử lý (Gửi đơn `monthly` vượt quỹ)

1. Người dùng chọn loại "Nghỉ phép tháng", khoảng ngày, lý do → gửi.
2. Hệ thống tính số ngày và số ngày `monthly` đã dùng trong tháng.
3. `remaining = quota − used`.
4. Nếu `remaining ≥ days` → 1 đơn `monthly`.
5. Nếu `remaining ≤ 0` → 1 đơn `unpaid`.
6. Nếu `0 < remaining < days` → tạo 2 đơn: `monthly` (remaining ngày đầu) + `unpaid` (phần còn lại), cùng `pending`.
7. Thông báo kết quả tách đơn cho người dùng.

## 8. Giao diện liên quan

- `leaves/index.blade.php` — danh sách, form gửi đơn, bảng phê duyệt (admin).
- `leaves/calendar.blade.php` — lịch nghỉ tháng.

## 9. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/LeaveController.php` (`index`, `calendar`, `store`, `storeMonthlyLeave`, `monthlyUsedDays`, `updateStatus`, `cancel`, `destroy`) |
| Model | `app/Models/LeaveRequest.php` |
| Route | `routes/web.php` (`leaves.*`) |

## 10. Trường hợp kiểm thử tiêu biểu

- Gửi `monthly` 1 ngày khi còn quỹ → 1 đơn `monthly` pending.
- Gửi `monthly` 3 ngày khi quỹ còn 1 → tách: 1 ngày `monthly` + 2 ngày `unpaid`.
- Gửi `monthly` khi hết quỹ → toàn bộ `unpaid`.
- Người dùng huỷ đơn `pending` của mình → thành công; huỷ đơn người khác → 403.
- Admin duyệt đơn → trạng thái `approved`, `approver_name` được ghi, ngày nghỉ bỏ qua khi chốt công.
- `end_date < start_date` → lỗi validation.
