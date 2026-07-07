@php
    $k = $kpi ?? null;
    $val = fn ($field, $default = '') => old($field, $k->{$field} ?? $default);
    $phases = old('phase_name') ? array_map(fn ($i) => [
        'id' => old('phase_id')[$i] ?? '',
        'name' => old('phase_name')[$i] ?? '',
        'assignee_employee_id' => old('phase_assignee')[$i] ?? null,
        'deadline' => old('phase_deadline')[$i] ?? null,
    ], array_keys(old('phase_name'))) : ($k ? $k->phases->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'assignee_employee_id' => $p->assignee_employee_id,
        'deadline' => $p->deadline?->format('Y-m-d'),
    ])->toArray() : [['id' => '', 'name' => '', 'assignee_employee_id' => null, 'deadline' => null]]);
@endphp
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-lg gap-md">
            <div>
                <a href="{{ route('kpis.index') }}" class="flex items-center text-primary font-label-md text-label-md mb-xs hover:underline gap-1 w-fit">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Quay lại danh sách
                </a>
                <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $k ? 'Chỉnh sửa mục tiêu KPI' : 'Thêm mục tiêu KPI mới' }}</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Thiết lập mục tiêu chiến lược và các giai đoạn thực hiện.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">
                <ul class="list-disc list-inside text-body-md">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
            @csrf
            @if (($method ?? 'POST') === 'PUT') @method('PUT') @endif

            <div class="lg:col-span-7 flex flex-col gap-lg">
                <section class="glass-card rounded-xl p-lg flex flex-col gap-md shadow-sm">
                    <div class="flex items-center gap-xs mb-base">
                        <span class="material-symbols-outlined text-primary">info</span>
                        <h3 class="font-headline-md text-headline-md">Thông tin cơ bản</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Tên mục tiêu *</label>
                            <input name="name" value="{{ $val('name') }}" required type="text" placeholder="Ví dụ: Tăng trưởng doanh thu Q2"
                                   class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all outline-none bg-white">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Loại đo lường</label>
                                <select name="measure_type" class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                                    @foreach (\App\Models\Kpi::MEASURE_LABELS as $key => $label)
                                        <option value="{{ $key }}" @selected($val('measure_type', 'percent') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Đơn vị</label>
                                <input name="unit" value="{{ $val('unit') }}" type="text" placeholder="%, Trang, VNĐ"
                                       class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Mục tiêu cần đạt</label>
                                <input name="target_value" value="{{ $val('target_value') }}" type="number" step="0.01" placeholder="100"
                                       class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Tiến độ hiện tại (%)</label>
                                <input name="progress" value="{{ $val('progress', 0) }}" type="number" min="0" max="100" placeholder="0"
                                       class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                            </div>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Mô tả chi tiết</label>
                            <textarea name="description" rows="3" placeholder="Mô tả chi tiết về mục tiêu này..."
                                      class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">{{ $val('description') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="glass-card rounded-xl p-lg flex flex-col gap-md shadow-sm">
                    <div class="flex items-center justify-between mb-base">
                        <div class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-primary">account_tree</span>
                            <h3 class="font-headline-md text-headline-md">Giai đoạn &amp; Công việc con</h3>
                        </div>
                        <button type="button" onclick="addPhaseRow()" class="text-primary font-label-md text-label-md flex items-center gap-1 hover:bg-primary/5 px-2 py-1 rounded transition-colors">
                            <span class="material-symbols-outlined text-sm">add</span> Thêm giai đoạn
                        </button>
                    </div>
                    <div class="space-y-4" id="phases-container">
                        @foreach ($phases as $phase)
                            <div class="phase-row p-md rounded-lg border border-outline-variant bg-surface-container-lowest flex flex-col gap-md relative">
                                <button type="button" onclick="this.closest('.phase-row').remove()" class="absolute top-2 right-2 text-outline hover:text-error transition-colors">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                                <input type="hidden" name="phase_id[]" value="{{ $phase['id'] ?? '' }}">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-md">
                                    <div class="md:col-span-5 flex flex-col gap-xs">
                                        <label class="font-label-md text-label-md text-on-surface-variant">Tên giai đoạn</label>
                                        <input name="phase_name[]" value="{{ $phase['name'] }}" type="text" placeholder="Ví dụ: Thiết kế UI/UX"
                                               class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md">
                                    </div>
                                    <div class="md:col-span-4 flex flex-col gap-xs">
                                        <label class="font-label-md text-label-md text-on-surface-variant">Người thực hiện</label>
                                        <select name="phase_assignee[]" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                                            <option value="">Chọn nhân viên</option>
                                            @foreach ($employees as $emp)
                                                <option value="{{ $emp->id }}" @selected($phase['assignee_employee_id'] == $emp->id)>{{ $emp->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3 flex flex-col gap-xs">
                                        <label class="font-label-md text-label-md text-on-surface-variant">Hạn chót</label>
                                        <input name="phase_deadline[]" value="{{ $phase['deadline'] }}" type="date"
                                               class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="lg:col-span-5 flex flex-col gap-lg">
                <section class="glass-card rounded-xl p-lg flex flex-col gap-md shadow-sm">
                    <div class="flex items-center gap-xs mb-base">
                        <span class="material-symbols-outlined text-primary">settings</span>
                        <h3 class="font-headline-md text-headline-md">Thiết lập chung</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Phòng ban</label>
                            <select name="department_id" class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                                <option value="">Chọn phòng ban</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($val('department_id') == $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Người phụ trách</label>
                            <select name="owner_employee_id" class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                                <option value="">Chọn người phụ trách</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected($val('owner_employee_id') == $emp->id)>{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Hạn định</label>
                            <input name="deadline" value="{{ old('deadline', $k?->deadline?->format('Y-m-d')) }}" type="date"
                                   class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Mức độ ưu tiên</label>
                            <div class="flex gap-2">
                                @foreach (\App\Models\Kpi::PRIORITY_LABELS as $key => $label)
                                    <label class="flex-1 cursor-pointer">
                                        <input class="hidden peer" name="priority" type="radio" value="{{ $key }}" @checked($val('priority', 'medium') === $key)>
                                        <div class="text-center py-2 rounded-lg border border-outline-variant peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-body-md">{{ $label }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Trạng thái</label>
                            <select name="status" class="w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">
                                @foreach (\App\Models\Kpi::STATUS_LABELS as $key => $label)
                                    <option value="{{ $key }}" @selected($val('status', 'in_progress') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="bg-primary text-on-primary rounded-xl p-lg flex flex-col gap-md shadow-lg relative overflow-hidden">
                    <div class="absolute top-[-20px] right-[-20px] opacity-10">
                        <span class="material-symbols-outlined text-[120px]" style="font-variation-settings: 'FILL' 1;">lightbulb</span>
                    </div>
                    <div class="relative z-10">
                        <h3 class="font-headline-md text-headline-md mb-2">Mẹo thiết lập KPI</h3>
                        <ul class="space-y-3 opacity-90">
                            <li class="flex gap-2 text-body-md"><span class="material-symbols-outlined text-sm pt-1">check_circle</span> Sử dụng mô hình SMART (Cụ thể, Đo lường được, Khả thi, Liên quan, Thời hạn).</li>
                            <li class="flex gap-2 text-body-md"><span class="material-symbols-outlined text-sm pt-1">check_circle</span> Chia nhỏ mục tiêu lớn thành 3-5 giai đoạn để dễ quản lý.</li>
                            <li class="flex gap-2 text-body-md"><span class="material-symbols-outlined text-sm pt-1">check_circle</span> Phân công rõ ràng trách nhiệm để tránh chồng chéo.</li>
                        </ul>
                    </div>
                </section>

                <div class="flex justify-end gap-sm">
                    <a href="{{ route('kpis.index') }}" class="px-lg py-2 border border-outline-variant rounded-lg font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-high active:scale-95 transition-all flex items-center">Hủy bỏ</a>
                    <button type="submit" class="px-lg py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md shadow-sm hover:shadow-md transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">save</span> Lưu mục tiêu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function addPhaseRow() {
        const container = document.getElementById('phases-container');
        const employeeOptions = `{!! collect($employees)->map(fn ($e) => '<option value="' . $e->id . '">' . e($e->name) . '</option>')->implode('') !!}`;
        const row = document.createElement('div');
        row.className = 'phase-row p-md rounded-lg border border-outline-variant bg-surface-container-lowest flex flex-col gap-md relative';
        row.innerHTML = `
            <button type="button" onclick="this.closest('.phase-row').remove()" class="absolute top-2 right-2 text-outline hover:text-error transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
            <input type="hidden" name="phase_id[]" value="">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-md">
                <div class="md:col-span-5 flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant">Tên giai đoạn</label>
                    <input name="phase_name[]" type="text" placeholder="Tên giai đoạn mới..." class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md">
                </div>
                <div class="md:col-span-4 flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant">Người thực hiện</label>
                    <select name="phase_assignee[]" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                        <option value="">Chọn nhân viên</option>${employeeOptions}
                    </select>
                </div>
                <div class="md:col-span-3 flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant">Hạn chót</label>
                    <input name="phase_deadline[]" type="date" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md">
                </div>
            </div>
        `;
        container.appendChild(row);
    }
</script>
@endpush
