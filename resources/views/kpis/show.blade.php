@extends('layouts.app')

@section('title', 'Chi tiết KPI')
@section('page-title', 'Chi tiết KPI')

@push('head')
<style>
    .kpi-rich-text ul { list-style: disc; padding-left: 1.25rem; margin: 0.25rem 0; }
    .kpi-rich-text ol { list-style: decimal; padding-left: 1.25rem; margin: 0.25rem 0; }
    .kpi-rich-text a { color: #4f46e5; text-decoration: underline; }
    .kpi-rich-text h2, .kpi-rich-text h3, .kpi-rich-text h4 { font-weight: 600; margin: 0.5rem 0 0.25rem; }
    .kpi-rich-text blockquote { border-left: 3px solid #cbd5e1; padding-left: 0.75rem; color: #64748b; margin: 0.5rem 0; }
    .kpi-rich-text table { border-collapse: collapse; }
    .kpi-rich-text table td, .kpi-rich-text table th { border: 1px solid #cbd5e1; padding: 0.25rem 0.5rem; }
    .kpi-rich-text p { margin: 0.25rem 0; }
</style>
@endpush

@php
    $completedPhases = $kpi->phases->where('status', 'done')->count();
    $totalPhases = $kpi->phases->count();
    $circumference = 2 * pi() * 58;
    $offset = $circumference * (1 - $kpi->progress / 100);
    $phaseStyles = [
        'done' => ['icon' => 'check_circle', 'fill' => "'FILL' 1;", 'wrap' => 'bg-primary/10 text-primary', 'badge' => 'bg-green-100 text-green-700', 'card' => 'border-outline-variant'],
        'in_progress' => ['icon' => 'pending', 'fill' => '', 'wrap' => 'bg-primary/10 text-primary', 'badge' => 'bg-blue-100 text-blue-700', 'card' => 'border-primary/30 border-l-4 border-l-primary'],
        'received' => ['icon' => 'assignment_turned_in', 'fill' => '', 'wrap' => 'bg-amber-100 text-amber-700', 'badge' => 'bg-amber-100 text-amber-700', 'card' => 'border-outline-variant'],
        'pending' => ['icon' => 'schedule', 'fill' => '', 'wrap' => 'bg-outline-variant/20 text-on-surface-variant', 'badge' => 'bg-surface-container-highest text-on-surface-variant', 'card' => 'border-outline-variant opacity-80'],
    ];
    $myEmployeeId = auth()->user()->employee?->id;
    $isAdmin = auth()->user()->isSuperAdmin();
    $priorityStyles = [
        'high' => 'bg-red-100 text-red-700',
        'medium' => 'bg-amber-100 text-amber-700',
        'low' => 'bg-slate-100 text-slate-600',
    ];
    $statusColumns = ['pending', 'received', 'in_progress', 'done'];
    $phasesByStatus = $kpi->phases->groupBy('status');
    $canActOn = fn ($phase) => $isAdmin || ($myEmployeeId && $phase->assignee_employee_id === $myEmployeeId);
@endphp

@section('content')
<div class="px-md md:px-xl pt-lg max-w-container-max mx-auto">
    <!-- Breadcrumb & Actions -->
    <div class="hidden md:flex items-center justify-between mb-lg">
        <div class="flex items-center gap-xs text-on-surface-variant">
            <a href="{{ route('kpis.index') }}" class="font-label-md text-label-md hover:text-primary">KPI</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="font-label-md text-label-md">{{ $kpi->department?->name ?? 'Chung' }}</span>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="font-label-md text-label-md text-primary font-semibold">{{ $kpi->name }}</span>
        </div>
        @can('admin')
        <div class="flex gap-md">
            <a href="{{ route('kpis.edit', $kpi) }}" class="flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-full font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all">
                <span class="material-symbols-outlined text-sm">edit</span> Chỉnh sửa KPI
            </a>
        </div>
        @endcan
    </div>

    <!-- KPI Header Card -->
    <section class="mb-lg">
        <div class="bg-surface-container-lowest rounded-xl p-lg border border-outline-variant shadow-sm overflow-hidden relative">
            <div class="absolute top-0 right-0 p-lg opacity-10 pointer-events-none">
                <span class="material-symbols-outlined text-8xl">task_alt</span>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
                <div class="lg:col-span-2">
                    <span class="inline-block px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-semibold mb-sm uppercase">Ưu tiên {{ $kpi->priority_label }}</span>
                    <h1 class="font-display-lg text-display-lg text-on-surface mb-xs">{{ $kpi->name }}</h1>
                    @if ($kpi->description)
                        <div class="kpi-rich-text text-on-surface-variant body-lg mb-lg max-w-2xl">{!! clean($kpi->description) !!}</div>
                    @else
                        <p class="text-on-surface-variant body-lg mb-lg max-w-2xl">Chưa có mô tả cho mục tiêu này.</p>
                    @endif
                    <div class="flex flex-wrap gap-xl">
                        <div class="flex flex-col">
                            <span class="text-xs text-on-surface-variant font-medium">MỤC TIÊU</span>
                            <span class="font-headline-md text-headline-md">{{ $kpi->target_value ? rtrim(rtrim(number_format($kpi->target_value, 1), '0'), '.') : '—' }} {{ $kpi->unit }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-on-surface-variant font-medium">HẠN ĐỊNH</span>
                            <span class="font-headline-md text-headline-md text-error">{{ $kpi->deadline?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-on-surface-variant font-medium">CHỦ TRÌ</span>
                            <div class="flex items-center gap-xs">
                                <x-avatar :name="$kpi->owner?->name ?? 'NA'" class="w-6 h-6 text-[10px]" />
                                <span class="font-headline-md text-headline-md">{{ $kpi->owner?->name ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center justify-center bg-surface-container-low rounded-xl p-lg border border-outline-variant/50">
                    <div class="relative w-32 h-32 mb-md">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle class="text-surface-container-highest" cx="64" cy="64" fill="transparent" r="58" stroke="currentColor" stroke-width="12"></circle>
                            <circle class="text-primary progress-circle" cx="64" cy="64" fill="transparent" r="58" stroke="currentColor" stroke-dasharray="{{ round($circumference, 2) }}" stroke-dashoffset="{{ round($offset, 2) }}" stroke-linecap="round" stroke-width="12"></circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center flex-col">
                            <span class="text-2xl font-bold">{{ $kpi->progress }}%</span>
                            <span class="text-[10px] text-on-surface-variant uppercase tracking-wider">Tiến độ</span>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-center">{{ $completedPhases }}/{{ $totalPhases }} giai đoạn hoàn thành</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Kanban board -->
    <section class="mb-lg">
        <div class="flex items-center justify-between mb-md px-xs">
            <div>
                <h2 class="font-headline-md text-headline-md">Bảng công việc</h2>
                <p class="text-xs text-on-surface-variant">Kéo–thả thẻ giữa các cột để đổi trạng thái. Bấm vào thẻ để xem chi tiết, checklist và bình luận.</p>
            </div>
            @can('admin')
                <button type="button" onclick="document.getElementById('add-phase-panel').classList.toggle('hidden')"
                        class="flex items-center gap-1 px-lg py-sm bg-primary text-on-primary rounded-full font-label-md text-label-md shadow-sm hover:opacity-90 transition-all shrink-0">
                    <span class="material-symbols-outlined text-sm">add</span> Thêm giai đoạn
                </button>
            @endcan
        </div>

        @if ($errors->any())
            <div class="bg-error-container text-on-error-container px-md py-sm rounded-lg text-body-md mb-md">
                {{ $errors->first() }}
            </div>
        @endif

        @can('admin')
            <div id="add-phase-panel" class="{{ $errors->any() ? '' : 'hidden' }} mb-md bg-surface-container-lowest border border-dashed border-outline-variant rounded-xl p-md">
                <form method="POST" action="{{ route('kpis.phases.store', $kpi) }}" class="grid grid-cols-1 md:grid-cols-12 gap-md items-end">
                    @csrf
                    <div class="md:col-span-6 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Tên giai đoạn *</label>
                        <input name="name" value="{{ old('name') }}" required type="text" placeholder="Ví dụ: Thu thập yêu cầu"
                               class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                    </div>
                    <div class="md:col-span-6 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Người thực hiện</label>
                        <select name="assignee_employee_id" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            <option value="">Chọn nhân viên</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" @selected(old('assignee_employee_id') == $emp->id)>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Độ ưu tiên</label>
                        <select name="priority" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            @foreach (\App\Models\KpiPhase::PRIORITY_LABELS as $pk => $pl)
                                <option value="{{ $pk }}" @selected(old('priority', 'medium') === $pk)>{{ $pl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Ngày bắt đầu</label>
                        <input name="start_date" value="{{ old('start_date') }}" type="date"
                               class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                    </div>
                    <div class="md:col-span-4 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Hạn chót</label>
                        <input name="deadline" value="{{ old('deadline') }}" type="date"
                               class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                    </div>
                    <div class="md:col-span-12 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Mô tả công việc</label>
                        <textarea name="description" rows="2" placeholder="Nội dung cần làm, tiêu chí hoàn thành..."
                                  class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">{{ old('description') }}</textarea>
                    </div>
                    <div class="md:col-span-12 flex justify-end">
                        <button type="submit" class="flex items-center gap-1 px-lg py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md shadow-sm hover:shadow-md active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-sm">add</span> Thêm giai đoạn
                        </button>
                    </div>
                </form>
            </div>
        @endcan

        <div id="kanban" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-md">
            @foreach ($statusColumns as $col)
                @php $s = $phaseStyles[$col] ?? $phaseStyles['pending']; @endphp
                <div class="flex flex-col bg-surface-container rounded-xl border border-outline-variant/60 min-h-[120px]">
                    <div class="flex items-center justify-between px-md py-sm border-b border-outline-variant/60">
                        <span class="font-label-md text-label-md font-semibold uppercase tracking-wide text-on-surface-variant">{{ \App\Models\KpiPhase::STATUS_LABELS[$col] }}</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $s['badge'] }}">{{ ($phasesByStatus[$col] ?? collect())->count() }}</span>
                    </div>
                    <div class="kanban-list flex flex-col gap-sm p-sm flex-1" data-status="{{ $col }}">
                        @foreach ($phasesByStatus[$col] ?? [] as $phase)
                            @php $canAct = $canActOn($phase); @endphp
                            <div class="kanban-card bg-surface-container-lowest border {{ $phase->is_overdue ? 'border-error/40 border-l-4 border-l-error' : 'border-outline-variant' }} rounded-lg p-md cursor-pointer hover:shadow-md transition-shadow {{ $canAct ? '' : 'nodrag' }}"
                                 data-phase-id="{{ $phase->id }}" onclick="openPhaseDrawer(event, {{ $phase->id }})">
                                <div class="flex items-start justify-between gap-xs">
                                    <p class="font-body-md text-body-md font-semibold text-on-surface">{{ $phase->name }}</p>
                                    @if ($phase->priority)
                                        <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $priorityStyles[$phase->priority] ?? 'bg-slate-100 text-slate-600' }}">{{ $phase->priority_label }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-md mt-sm text-xs text-on-surface-variant">
                                    <span class="flex items-center gap-1 {{ $phase->is_overdue ? 'text-error font-semibold' : '' }}">
                                        <span class="material-symbols-outlined text-sm">calendar_today</span>{{ $phase->deadline?->format('d/m') ?? '—' }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">person</span>{{ $phase->assignee?->name ?? '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-md mt-sm text-xs text-on-surface-variant">
                                    @if ($phase->checklist_total > 0)
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">checklist</span>{{ $phase->checklist_done_count }}/{{ $phase->checklist_total }}</span>
                                    @endif
                                    @if ($phase->comments->isNotEmpty())
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">chat_bubble</span>{{ $phase->comments->count() }}</span>
                                    @endif
                                    @if ($phase->is_overdue)
                                        <span class="text-error font-semibold">Trễ hạn</span>
                                    @elseif ($phase->completed_late)
                                        <span class="text-amber-600 font-semibold">Xong trễ</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Info row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg mb-xl">
        <div class="bg-surface-container-low border border-outline-variant rounded-xl p-lg">
            <h2 class="font-headline-md text-headline-md mb-md">Thành viên dự án</h2>
            <div class="space-y-md">
                @php
                    $members = collect([$kpi->owner])->merge($kpi->phases->map->assignee)->filter()->unique('id');
                @endphp
                @forelse ($members as $member)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-md">
                            <x-avatar :name="$member->name" class="w-10 h-10" />
                            <div>
                                <p class="font-body-md text-body-md font-semibold">{{ $member->name }}</p>
                                <p class="text-xs text-on-surface-variant">{{ $member->position ?? 'Thành viên' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-md text-on-surface-variant">Chưa có thành viên.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-surface-container-low border border-outline-variant rounded-xl p-lg">
            <h2 class="font-headline-md text-headline-md mb-md">Thông tin thêm</h2>
            <div class="space-y-sm text-body-md">
                <div class="flex justify-between"><span class="text-on-surface-variant">Trạng thái</span><span class="font-semibold">{{ $kpi->status_label }}</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Loại đo lường</span><span class="font-semibold">{{ \App\Models\Kpi::MEASURE_LABELS[$kpi->measure_type] ?? $kpi->measure_type }}</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Phòng ban</span><span class="font-semibold">{{ $kpi->department?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Ngày tạo</span><span class="font-semibold">{{ $kpi->created_at?->format('d/m/Y') }}</span></div>
            </div>
        </div>

        <div class="bg-surface-container-low border border-outline-variant rounded-xl p-lg">
            <h2 class="font-headline-md text-headline-md mb-md flex items-center gap-xs">
                <span class="material-symbols-outlined text-primary text-lg">attach_file</span> Tài liệu đính kèm
            </h2>
            @if ($kpi->attachments->isNotEmpty())
                <div class="divide-y divide-outline-variant/40">
                    @foreach ($kpi->attachments as $file)
                        <a href="{{ $file->url }}" target="_blank" rel="noopener" class="flex items-center gap-md min-w-0 group py-sm">
                            <span class="material-symbols-outlined text-on-surface-variant shrink-0">{{ $file->icon }}</span>
                            <div class="min-w-0">
                                <p class="font-body-md text-body-md text-on-surface truncate group-hover:text-primary group-hover:underline">{{ $file->original_name }}</p>
                                <p class="text-xs text-on-surface-variant">{{ $file->human_size }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-body-md text-on-surface-variant">Chưa có tài liệu.</p>
            @endif
        </div>
    </div>
</div>

<!-- Drawers chi tiết giai đoạn -->
@foreach ($kpi->phases as $phase)
    @php
        $canAct = $canActOn($phase);
        $s = $phaseStyles[$phase->status] ?? $phaseStyles['pending'];
    @endphp
    <div id="phase-drawer-{{ $phase->id }}" class="phase-drawer fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closePhaseDrawer({{ $phase->id }})"></div>
        <div class="absolute right-0 top-0 h-full w-full sm:max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-start justify-between gap-md p-lg border-b border-outline-variant">
                <div class="min-w-0">
                    <div class="flex items-center gap-xs flex-wrap">
                        <h3 class="font-headline-md text-headline-md text-on-surface">{{ $phase->name }}</h3>
                        @if ($phase->priority)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $priorityStyles[$phase->priority] ?? 'bg-slate-100 text-slate-600' }}">{{ $phase->priority_label }}</span>
                        @endif
                    </div>
                    <span class="inline-block mt-xs px-3 py-1 {{ $s['badge'] }} rounded-full text-xs font-bold uppercase">{{ $phase->status_label }}</span>
                    @if ($phase->is_overdue)
                        <span class="inline-block mt-xs px-3 py-1 bg-error-container text-on-error-container rounded-full text-xs font-bold uppercase">Trễ hạn</span>
                    @endif
                </div>
                <div class="flex items-center gap-xs shrink-0">
                    @can('admin')
                        <form method="POST" action="{{ route('kpis.phases.destroy', [$kpi, $phase]) }}" onsubmit="return confirm('Xoá giai đoạn này? Checklist và bình luận kèm theo sẽ bị ẩn.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-on-surface-variant hover:text-error p-1" title="Xoá giai đoạn"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    @endcan
                    <button type="button" onclick="closePhaseDrawer({{ $phase->id }})" class="text-on-surface-variant hover:text-on-surface p-1" title="Đóng">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-lg flex flex-col gap-lg">
                <!-- Meta -->
                <div class="grid grid-cols-2 gap-md text-body-md">
                    <div class="flex flex-col">
                        <span class="text-xs text-on-surface-variant uppercase">Người thực hiện</span>
                        <span class="font-medium">{{ $phase->assignee?->name ?? '—' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-on-surface-variant uppercase">Hạn chót</span>
                        <span class="font-medium {{ $phase->is_overdue ? 'text-error' : '' }}">{{ $phase->deadline?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    @if ($phase->start_date)
                        <div class="flex flex-col">
                            <span class="text-xs text-on-surface-variant uppercase">Bắt đầu</span>
                            <span class="font-medium">{{ $phase->start_date->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Status actions -->
                @if ($canAct)
                    <div class="flex flex-wrap gap-xs">
                        @if ($phase->status === 'pending')
                            <x-phase-action :kpi="$kpi" :phase="$phase" status="received" icon="assignment_turned_in" label="Nhận việc" />
                        @elseif ($phase->status === 'received')
                            <x-phase-action :kpi="$kpi" :phase="$phase" status="in_progress" icon="play_arrow" label="Bắt đầu" />
                        @elseif ($phase->status === 'in_progress')
                            <x-phase-action :kpi="$kpi" :phase="$phase" status="done" icon="check" label="Hoàn thành" primary />
                        @elseif ($phase->status === 'done')
                            <x-phase-action :kpi="$kpi" :phase="$phase" status="in_progress" icon="undo" label="Mở lại" muted />
                        @endif
                    </div>
                @endif

                <!-- Chỉnh sửa giai đoạn -->
                @if ($canAct)
                    <details class="group border border-outline-variant rounded-lg">
                        <summary class="flex items-center gap-xs p-md cursor-pointer text-primary font-label-md text-label-md select-none list-none">
                            <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-180">edit</span>
                            Chỉnh sửa giai đoạn
                        </summary>
                        <form method="POST" action="{{ route('kpis.phases.update', [$kpi, $phase]) }}" class="px-md pb-md grid grid-cols-2 gap-md">
                            @csrf @method('PATCH')
                            <div class="col-span-2 flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Tên giai đoạn *</label>
                                <input name="name" value="{{ $phase->name }}" required type="text"
                                       class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            </div>
                            <div class="col-span-2 flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Người thực hiện</label>
                                <select name="assignee_employee_id" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                                    <option value="">Chọn nhân viên</option>
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}" @selected($phase->assignee_employee_id == $emp->id)>{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Độ ưu tiên</label>
                                <select name="priority" class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                                    @foreach (\App\Models\KpiPhase::PRIORITY_LABELS as $pk => $pl)
                                        <option value="{{ $pk }}" @selected(($phase->priority ?? 'medium') === $pk)>{{ $pl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Ngày bắt đầu</label>
                                <input name="start_date" value="{{ $phase->start_date?->format('Y-m-d') }}" type="date"
                                       class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            </div>
                            <div class="col-span-2 flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Hạn chót</label>
                                <input name="deadline" value="{{ $phase->deadline?->format('Y-m-d') }}" type="date"
                                       class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            </div>
                            <div class="col-span-2 flex flex-col gap-xs">
                                <label class="font-label-md text-label-md text-on-surface-variant">Mô tả công việc</label>
                                <textarea name="description" rows="3" placeholder="Nội dung cần làm, tiêu chí hoàn thành..."
                                          class="px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">{{ $phase->description }}</textarea>
                            </div>
                            <div class="col-span-2 flex justify-end">
                                <button type="submit" class="flex items-center gap-1 px-lg py-2 bg-primary text-on-primary rounded-lg text-label-md">
                                    <span class="material-symbols-outlined text-sm">save</span> Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </details>
                @endif

                <!-- Description -->
                <div>
                    <h4 class="font-label-md text-label-md text-on-surface-variant uppercase mb-xs">Mô tả</h4>
                    @if ($phase->description)
                        <div class="kpi-rich-text text-body-md text-on-surface">{!! clean($phase->description) !!}</div>
                    @else
                        <p class="text-body-md text-on-surface-variant italic">Chưa có mô tả.</p>
                    @endif
                </div>

                <!-- Checklist -->
                <div>
                    @php $done = $phase->checklist_done_count; $total = $phase->checklist_total; @endphp
                    <div class="flex items-center justify-between mb-xs">
                        <h4 class="font-label-md text-label-md text-on-surface-variant uppercase">Checklist</h4>
                        <span class="text-xs text-on-surface-variant">{{ $done }}/{{ $total }}</span>
                    </div>
                    <div class="h-1.5 w-full bg-surface-container-highest rounded-full overflow-hidden mb-sm">
                        <div class="h-full bg-primary rounded-full" style="width: {{ $total > 0 ? round($done / $total * 100) : 0 }}%"></div>
                    </div>
                    <div class="flex flex-col gap-xs">
                        @foreach ($phase->checklistItems as $item)
                            <div class="flex items-center gap-xs group">
                                <form method="POST" action="{{ route('kpis.phases.checklist.toggle', [$kpi, $phase, $item]) }}" class="flex items-center">
                                    @csrf @method('PATCH')
                                    <input type="checkbox" onchange="this.form.submit()" @checked($item->is_done) @disabled(! $canAct)
                                           class="w-4 h-4 accent-primary cursor-pointer disabled:cursor-not-allowed">
                                </form>
                                <span class="flex-1 text-body-md {{ $item->is_done ? 'line-through text-on-surface-variant' : 'text-on-surface' }}">{{ $item->title }}</span>
                                @if ($canAct)
                                    <form method="POST" action="{{ route('kpis.phases.checklist.destroy', [$kpi, $phase, $item]) }}" onsubmit="return confirm('Xoá mục này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-outline hover:text-error opacity-0 group-hover:opacity-100 transition-opacity"><span class="material-symbols-outlined text-sm">close</span></button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($canAct)
                        <form method="POST" action="{{ route('kpis.phases.checklist.store', [$kpi, $phase]) }}" class="flex items-center gap-xs mt-sm">
                            @csrf
                            <input name="title" required type="text" placeholder="Thêm mục checklist..."
                                   class="flex-1 px-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            <button type="submit" class="px-md py-1.5 bg-primary text-on-primary rounded-lg text-label-md shrink-0"><span class="material-symbols-outlined text-sm">add</span></button>
                        </form>
                    @endif
                </div>

                <!-- Comments -->
                <div>
                    <h4 class="font-label-md text-label-md text-on-surface-variant uppercase mb-sm">Bình luận ({{ $phase->comments->count() }})</h4>
                    <div class="flex flex-col gap-md mb-sm">
                        @forelse ($phase->comments as $comment)
                            <div class="flex items-start gap-sm">
                                <x-avatar :name="$comment->user?->name ?? 'NA'" class="w-8 h-8 text-[10px] shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-xs text-on-surface-variant"><span class="font-semibold text-on-surface">{{ $comment->user?->name ?? 'Ẩn danh' }}</span> • {{ $comment->created_at->format('d/m/Y H:i') }}</p>
                                    <p class="text-body-md text-on-surface whitespace-pre-line">{{ $comment->body }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-body-md text-on-surface-variant italic">Chưa có bình luận.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('kpis.phases.comments.store', [$kpi, $phase]) }}" class="flex flex-col gap-xs">
                        @csrf
                        <textarea name="body" rows="2" required placeholder="Viết bình luận..."
                                  class="w-full px-md py-2 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white"></textarea>
                        <button type="submit" class="self-end px-lg py-1.5 bg-primary text-on-primary rounded-lg text-label-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">send</span> Gửi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const KPI_ID = {{ $kpi->id }};
    const CSRF = '{{ csrf_token() }}';
    let justDragged = false;

    function openPhaseDrawer(event, id) {
        if (justDragged) { justDragged = false; return; }
        if (event && event.target.closest('a, button, input, form, textarea, select')) return;
        const el = document.getElementById('phase-drawer-' + id);
        if (el) { el.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    }
    function closePhaseDrawer(id) {
        const el = document.getElementById('phase-drawer-' + id);
        if (el) { el.classList.add('hidden'); document.body.style.overflow = ''; }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') document.querySelectorAll('.phase-drawer:not(.hidden)').forEach(d => d.classList.add('hidden')) && (document.body.style.overflow = '');
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Mở lại drawer sau khi thao tác (checklist/bình luận/đổi trạng thái).
        @if (session('open_phase'))
            const reopen = document.getElementById('phase-drawer-{{ session('open_phase') }}');
            if (reopen) { reopen.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        @endif

        if (!window.Sortable) return;
        document.querySelectorAll('.kanban-list').forEach(function (list) {
            new Sortable(list, {
                group: 'kanban',
                animation: 150,
                filter: '.nodrag',
                ghostClass: 'opacity-40',
                onStart: function () { justDragged = true; },
                onEnd: function (evt) {
                    setTimeout(() => { justDragged = false; }, 50);
                    const newStatus = evt.to.getAttribute('data-status');
                    const oldStatus = evt.from.getAttribute('data-status');
                    if (newStatus === oldStatus) return;
                    const phaseId = evt.item.getAttribute('data-phase-id');
                    fetch(`/kpi/${KPI_ID}/giai-doan/${phaseId}/trang-thai`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify({ status: newStatus, ajax: 1 }),
                    }).then(r => {
                        if (!r.ok) throw new Error('failed');
                        return r.json();
                    }).then(() => { window.location.reload(); })
                      .catch(() => { alert('Không thể đổi trạng thái (bạn không phụ trách giai đoạn này?).'); window.location.reload(); });
                },
            });
        });
    });
</script>
@endpush
