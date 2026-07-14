@extends('layouts.app')

@section('title', 'KPI')
@section('page-title', 'KPI & Chỉ số')

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="KPI & Chỉ số" subtitle="Theo dõi hiệu suất và mục tiêu chiến lược">
            @can('admin')
            <a href="{{ route('kpis.create') }}" class="flex items-center gap-xs bg-primary text-on-primary px-lg py-sm rounded-lg font-label-md text-label-md shadow-sm hover:shadow-md active:scale-95 transition-all">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Thêm mục tiêu</span>
            </a>
            @endcan
        </x-page-header>

        <!-- Bento Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-sm">
                        <span class="text-label-md font-bold text-outline uppercase tracking-wider">KPI Toàn công ty</span>
                    </div>
                    <div class="flex items-baseline gap-xs">
                        <h3 class="text-display-lg font-display-lg text-primary">{{ $companyAvg }}%</h3>
                        <span class="text-body-md text-outline">trên 100%</span>
                    </div>
                </div>
                <div class="mt-lg h-16 w-full flex items-end gap-1">
                    @foreach ($trend as $t)
                        <div class="flex-1 {{ $loop->last ? 'bg-primary' : 'bg-primary/30' }} rounded-t-sm" style="height: {{ max($t['pct'], 8) }}%"></div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm">
                <span class="text-label-md font-bold text-outline uppercase tracking-wider">KPI Theo phòng ban</span>
                <div class="mt-md space-y-sm">
                    @forelse ($departmentAvg as $dept)
                        <div class="flex items-center justify-between">
                            <span class="text-body-md font-medium">{{ $dept['name'] }}</span>
                            <span class="text-body-md font-bold">{{ $dept['progress'] }}%</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-1.5">
                            <div class="{{ $loop->first ? 'bg-primary' : 'bg-tertiary' }} h-1.5 rounded-full" style="width: {{ $dept['progress'] }}%"></div>
                        </div>
                    @empty
                        <p class="text-body-md text-outline">Chưa có dữ liệu.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-primary p-lg rounded-xl border border-primary-container shadow-lg text-white">
                <span class="text-label-md font-bold opacity-80 uppercase tracking-wider">Mục tiêu dẫn đầu</span>
                @if ($topKpi)
                    <div class="mt-lg flex items-center gap-md">
                        <x-avatar :name="$topKpi->owner?->name ?? 'KPI'" class="w-14 h-14 border-2 border-white/30" />
                        <div>
                            <h4 class="font-headline-md text-headline-md leading-tight">{{ \Illuminate\Support\Str::limit($topKpi->name, 24) }}</h4>
                            <p class="text-body-md opacity-90">{{ $topKpi->owner?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="mt-lg flex items-center justify-between">
                        <span class="text-body-md">Tiến độ:</span>
                        <span class="text-headline-md font-bold">{{ $topKpi->progress }}%</span>
                    </div>
                @else
                    <p class="mt-lg opacity-90">Chưa có mục tiêu KPI.</p>
                @endif
            </div>
        </div>

        <!-- KPI list -->
        <div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="p-lg border-b border-outline-variant flex justify-between items-center bg-surface-container-lowest">
                <h3 class="font-headline-md text-headline-md text-on-surface-variant">Danh sách mục tiêu KPI</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-lg py-md text-label-md font-bold text-outline uppercase">Mục tiêu</th>
                            <th class="px-lg py-md text-label-md font-bold text-outline uppercase">Phòng ban</th>
                            <th class="px-lg py-md text-label-md font-bold text-outline uppercase">Người phụ trách</th>
                            <th class="px-lg py-md text-label-md font-bold text-outline uppercase">Tiến độ</th>
                            <th class="px-lg py-md text-label-md font-bold text-outline uppercase">Trạng thái</th>
                            <th class="px-lg py-md text-label-md font-bold text-outline uppercase text-right">{{ auth()->user()->isSuperAdmin() ? 'Hành động' : '' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($kpis as $kpi)
                            <tr class="hover:bg-surface-container-lowest transition-colors group">
                                <td class="px-lg py-lg">
                                    <a href="{{ route('kpis.show', $kpi) }}" class="flex flex-col">
                                        <span class="font-body-md font-semibold text-on-surface">{{ $kpi->name }}</span>
                                        <span class="text-xs text-outline">Target: {{ $kpi->target_value ? rtrim(rtrim(number_format($kpi->target_value, 1), '0'), '.') : '—' }} {{ $kpi->unit }}</span>
                                    </a>
                                </td>
                                <td class="px-lg py-lg text-body-md">{{ $kpi->department?->name ?? '—' }}</td>
                                <td class="px-lg py-lg">
                                    <div class="flex items-center gap-xs">
                                        <x-avatar :name="$kpi->owner?->name ?? 'NA'" class="w-6 h-6 text-[10px]" />
                                        <span class="text-body-md">{{ $kpi->owner?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-lg py-lg w-48">
                                    <div class="flex items-center gap-sm">
                                        <div class="flex-1 bg-surface-container rounded-full h-1.5">
                                            <div class="{{ $kpi->progress >= 70 ? 'bg-primary' : ($kpi->progress >= 40 ? 'bg-tertiary' : 'bg-error') }} h-1.5 rounded-full" style="width: {{ $kpi->progress }}%"></div>
                                        </div>
                                        <span class="text-label-md font-bold">{{ $kpi->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-lg py-lg"><x-status-badge :status="$kpi->status" :label="$kpi->status_label" class="!rounded-full px-3 py-1" /></td>
                                <td class="px-lg py-lg text-right">
                                    @can('admin')
                                    <div class="flex items-center justify-end gap-xs">
                                        <a href="{{ route('kpis.edit', $kpi) }}" class="material-symbols-outlined text-outline hover:text-primary transition-colors">edit</a>
                                        <form method="POST" action="{{ route('kpis.destroy', $kpi) }}" onsubmit="return confirm('Xóa mục tiêu KPI này?')">
                                            @csrf @method('DELETE')
                                            <button class="material-symbols-outlined text-outline hover:text-error transition-colors">delete</button>
                                        </form>
                                    </div>
                                    @else
                                        <a href="{{ route('kpis.show', $kpi) }}" class="material-symbols-outlined text-outline hover:text-primary transition-colors">visibility</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-lg py-xl text-center text-on-surface-variant">Chưa có mục tiêu KPI nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($kpis->hasPages())
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest">{{ $kpis->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
