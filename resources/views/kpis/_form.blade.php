@php
    $k = $kpi ?? null;
    $val = fn ($field, $default = '') => old($field, $k->{$field} ?? $default);
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

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
            @csrf
            @if (($method ?? 'POST') === 'PUT') @method('PUT') @endif

            <div class="lg:col-span-7 flex flex-col gap-lg">
                <section class="glass-card rounded-xl p-lg flex flex-col gap-md shadow-sm">
                    <div class="flex items-center gap-xs mb-base">
                        <span class="material-symbols-outlined text-primary">info</span>
                        <h3 class="font-headline-md text-headline-md">Thông tin KPI</h3>
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
                            <textarea id="kpi-description" name="description" rows="4" placeholder="Mô tả chi tiết về mục tiêu này..."
                                      class="ck-editor w-full px-md py-2.5 rounded-lg border border-outline-variant focus:border-primary outline-none bg-white">{{ $val('description') }}</textarea>
                        </div>

                        <div class="flex flex-col gap-xs pt-sm border-t border-outline-variant/60">
                            <label class="font-label-md text-label-md text-on-surface-variant flex items-center gap-xs">
                                <span class="material-symbols-outlined text-primary text-lg">attach_file</span> Tài liệu đính kèm
                            </label>
                            @if ($k && $k->attachments->isNotEmpty())
                                <div class="divide-y divide-outline-variant/40 mb-xs">
                                    @foreach ($k->attachments as $file)
                                        <div class="flex items-center justify-between gap-md py-sm">
                                            <button type="button" data-preview
                                                    data-url="{{ $file->url }}" data-name="{{ $file->original_name }}"
                                                    data-mime="{{ $file->mime_type }}" data-ext="{{ pathinfo($file->original_name, PATHINFO_EXTENSION) }}"
                                                    class="flex items-center gap-md min-w-0 group text-left flex-1">
                                                <span class="material-symbols-outlined text-on-surface-variant shrink-0">{{ $file->icon }}</span>
                                                <div class="min-w-0">
                                                    <p class="font-body-md text-body-md text-on-surface truncate group-hover:text-primary group-hover:underline">{{ $file->original_name }}</p>
                                                    <p class="text-xs text-on-surface-variant">{{ $file->human_size }} • {{ $file->created_at->format('d/m/Y H:i') }}{{ $file->uploader ? ' • ' . $file->uploader->name : '' }}</p>
                                                </div>
                                            </button>
                                            <button type="submit" form="del-att-{{ $file->id }}" onclick="return confirm('Xoá tài liệu này?')"
                                                    class="text-outline hover:text-error transition-colors p-1 shrink-0" title="Xoá">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <input type="file" name="attachments[]" multiple
                                   class="w-full text-body-md file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary file:cursor-pointer file:font-label-md">
                            <p class="text-xs text-on-surface-variant">Hỗ trợ: PDF, Word, Excel, PowerPoint, ảnh (JPG/PNG/GIF/WebP/SVG), zip… tối đa 10MB. Tệp sẽ được lưu khi bấm “Lưu mục tiêu”.</p>
                        </div>
                    </div>
                </section>

                <section class="glass-card rounded-xl p-lg flex items-start gap-md shadow-sm">
                    <span class="material-symbols-outlined text-primary">account_tree</span>
                    <div>
                        <h3 class="font-headline-md text-headline-md">Giai đoạn &amp; Công việc con</h3>
                        @if ($k)
                            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Các giai đoạn được quản lý trực tiếp trên <strong>trang chi tiết KPI</strong> (bảng Kanban): thêm, kéo–thả đổi trạng thái, checklist, bình luận và chỉnh sửa/xoá từng giai đoạn ngay trong drawer.</p>
                            <a href="{{ route('kpis.show', $k) }}" class="inline-flex items-center gap-1 mt-sm text-primary font-label-md text-label-md hover:underline">
                                <span class="material-symbols-outlined text-sm">open_in_new</span> Mở bảng công việc
                            </a>
                        @else
                            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Sau khi lưu mục tiêu, bạn sẽ được chuyển tới <strong>trang chi tiết KPI</strong> để thêm và quản lý các giai đoạn (bảng Kanban, checklist, bình luận).</p>
                        @endif
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

        {{-- Form xoá tài liệu tách riêng (không lồng trong form KPI), kích hoạt qua thuộc tính form="" của nút xoá. --}}
        @if ($k)
            @foreach ($k->attachments as $file)
                <form id="del-att-{{ $file->id }}" method="POST" action="{{ route('kpis.attachments.destroy', [$k, $file]) }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>
</div>

<x-file-preview />

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    // Khởi tạo CKEditor cho ô mô tả KPI (đồng bộ vào textarea khi submit).
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.querySelector('#kpi-description');
        if (el && window.ClassicEditor) {
            ClassicEditor.create(el, {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', 'undo', 'redo']
            }).catch(function (e) { console.error(e); });
        }
    });
</script>
@endpush
