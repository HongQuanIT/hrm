@extends('layouts.app')

@section('title', 'Cài đặt hệ thống')
@section('page-title', 'Cài đặt hệ thống')

@php
    $deptColors = [
        ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
        ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
        ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
        ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
        ['bg' => 'bg-pink-100', 'text' => 'text-pink-700'],
    ];
@endphp

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto flex flex-col lg:flex-row gap-lg">
        <!-- Sub navigation -->
        <nav class="lg:w-72 flex-shrink-0">
            <div class="flex lg:flex-col gap-base overflow-x-auto lg:overflow-visible">
                <button type="button" onclick="switchTab('company')" id="tab-company" class="settings-tab flex-shrink-0 w-full text-left flex items-center gap-md p-md rounded-xl transition-all hover:bg-surface-container-high group active-tab">
                    <span class="material-symbols-outlined text-primary">business</span>
                    <div>
                        <p class="font-body-md text-body-md font-semibold">Hồ sơ công ty</p>
                        <p class="text-[11px] text-outline">Thông tin pháp lý &amp; Nhận diện</p>
                    </div>
                </button>
                <button type="button" onclick="switchTab('attendance')" id="tab-attendance" class="settings-tab flex-shrink-0 w-full text-left flex items-center gap-md p-md rounded-xl transition-all hover:bg-surface-container-high group text-on-surface-variant">
                    <span class="material-symbols-outlined">schedule</span>
                    <div>
                        <p class="font-body-md text-body-md font-semibold">Giờ làm việc &amp; Chấm công</p>
                        <p class="text-[11px] text-outline">Giờ vào/ra &amp; Quy tắc đi muộn</p>
                    </div>
                </button>
                <button type="button" onclick="switchTab('departments')" id="tab-departments" class="settings-tab flex-shrink-0 w-full text-left flex items-center gap-md p-md rounded-xl transition-all hover:bg-surface-container-high group text-on-surface-variant">
                    <span class="material-symbols-outlined">account_tree</span>
                    <div>
                        <p class="font-body-md text-body-md font-semibold">Phòng ban &amp; Chức vụ</p>
                        <p class="text-[11px] text-outline">Cơ cấu tổ chức &amp; Sơ đồ</p>
                    </div>
                </button>
                <button type="button" onclick="switchTab('permissions')" id="tab-permissions" class="settings-tab flex-shrink-0 w-full text-left flex items-center gap-md p-md rounded-xl transition-all hover:bg-surface-container-high group text-on-surface-variant">
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                    <div>
                        <p class="font-body-md text-body-md font-semibold">Phân quyền người dùng</p>
                        <p class="text-[11px] text-outline">Vai trò &amp; Quyền truy cập</p>
                    </div>
                </button>
                <button type="button" onclick="switchTab('holidays')" id="tab-holidays" class="settings-tab flex-shrink-0 w-full text-left flex items-center gap-md p-md rounded-xl transition-all hover:bg-surface-container-high group text-on-surface-variant">
                    <span class="material-symbols-outlined">event</span>
                    <div>
                        <p class="font-body-md text-body-md font-semibold">Ngày nghỉ lễ</p>
                        <p class="text-[11px] text-outline">Loại trừ khỏi ngày công</p>
                    </div>
                </button>
            </div>
        </nav>

        <!-- Detail -->
        <section class="flex-1 min-w-0">
            @if ($errors->any())
                <div class="mb-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">
                    <ul class="list-disc list-inside text-body-md">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- COMPANY -->
            <div id="section-company">
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <div class="mb-lg flex flex-col sm:flex-row justify-between sm:items-end gap-md">
                        <div>
                            <h3 class="font-headline-lg text-headline-lg text-on-surface">Hồ sơ công ty</h3>
                            <p class="text-body-md text-outline">Cập nhật thông tin nhận diện và các thông số hoạt động của doanh nghiệp.</p>
                        </div>
                        <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-xl font-body-md hover:shadow-lg transition-all flex items-center gap-xs w-fit">
                            <span class="material-symbols-outlined text-[20px]">save</span> Lưu thay đổi
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
                        <div class="md:col-span-1 bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col items-center">
                            <div class="relative group">
                                <div class="w-32 h-32 rounded-2xl bg-primary-container flex items-center justify-center text-on-primary-container">
                                    <span class="material-symbols-outlined text-6xl" style="font-variation-settings: 'FILL' 1;">pulse_alert</span>
                                </div>
                            </div>
                            <div class="mt-lg text-center">
                                <p class="font-body-md text-body-md font-bold text-on-surface">Logo công ty</p>
                                <p class="text-[12px] text-outline mt-base">Định dạng PNG, JPG. Tối đa 2MB.</p>
                            </div>
                            <div class="w-full mt-lg pt-lg border-t border-outline-variant">
                                <label class="block text-label-md font-label-md text-outline mb-xs">Màu thương hiệu</label>
                                <div class="flex gap-xs">
                                    <div class="w-8 h-8 rounded-full bg-primary border-2 border-white shadow-sm ring-2 ring-primary-container"></div>
                                    <div class="w-8 h-8 rounded-full bg-secondary border-2 border-white shadow-sm"></div>
                                    <div class="w-8 h-8 rounded-full bg-tertiary border-2 border-white shadow-sm"></div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2 bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                            <div class="grid grid-cols-2 gap-lg">
                                <div class="col-span-2">
                                    <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Tên chính thức (Tiếng Việt)</label>
                                    <input name="company_name" value="{{ old('company_name', $settings['company_name'] ?? 'Công ty Cổ phần Giải pháp Công nghệ HRM') }}" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none" type="text"/>
                                </div>
                                <div>
                                    <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Mã số thuế</label>
                                    <input name="tax_code" value="{{ old('tax_code', $settings['tax_code'] ?? '0101234567') }}" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none" type="text"/>
                                </div>
                                <div>
                                    <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Website</label>
                                    <input name="website" value="{{ old('website', $settings['website'] ?? 'www.HRM.vn') }}" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none" type="text"/>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Địa chỉ trụ sở</label>
                                    <input name="address" value="{{ old('address', $settings['address'] ?? 'Tòa nhà Innovation, Công viên Phần mềm Quang Trung, Quận 12, TP.HCM') }}" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none" type="text"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leave policy -->
                    <div class="mt-lg bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                        <div class="flex items-center gap-xs mb-md">
                            <span class="material-symbols-outlined text-primary">event_available</span>
                            <h4 class="font-headline-md text-headline-md text-on-surface">Chính sách nghỉ phép</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Số ngày nghỉ phép / tháng</label>
                                <input name="leave_days_per_month" type="number" min="0" max="31"
                                       value="{{ old('leave_days_per_month', $settings['leave_days_per_month'] ?? 1) }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Dùng để tính ngày công chuẩn: <span class="font-medium">số ngày trong tháng − số ngày phép tháng</span>.</p>
                            </div>
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Số ngày nghỉ phép / năm</label>
                                <input name="leave_days_per_year" type="number" min="0" max="365"
                                       value="{{ old('leave_days_per_year', $settings['leave_days_per_year'] ?? 12) }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Tổng quỹ phép năm, dùng cho số dư nghỉ phép của nhân viên.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ATTENDANCE / WORKING HOURS -->
            <div class="hidden" id="section-attendance">
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <div class="mb-lg flex flex-col sm:flex-row justify-between sm:items-end gap-md">
                        <div>
                            <h3 class="font-headline-lg text-headline-lg text-on-surface">Giờ làm việc &amp; Chấm công</h3>
                            <p class="text-body-md text-outline">Cấu hình khung giờ làm việc và quy tắc xử lý check-in/check-out.</p>
                        </div>
                        <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-xl font-body-md hover:shadow-lg transition-all flex items-center gap-xs w-fit">
                            <span class="material-symbols-outlined text-[20px]">save</span> Lưu thay đổi
                        </button>
                    </div>

                    <!-- Working hours -->
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm mb-lg">
                        <div class="flex items-center gap-xs mb-md">
                            <span class="material-symbols-outlined text-primary">work_history</span>
                            <h4 class="font-headline-md text-headline-md text-on-surface">Khung giờ làm việc</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Giờ mở check-in</label>
                                <input name="checkin_open_time" type="time"
                                       value="{{ old('checkin_open_time', $settings['checkin_open_time'] ?? '07:00') }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Bắt đầu cho phép check-in. Phải <span class="font-medium">sớm hơn</span> giờ bắt đầu làm việc.</p>
                            </div>
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Giờ bắt đầu làm việc</label>
                                <input name="work_start_time" type="time"
                                       value="{{ old('work_start_time', $settings['work_start_time'] ?? '08:00') }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Mốc chuẩn để xác định đi muộn.</p>
                            </div>
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Giờ kết thúc làm việc</label>
                                <input name="work_end_time" type="time"
                                       value="{{ old('work_end_time', $settings['work_end_time'] ?? '17:30') }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Check-out trước mốc này sẽ bị đánh dấu <span class="font-medium">về sớm</span>.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Deadlines -->
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm mb-lg">
                        <div class="flex items-center gap-xs mb-md">
                            <span class="material-symbols-outlined text-primary">timer_off</span>
                            <h4 class="font-headline-md text-headline-md text-on-surface">Hạn chót chấm công</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Hạn chót check-in</label>
                                <input name="checkin_deadline" type="time"
                                       value="{{ old('checkin_deadline', $settings['checkin_deadline'] ?? '10:00') }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Check-in <span class="font-medium">sau</span> mốc này ⇒ tính <span class="font-medium text-error">Vắng mặt</span>.</p>
                            </div>
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Hạn chót check-out</label>
                                <input name="checkout_deadline" type="time"
                                       value="{{ old('checkout_deadline', $settings['checkout_deadline'] ?? '22:00') }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Quên bấm ra: hệ thống tự chốt giờ ra về mốc này.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Late warning levels -->
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                        <div class="flex items-center gap-xs mb-md">
                            <span class="material-symbols-outlined text-primary">notifications_active</span>
                            <h4 class="font-headline-md text-headline-md text-on-surface">Mức cảnh báo đi muộn</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Ân hạn (phút)</label>
                                <input name="late_grace_minutes" type="number" min="0" max="120"
                                       value="{{ old('late_grace_minutes', $settings['late_grace_minutes'] ?? 5) }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Trong khoảng này vẫn tính <span class="font-medium">Đúng giờ</span>.</p>
                            </div>
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Ngưỡng muộn nhẹ (phút)</label>
                                <input name="late_level1_minutes" type="number" min="1" max="240"
                                       value="{{ old('late_level1_minutes', $settings['late_level1_minutes'] ?? 15) }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Muộn ≤ mốc này: <span class="font-medium text-yellow-600">Muộn nhẹ</span>.</p>
                            </div>
                            <div>
                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Ngưỡng muộn vừa (phút)</label>
                                <input name="late_level2_minutes" type="number" min="1" max="480"
                                       value="{{ old('late_level2_minutes', $settings['late_level2_minutes'] ?? 30) }}"
                                       class="w-full px-md py-sm rounded-lg border border-outline-variant focus:ring-primary/20 focus:border-primary transition-all text-body-md outline-none"/>
                                <p class="text-[12px] text-outline mt-xs">Muộn ≤ mốc này: <span class="font-medium text-orange-600">Muộn</span>; vượt: <span class="font-medium text-red-600">Muộn nghiêm trọng</span>.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- DEPARTMENTS -->
            <div class="hidden" id="section-departments">
                <div class="mb-lg flex flex-col sm:flex-row justify-between sm:items-center gap-md">
                    <div>
                        <h3 class="font-headline-lg text-headline-lg text-on-surface">Quản lý Phòng ban &amp; Chức vụ</h3>
                        <p class="text-body-md text-outline">Xây dựng cơ cấu tổ chức và các cấp bậc chức danh.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('add-dept-form').classList.toggle('hidden')" class="px-md py-sm bg-primary-container text-on-primary-container rounded-xl font-label-md flex items-center gap-xs hover:bg-primary hover:text-on-primary transition-all w-fit">
                        <span class="material-symbols-outlined">add_circle</span> Thêm phòng ban
                    </button>
                </div>

                <form method="POST" action="{{ route('settings.departments.store') }}" id="add-dept-form" class="hidden mb-lg bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-md items-end">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Tên phòng ban *</label>
                            <input name="name" required type="text" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Mã phòng *</label>
                            <input name="code" required type="text" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Trưởng bộ phận</label>
                            <select name="head_employee_id" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                                <option value="">— Chưa chọn —</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected(old('head_employee_id') == $emp->id)>{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md h-fit">Lưu</button>
                    </div>
                </form>

                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-container-low border-b border-outline-variant">
                            <tr>
                                <th class="px-lg py-md font-label-md text-on-surface">Tên phòng ban</th>
                                <th class="px-lg py-md font-label-md text-on-surface">Mã phòng</th>
                                <th class="px-lg py-md font-label-md text-on-surface">Trưởng bộ phận</th>
                                <th class="px-lg py-md font-label-md text-on-surface">Nhân sự</th>
                                <th class="px-lg py-md font-label-md text-on-surface text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($departments as $dept)
                                @php $c = $deptColors[$loop->index % count($deptColors)]; $headName = $dept->head_display; @endphp
                                <tr class="hover:bg-surface-container transition-colors group">
                                    <td class="px-lg py-md">
                                        <div class="flex items-center gap-md">
                                            <div class="w-8 h-8 rounded-lg {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center font-bold text-xs">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($dept->code, 0, 2)) }}</div>
                                            <span class="font-body-md text-on-surface font-medium">{{ $dept->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md font-body-md text-on-surface-variant">{{ $dept->code }}</td>
                                    <td class="px-lg py-md">
                                        <div class="flex items-center gap-xs">
                                            <x-avatar :name="$headName ?: '—'" class="w-6 h-6 text-[10px]" />
                                            <span class="font-body-md text-on-surface-variant">{{ $headName ?: '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md">
                                        <span class="px-sm py-1 bg-secondary-container/50 text-on-secondary-container rounded-full text-xs font-bold">{{ $dept->employees_count }}</span>
                                    </td>
                                    <td class="px-lg py-md text-right">
                                        <div class="flex items-center justify-end gap-xs">
                                            <button type="button" onclick="toggleDeptEdit({{ $dept->id }})" class="p-xs text-outline hover:text-primary transition-colors" title="Chỉnh sửa">
                                                <span class="material-symbols-outlined text-[20px]">edit</span>
                                            </button>
                                            <form method="POST" action="{{ route('settings.departments.destroy', $dept) }}" onsubmit="return confirm('Xóa phòng ban này?')" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="p-xs text-outline hover:text-error transition-colors" title="Xoá">
                                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="dept-edit-{{ $dept->id }}" class="hidden bg-surface-container-low">
                                    <td colspan="5" class="px-lg py-md">
                                        <form method="POST" action="{{ route('settings.departments.update', $dept) }}" class="grid grid-cols-1 md:grid-cols-4 gap-md items-end">
                                            @csrf @method('PUT')
                                            <div>
                                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Tên phòng ban *</label>
                                                <input name="name" required type="text" value="{{ $dept->name }}" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md"/>
                                            </div>
                                            <div>
                                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Mã phòng *</label>
                                                <input name="code" required type="text" value="{{ $dept->code }}" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md"/>
                                            </div>
                                            <div>
                                                <label class="block text-label-md font-label-md text-on-surface-variant mb-xs">Trưởng bộ phận</label>
                                                <select name="head_employee_id" class="w-full px-md py-sm rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                                                    <option value="">— Chưa chọn —</option>
                                                    @foreach ($employees as $emp)
                                                        <option value="{{ $emp->id }}" @selected($dept->head_employee_id == $emp->id)>{{ $emp->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex gap-xs">
                                                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md h-fit">Cập nhật</button>
                                                <button type="button" onclick="toggleDeptEdit({{ $dept->id }})" class="px-lg py-sm border border-outline-variant rounded-lg font-label-md text-label-md h-fit text-on-surface-variant">Đóng</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-lg py-xl text-center text-on-surface-variant">Chưa có phòng ban nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PERMISSIONS -->
            <div class="hidden" id="section-permissions">
                <div class="mb-lg">
                    <h3 class="font-headline-lg text-headline-lg text-on-surface">Phân quyền người dùng</h3>
                    <p class="text-body-md text-outline">Gán vai trò cho từng tài khoản. Vai trò quyết định quyền truy cập trên toàn hệ thống.</p>
                </div>

                <!-- Role definitions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg mb-lg">
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-primary/20 ring-1 ring-primary/10 shadow-md">
                        <div class="flex justify-between items-start mb-md">
                            <div class="flex items-center gap-sm">
                                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">shield_with_heart</span>
                                <h4 class="font-headline-md text-headline-md text-on-surface">Super Admin</h4>
                            </div>
                            <span class="px-sm py-xs bg-primary/10 text-primary rounded-lg font-label-md text-label-md uppercase">Toàn quyền</span>
                        </div>
                        <p class="text-body-md text-on-surface-variant mb-lg">Có toàn quyền truy cập và chỉnh sửa mọi module, xem chấm công toàn công ty, duyệt đơn và cấu hình hệ thống.</p>
                        <div class="flex flex-wrap gap-xs">
                            <span class="px-sm py-1 bg-surface-container-high text-outline rounded-full text-[11px]">Thêm/sửa/xoá nhân viên</span>
                            <span class="px-sm py-1 bg-surface-container-high text-outline rounded-full text-[11px]">Quản lý KPI</span>
                            <span class="px-sm py-1 bg-surface-container-high text-outline rounded-full text-[11px]">Duyệt nghỉ phép</span>
                            <span class="px-sm py-1 bg-surface-container-high text-outline rounded-full text-[11px]">Cấu hình hệ thống</span>
                            <span class="px-sm py-1 bg-surface-container-high text-outline rounded-full text-[11px]">Chấm công toàn công ty</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                        <div class="flex items-center gap-sm mb-md">
                            <span class="material-symbols-outlined text-outline">visibility</span>
                            <h4 class="font-headline-md text-headline-md text-on-surface">Người dùng</h4>
                        </div>
                        <p class="text-body-md text-on-surface-variant mb-lg">Chỉ xem thông tin nhân viên và KPI. Không được thêm, sửa, xoá. Chấm công và gửi đơn nghỉ của chính mình.</p>
                        <div class="flex flex-wrap gap-xs">
                            <span class="px-sm py-1 bg-green-100 text-green-700 rounded-full text-[11px]">Xem nhân viên</span>
                            <span class="px-sm py-1 bg-green-100 text-green-700 rounded-full text-[11px]">Xem KPI</span>
                            <span class="px-sm py-1 bg-green-100 text-green-700 rounded-full text-[11px]">Chấm công cá nhân</span>
                            <span class="px-sm py-1 bg-error-container text-on-error-container rounded-full text-[11px]">Không sửa/xoá</span>
                            <span class="px-sm py-1 bg-error-container text-on-error-container rounded-full text-[11px]">Không cấu hình</span>
                        </div>
                    </div>
                </div>

                <!-- User list with role assignment -->
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
                    <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Tài khoản &amp; Vai trò</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-surface-container-low border-b border-outline-variant">
                                <tr>
                                    <th class="px-lg py-md font-label-md text-on-surface">Người dùng</th>
                                    <th class="px-lg py-md font-label-md text-on-surface">Email</th>
                                    <th class="px-lg py-md font-label-md text-on-surface">Vai trò hiện tại</th>
                                    <th class="px-lg py-md font-label-md text-on-surface text-right">Đổi vai trò</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                @foreach ($users as $u)
                                    <tr class="hover:bg-surface-container transition-colors">
                                        <td class="px-lg py-md">
                                            <div class="flex items-center gap-md">
                                                <x-avatar :name="$u->name" class="w-8 h-8 text-[11px]" />
                                                <span class="font-body-md text-on-surface font-medium">{{ $u->name }}</span>
                                                @if ($u->id === auth()->id())
                                                    <span class="px-xs py-0.5 bg-secondary-container text-on-secondary-container rounded-full text-[10px]">Bạn</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-lg py-md font-body-md text-on-surface-variant">{{ $u->email }}</td>
                                        <td class="px-lg py-md">
                                            @if ($u->isSuperAdmin())
                                                <span class="px-sm py-1 bg-primary/10 text-primary rounded-full text-[11px] font-bold">{{ $u->role_label }}</span>
                                            @else
                                                <span class="px-sm py-1 bg-surface-container-high text-on-surface-variant rounded-full text-[11px] font-bold">{{ $u->role_label }}</span>
                                            @endif
                                        </td>
                                        <td class="px-lg py-md text-right">
                                            <form method="POST" action="{{ route('settings.users.role', $u) }}" class="inline-flex items-center gap-xs justify-end">
                                                @csrf @method('PUT')
                                                <select name="role" onchange="this.form.submit()" class="px-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                                                    @foreach (\App\Models\User::ROLE_LABELS as $key => $label)
                                                        <option value="{{ $key }}" @selected($u->role === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <noscript><button class="px-md py-1.5 bg-primary text-on-primary rounded-lg text-label-md">Lưu</button></noscript>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- HOLIDAYS -->
            <div class="hidden" id="section-holidays">
                <div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                    <div class="px-lg py-md border-b border-outline-variant flex items-center gap-sm bg-surface-container-low">
                        <span class="material-symbols-outlined text-primary">event</span>
                        <div>
                            <h2 class="font-headline-md text-headline-md text-on-surface">Ngày nghỉ lễ</h2>
                            <p class="text-[12px] text-outline">Các ngày này được loại trừ khỏi ngày công chuẩn và không bị chốt vắng mặt.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.holidays.store') }}" class="p-lg grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-md items-end border-b border-outline-variant bg-surface-container-lowest">
                        @csrf
                        <div class="space-y-xs">
                            <label class="block font-label-md text-label-md text-on-surface-variant">Ngày</label>
                            <input name="date" type="date" required class="w-full h-11 px-md border border-outline-variant rounded-lg font-body-md form-input-ring">
                        </div>
                        <div class="space-y-xs">
                            <label class="block font-label-md text-label-md text-on-surface-variant">Tên ngày lễ</label>
                            <input name="name" type="text" required placeholder="VD: Tết Dương lịch" class="w-full h-11 px-md border border-outline-variant rounded-lg font-body-md form-input-ring">
                        </div>
                        <button type="submit" class="h-11 px-lg bg-primary text-on-primary rounded-lg font-medium hover:bg-on-primary-fixed-variant active:scale-95 transition-all flex items-center gap-sm">
                            <span class="material-symbols-outlined text-[20px]">add</span> Thêm
                        </button>
                    </form>

                    <div class="divide-y divide-outline-variant">
                        @forelse ($holidays as $holiday)
                            <div class="px-lg py-md flex items-center justify-between">
                                <div class="flex items-center gap-md">
                                    <span class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center material-symbols-outlined text-[20px]">celebration</span>
                                    <div>
                                        <p class="font-body-md text-body-md text-on-surface font-semibold">{{ $holiday->name }}</p>
                                        <p class="text-[12px] text-outline">{{ $holiday->date->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('settings.holidays.destroy', $holiday) }}" onsubmit="return confirm('Xoá ngày lễ này?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-9 h-9 rounded-lg text-error hover:bg-error-container transition-colors flex items-center justify-center material-symbols-outlined text-[20px]">delete</button>
                                </form>
                            </div>
                        @empty
                            <p class="px-lg py-xl text-center text-on-surface-variant text-body-md">Chưa có ngày nghỉ lễ nào.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchTab(tabId) {
        ['company', 'attendance', 'departments', 'permissions', 'holidays'].forEach(t => {
            document.getElementById('section-' + t).classList.add('hidden');
            const btn = document.getElementById('tab-' + t);
            btn.classList.remove('active-tab');
            btn.classList.add('text-on-surface-variant');
            btn.querySelector('.material-symbols-outlined').classList.remove('text-primary');
        });
        document.getElementById('section-' + tabId).classList.remove('hidden');
        const activeBtn = document.getElementById('tab-' + tabId);
        activeBtn.classList.add('active-tab');
        activeBtn.classList.remove('text-on-surface-variant');
        activeBtn.querySelector('.material-symbols-outlined').classList.add('text-primary');
    }
    function toggleDeptEdit(id) {
        document.getElementById('dept-edit-' + id).classList.toggle('hidden');
    }
    @if ($errors->has('name') || $errors->has('code'))
        document.addEventListener('DOMContentLoaded', () => {
            switchTab('departments');
            document.getElementById('add-dept-form').classList.remove('hidden');
        });
    @endif
</script>
@endpush
