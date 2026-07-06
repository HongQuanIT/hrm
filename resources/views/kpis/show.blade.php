@extends('layouts.app')

@section('title', 'Chi tiết KPI')
@section('page-title', 'Chi tiết KPI')

@php
    $completedPhases = $kpi->phases->where('status', 'done')->count();
    $totalPhases = $kpi->phases->count();
    $circumference = 2 * pi() * 58;
    $offset = $circumference * (1 - $kpi->progress / 100);
    $phaseStyles = [
        'done' => ['icon' => 'check_circle', 'fill' => "'FILL' 1;", 'wrap' => 'bg-primary/10 text-primary', 'badge' => 'bg-green-100 text-green-700', 'card' => 'border-outline-variant'],
        'in_progress' => ['icon' => 'pending', 'fill' => '', 'wrap' => 'bg-primary/10 text-primary', 'badge' => 'bg-blue-100 text-blue-700', 'card' => 'border-primary/30 border-l-4 border-l-primary'],
        'pending' => ['icon' => 'schedule', 'fill' => '', 'wrap' => 'bg-outline-variant/20 text-on-surface-variant', 'badge' => 'bg-surface-container-highest text-on-surface-variant', 'card' => 'border-outline-variant opacity-80'],
    ];
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
                    <p class="text-on-surface-variant body-lg mb-lg max-w-2xl">{{ $kpi->description ?: 'Chưa có mô tả cho mục tiêu này.' }}</p>
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

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg mb-xl">
        <!-- Project Phases -->
        <div class="lg:col-span-2 space-y-md">
            <div class="flex items-center justify-between px-xs">
                <h2 class="font-headline-md text-headline-md">Các giai đoạn thực hiện</h2>
                @can('admin')
                    <a href="{{ route('kpis.edit', $kpi) }}" class="text-primary font-label-md text-label-md hover:underline">Thêm giai đoạn</a>
                @endcan
            </div>
            @forelse ($kpi->phases as $phase)
                @php $s = $phaseStyles[$phase->status] ?? $phaseStyles['pending']; @endphp
                <div class="bg-surface-container-lowest border {{ $s['card'] }} p-md rounded-xl hover:shadow-md transition-shadow">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md">
                        <div class="flex items-start gap-md">
                            <div class="p-xs {{ $s['wrap'] }} rounded-lg mt-1">
                                <span class="material-symbols-outlined {{ $phase->status === 'in_progress' ? 'animate-pulse' : '' }}" style="font-variation-settings: {{ $s['fill'] ?: "'FILL' 0;" }}">{{ $s['icon'] }}</span>
                            </div>
                            <div>
                                <h3 class="font-body-lg text-body-lg font-semibold {{ $phase->status === 'in_progress' ? 'text-primary' : '' }}">{{ $phase->name }}</h3>
                                <div class="flex items-center gap-md mt-sm">
                                    <div class="flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-sm">calendar_today</span>
                                        <span class="text-xs">{{ $phase->deadline?->format('d/m/Y') ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-sm">person</span>
                                        <span class="text-xs">{{ $phase->assignee?->name ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-lg sm:flex-col sm:items-end">
                            <span class="px-3 py-1 {{ $s['badge'] }} rounded-full text-xs font-bold uppercase">{{ $phase->status_label }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-surface-container-lowest border border-outline-variant p-lg rounded-xl text-center text-on-surface-variant">
                    Chưa có giai đoạn nào.@can('admin') <a href="{{ route('kpis.edit', $kpi) }}" class="text-primary hover:underline">Thêm giai đoạn</a>@endcan
                </div>
            @endforelse
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-lg">
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
                            <span class="material-symbols-outlined text-on-surface-variant text-sm">chat_bubble</span>
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
        </div>
    </div>
</div>
@endsection
