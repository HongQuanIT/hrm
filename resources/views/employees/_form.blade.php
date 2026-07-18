@php
    $e = $employee ?? null;
    $val = fn ($field, $default = '') => old($field, $e->{$field} ?? $default);
@endphp
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <div class="mb-lg">
            <a href="{{ route('employees.index') }}" class="flex items-center text-primary font-label-md text-label-md mb-xs hover:underline gap-1 w-fit">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Quay lại danh sách
            </a>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">{{ $e ? 'Chỉnh sửa hồ sơ nhân viên' : 'Tạo hồ sơ nhân viên mới' }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Vui lòng điền đầy đủ các thông tin bắt buộc.</p>
        </div>

        @if ($errors->any())
            <div class="mb-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">
                <p class="font-semibold mb-xs">Vui lòng kiểm tra lại thông tin:</p>
                <ul class="list-disc list-inside text-body-md">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="space-y-xl">
            @csrf
            @if (($method ?? 'POST') === 'PUT')
                @method('PUT')
            @endif

            <!-- Personal Info -->
            <div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-xl py-lg border-b border-outline-variant flex items-center gap-sm bg-surface-container-low">
                    <span class="material-symbols-outlined text-primary">person</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface">Thông tin cá nhân</h2>
                </div>
                <div class="p-xl grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Họ và tên *</label>
                        <input name="name" value="{{ $val('name') }}" required type="text" placeholder="Nhập đầy đủ họ tên"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Giới tính</label>
                        <select name="gender" class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all bg-white">
                            <option value="">Chọn giới tính</option>
                            @foreach (\App\Models\Employee::GENDER_LABELS as $key => $label)
                                <option value="{{ $key }}" @selected($val('gender') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Ngày sinh</label>
                        <input name="dob" value="{{ old('dob', $e?->dob?->format('Y-m-d')) }}" type="date"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Số điện thoại</label>
                        <input name="phone" value="{{ $val('phone') }}" type="tel" placeholder="090x xxx xxx"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Email công việc *</label>
                        <input name="email" value="{{ $val('email') }}" required type="email" placeholder="name@HRM.vn"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Email cá nhân</label>
                        <input name="personal_email" value="{{ $val('personal_email') }}" type="email" placeholder="example@gmail.com"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Số CCCD / CMND</label>
                        <input name="national_id" value="{{ $val('national_id') }}" type="text" placeholder="Nhập số căn cước"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Tình trạng hôn nhân</label>
                        <input name="marital_status" value="{{ $val('marital_status') }}" type="text" placeholder="Độc thân / Đã kết hôn"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs md:col-span-2">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Địa chỉ thường trú</label>
                        <input name="permanent_address" value="{{ $val('permanent_address') }}" type="text" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs md:col-span-2">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Địa chỉ tạm trú</label>
                        <input name="temporary_address" value="{{ $val('temporary_address') }}" type="text"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs md:col-span-2">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Người liên hệ khẩn cấp</label>
                        <input name="emergency_contact" value="{{ $val('emergency_contact') }}" type="text" placeholder="Họ tên (Quan hệ) - Số điện thoại"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                </div>
            </div>

            <!-- Work Info -->
            <div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-xl py-lg border-b border-outline-variant flex items-center gap-sm bg-surface-container-low">
                    <span class="material-symbols-outlined text-primary">work</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface">Thông tin công việc</h2>
                </div>
                <div class="p-xl grid grid-cols-1 md:grid-cols-3 gap-lg">
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Mã nhân viên *</label>
                        <input name="code" value="{{ $val('code', $nextCode ?? '') }}" required type="text"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all bg-surface-container-lowest font-semibold">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Phòng ban</label>
                        <select name="department_id" class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all bg-white">
                            <option value="">Chọn phòng ban</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" @selected($val('department_id') == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Vị trí / Chức vụ</label>
                        <input name="position" value="{{ $val('position') }}" type="text" placeholder="Ví dụ: Senior Developer"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Cấp bậc</label>
                        <input name="level" value="{{ $val('level') }}" type="text" placeholder="Ví dụ: L4 - Specialist"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Loại hợp đồng</label>
                        <select name="contract_type" class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all bg-white">
                            @foreach (['Không xác định thời hạn', 'Xác định thời hạn (1-3 năm)', 'Thử việc', 'Cộng tác viên'] as $ct)
                                <option value="{{ $ct }}" @selected($val('contract_type') === $ct)>{{ $ct }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Ngày vào làm</label>
                        <input name="join_date" value="{{ old('join_date', $e?->join_date?->format('Y-m-d')) }}" type="date"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Người quản lý trực tiếp</label>
                        <select name="manager_id" class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all bg-white">
                            <option value="">Chọn người quản lý</option>
                            @foreach ($managers as $m)
                                <option value="{{ $m->id }}" @selected($val('manager_id') == $m->id)>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Trạng thái *</label>
                        <select name="status" class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all bg-white">
                            @foreach (\App\Models\Employee::STATUS_LABELS as $key => $label)
                                <option value="{{ $key }}" @selected($val('status', 'active') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Mật khẩu đăng nhập{{ $e ? ' mới' : '' }}</label>
                        <input name="password" type="password" autocomplete="new-password"
                               placeholder="{{ $e ? 'Để trống nếu giữ nguyên' : 'Bỏ trống: dùng "password"' }}"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                        <p class="text-[11px] text-outline">
                            @if ($e)
                                Chỉ điền khi muốn đặt lại mật khẩu cho tài khoản của nhân viên này.
                            @else
                                Tài khoản đăng nhập dùng email công việc. Bỏ trống sẽ dùng mật khẩu mặc định "password".
                            @endif
                        </p>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Quốc tịch</label>
                        <input name="nationality" value="{{ $val('nationality', 'Việt Nam') }}" type="text"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs md:col-span-3">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Kỹ năng &amp; Chuyên môn (phân cách bởi dấu phẩy)</label>
                        <input name="skills" value="{{ old('skills', $e ? implode(', ', $e->skills ?? []) : '') }}" type="text" placeholder="Tuyển dụng, Phỏng vấn, Tiếng Anh (C1)"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                </div>
            </div>

            <!-- Bank Info -->
            <div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-xl py-lg border-b border-outline-variant flex items-center gap-sm bg-surface-container-low">
                    <span class="material-symbols-outlined text-primary">account_balance</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface">Thông tin ngân hàng &amp; Lương</h2>
                </div>
                <div class="p-xl grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Tên ngân hàng</label>
                        <input name="bank_name" value="{{ $val('bank_name') }}" type="text" placeholder="Ví dụ: Vietcombank, Techcombank..."
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Số tài khoản</label>
                        <input name="bank_account" value="{{ $val('bank_account') }}" type="text" placeholder="000100xxxxxxx"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Chủ tài khoản</label>
                        <input name="bank_holder" value="{{ $val('bank_holder') }}" type="text" placeholder="VIET IN HOA KHONG DAU"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Lương cơ bản (Gross)</label>
                        <div class="relative">
                            <input name="base_salary" value="{{ $val('base_salary') }}" type="number" step="1000" placeholder="0"
                                   class="w-full h-12 pl-md pr-12 border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-medium">VND</span>
                        </div>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Phụ cấp (ăn trưa, đi lại, chỗ ở...)</label>
                        <div class="relative">
                            <input name="lunch_allowance" value="{{ $val('lunch_allowance') }}" type="number" step="1000" placeholder="730000"
                                   class="w-full h-12 pl-md pr-12 border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-medium">VND</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-md">
                <a href="{{ route('employees.index') }}" class="h-12 px-lg flex items-center border border-outline-variant rounded-lg font-medium text-on-surface-variant hover:bg-surface-container-high active:scale-95 transition-all">Hủy bỏ</a>
                <button type="submit" class="h-12 px-xl bg-primary text-on-primary rounded-lg font-medium shadow-[0_4px_12px_rgba(0,74,198,0.25)] hover:bg-on-primary-fixed-variant active:scale-95 transition-all flex items-center justify-center gap-sm">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    Lưu hồ sơ
                </button>
            </div>
        </form>
    </div>
</div>
