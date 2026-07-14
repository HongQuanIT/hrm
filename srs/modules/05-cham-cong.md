# M05 — Chấm công

## 1. Mục tiêu

Ghi nhận giờ vào/ra hằng ngày của nhân viên, tự động phân loại trạng thái (đúng giờ, đi muộn, vắng mặt, quên check-out) theo chính sách cấu hình, và chốt công cuối ngày cho những người không chấm công.

## 2. Phạm vi

- Xem lịch sử chấm công theo tháng, thống kê cá nhân, xu hướng 6 tháng.
- Tự chấm công vào (check-in) và ra (check-out).
- Chốt công tự động (đánh dấu vắng mặt / quên check-out).

## 3. Tác nhân

- **Người dùng**: tự check-in/out, xem lịch sử của mình.
- **Super Admin**: xem lịch sử toàn công ty; cũng có thể tự chấm công.
- **Hệ thống**: chốt công cuối ngày (self-gated / lịch biểu 23:30).

## 4. Yêu cầu chức năng

### 4.1. Xem & thống kê
| Mã | Yêu cầu |
|----|--------|
| FR-M05-01 | `GET /cham-cong`: hiển thị lịch sử chấm công theo tháng đang chọn (mặc định tháng hiện tại, không cho chọn tương lai), phân trang 15/trang. |
| FR-M05-02 | Super Admin thấy lịch sử **toàn bộ** nhân viên; người dùng chỉ thấy **của mình**. |
| FR-M05-03 | Hiển thị chỉ số cá nhân theo tháng: số ngày công, ngày công chuẩn, số lần đi muộn, giờ tăng ca, số dư phép năm, bản ghi hôm nay. |
| FR-M05-04 | Hiển thị mốc giờ cấu hình (giờ vào/ra, giờ mở check-in, hạn check-in) và biểu đồ xu hướng 6 tháng. |
| FR-M05-05 | Trước khi hiển thị, gọi `AttendanceCloser::run()` để đồng bộ trạng thái. |

### 4.2. Check-in
| Mã | Yêu cầu |
|----|--------|
| FR-M05-06 | `POST /cham-cong/check-in`: ghi giờ vào cho ngày hôm nay của nhân viên hiện tại. |
| FR-M05-07 | Chặn check-in nếu tài khoản chưa gắn hồ sơ nhân viên. |
| FR-M05-08 | Chặn check-in nếu hôm nay đang trong kỳ nghỉ phép đã duyệt. |
| FR-M05-09 | Chặn check-in nếu chưa đến giờ mở (`checkin_open_time`). |
| FR-M05-10 | Chặn check-in lần hai (đã có giờ vào). |
| FR-M05-11 | Phân loại trạng thái theo giờ vào (xem BR). |

### 4.3. Check-out
| Mã | Yêu cầu |
|----|--------|
| FR-M05-12 | `POST /cham-cong/check-out`: ghi giờ ra và tính tổng phút làm việc. |
| FR-M05-13 | Chặn nếu chưa check-in hoặc đã check-out. |
| FR-M05-14 | Nếu quá `checkout_deadline`, tự chốt giờ ra về mốc hạn chót (cap). |
| FR-M05-15 | Cảnh báo/ghi chú "về sớm" nếu ra trước `work_end_time`. |

## 5. Quy tắc nghiệp vụ

### 5.1. Phân loại khi check-in
Gọi: `start = work_start_time` (mặc định 08:00), `grace = late_grace_minutes` (5), `deadline = checkin_deadline` (10:00).

| Điều kiện giờ vào `now` | Trạng thái | Ghi chú |
|-------------------------|-----------|---------|
| `now > deadline` | `absent` | Muộn quá hạn chót → tính **vắng mặt** dù đã bấm vào; ghi `late_minutes` |
| `now > start + grace` (nhưng ≤ deadline) | `late` | Đi muộn; lưu `late_minutes = now − start` |
| `now ≤ start + grace` | `working` | Đúng giờ / trong ân hạn; `late_minutes = 0` |

| Mã | Quy tắc |
|----|--------|
| BR-M05-01 | Mỗi nhân viên chỉ một bản ghi/ngày (unique `employee_id + work_date`); check-in tạo mới hoặc dùng bản ghi có sẵn. |
| BR-M05-02 | `late_minutes` = số phút chênh từ giờ bắt đầu tới giờ vào (chỉ khi vào sau giờ bắt đầu). |

### 5.2. Check-out
| Mã | Quy tắc |
|----|--------|
| BR-M05-03 | Giờ ra hiệu lực = `min(now, checkout_deadline)`; nếu bị cap, thông báo "hệ thống tự chốt giờ ra". |
| BR-M05-04 | `total_minutes = max(giờ_ra_hiệu_lực − giờ_vào, 0)`. |
| BR-M05-05 | Nếu trạng thái đang là `working` thì chuyển thành `on_time` khi check-out; các trạng thái `late`/`absent` giữ nguyên. |
| BR-M05-06 | Ra trước `work_end_time` → thêm ghi chú "về sớm N phút". |

### 5.3. Chốt công tự động (`AttendanceCloser`)
| Mã | Quy tắc |
|----|--------|
| BR-M05-07 | Self-gated qua `attendance_closed_through`: mỗi lần chỉ xử lý ngày còn thiếu, giới hạn truy hồi tối đa **31 ngày**. |
| BR-M05-08 | Hôm nay chỉ được chốt khi đã qua `checkin_deadline`; nếu chưa, chỉ chốt đến hôm qua. |
| BR-M05-09 | Bỏ qua **cuối tuần**, nhân viên `resigned`, người đã có bản ghi trong ngày, và người đang nghỉ phép đã duyệt. |
| BR-M05-10 | Nhân viên đủ điều kiện mà không chấm công → tạo bản ghi `absent` (ghi chú "Không chấm công trong ngày làm việc"). |
| BR-M05-11 | Ngày đã qua mà có check-in nhưng thiếu check-out → chuyển `missing_checkout`, `total_minutes = 0` (không tính công). Ngày hôm nay không xử lý (vẫn "đang làm việc"). |

### 5.4. Mức độ đi muộn (hiển thị)
| Mã | Quy tắc |
|----|--------|
| BR-M05-12 | `late_level` (1–3) tính theo ngưỡng `late_level1_minutes`, `late_level2_minutes` trong cấu hình; dùng để hiển thị nhãn mức độ đi muộn. |

## 6. Ràng buộc dữ liệu

- Giờ lưu định dạng `H:i:s`; ngày `work_date` là date.
- `total_minutes`, `late_minutes` ≥ 0.
- Không cho xem/chọn tháng ở tương lai.

## 7. Giao diện liên quan

- `attendance/index.blade.php` — thẻ check-in/out, lịch sử tháng, thống kê, biểu đồ.

## 8. Ánh xạ mã nguồn

| Thành phần | Vị trí |
|------------|--------|
| Controller | `app/Http/Controllers/AttendanceController.php` (`index`, `checkin`, `checkout`, `selectedMonth`, `onApprovedLeave`, `timeOn`) |
| Service | `app/Services/AttendanceCloser.php` (`run`, `closeDay`, `resolveMissingCheckouts`) |
| Command | `app/Console/Commands/CloseAttendanceDay.php` (`attendance:close-day`) |
| Lịch biểu | `routes/console.php` — daily 23:30 |
| Model | `app/Models/Attendance.php` |
| Route | `routes/web.php` (`attendance.*`) |

## 9. Trường hợp kiểm thử tiêu biểu

- Check-in lúc 08:03 (ân hạn 5') → `working`, không tính muộn.
- Check-in lúc 08:20 → `late`, `late_minutes = 20`.
- Check-in lúc 10:30 (sau hạn 10:00) → `absent`.
- Check-in khi đang nghỉ phép đã duyệt → bị chặn.
- Check-out trước 17:30 → ghi chú "về sớm".
- Không bấm ra, chạy chốt hôm sau → `missing_checkout`, `total_minutes=0`.
- Không chấm công cả ngày làm việc → chốt `absent`; cuối tuần không tạo bản ghi.
