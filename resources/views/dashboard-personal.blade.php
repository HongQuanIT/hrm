@extends('layouts.app')

@section('title', 'Tổng quan')
@section('page-title', 'Tổng quan')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 1), '0'), '.');
    $checkedIn = $todayRecord && $todayRecord->check_in;
    $checkedOut = $todayRecord && $todayRecord->check_out;
@endphp

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Chào {{ auth()->user()->name }}" subtitle="Hôm nay là {{ \Carbon\Carbon::now()->translatedFormat('l, \n\g\à\y d \t\h\á\n\g m, Y') }}">
            <a href="{{ route('attendance.index') }}" class="flex items-center gap-xs bg-surface border border-outline-variant px-md py-sm rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">fingerprint</span>
                <span>Chấm công</span>
            </a>
            <a href="{{ route('leaves.index') }}" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Tạo đơn nghỉ</span>
            </a>
        </x-page-header>

        @unless ($employee)
            <div class="glass-card p-lg rounded-xl shadow-sm mb-xl flex items-center gap-md text-on-surface-variant">
                <span class="material-symbols-outlined text-tertiary">info</span>
                <span>Tài khoản của bạn chưa được gắn với hồ sơ nhân viên. Vui lòng liên hệ quản trị viên để xem thống kê cá nhân.</span>
            </div>
        @endunless

        <!-- Personal Stats Bento -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-md mb-xl">
            <!-- Chấm công hôm nay -->
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs {{ $checkedIn ? 'bg-green-100 text-green-700' : 'bg-surface-container text-on-surface-variant' }} rounded-lg">
                        <span class="material-symbols-outlined">fingerprint</span>
                    </div>
                    @if ($checkedIn && ! $checkedOut)<div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>@endif
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Chấm công hôm nay</p>
                    @if ($checkedIn)
                        <h3 class="font-headline-md text-headline-md text-on-surface">{{ \Illuminate\Support\Str::of($todayRecord->check_in)->substr(0,5) }}
                            @if ($checkedOut)<span class="text-on-surface-variant">→ {{ \Illuminate\Support\Str::of($todayRecord->check_out)->substr(0,5) }}</span>@endif
                        </h3>
                    @else
                        <h3 class="font-headline-md text-headline-md text-outline">Chưa chấm</h3>
                    @endif
                </div>
            </div>

            <!-- Ngày công tháng -->
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-primary-container/20 text-primary rounded-lg">
                        <span class="material-symbols-outlined">calendar_today</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Ngày công (tháng)</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface">{{ $workedDays }}<span class="text-headline-md text-on-surface-variant">/{{ $standardDays }}</span></h3>
                </div>
            </div>

            <!-- Đi muộn -->
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm border-l-4 border-l-tertiary">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-tertiary-fixed text-on-tertiary-fixed-variant rounded-lg">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Đi muộn (tháng)</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface">{{ $lateThisMonth }}<span class="text-headline-md text-on-surface-variant"> lần</span></h3>
                </div>
            </div>

            <!-- Số dư phép -->
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm {{ $leaveBalance < 0 ? 'border-l-4 border-l-error' : '' }}">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs {{ $leaveBalance < 0 ? 'bg-error-container text-on-error-container' : 'bg-secondary-container text-on-secondary-container' }} rounded-lg">
                        <span class="material-symbols-outlined">event_available</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Số dư phép (tháng)</p>
                    <h3 class="font-display-lg text-display-lg {{ $leaveBalance < 0 ? 'text-error' : 'text-on-surface' }}">{{ $fmt($leaveBalance) }}<span class="text-headline-md {{ $leaveBalance < 0 ? 'text-error/70' : 'text-on-surface-variant' }}"> ngày</span></h3>
                </div>
            </div>

            <!-- KPI của tôi -->
            <div class="bg-primary-container p-lg rounded-xl flex flex-col justify-between shadow-lg text-on-primary-container">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-white/20 rounded-lg">
                        <span class="material-symbols-outlined">trending_up</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md opacity-80 uppercase tracking-wider">KPI của tôi</p>
                    <h3 class="font-display-lg text-display-lg">{{ $myAvgKpi }}%</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl">
            <div class="lg:col-span-8 space-y-xl">
                <!-- 7-Day Personal Attendance -->
                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <div class="flex items-center justify-between mb-xl">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Giờ làm 7 ngày qua</h4>
                        <a href="{{ route('attendance.index') }}" class="text-primary font-label-md text-label-md hover:underline">Chi tiết</a>
                    </div>
                    <div class="h-64 flex items-end justify-between gap-base md:gap-md px-md relative">
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none py-md px-md opacity-10">
                            <div class="border-t border-on-surface w-full"></div>
                            <div class="border-t border-on-surface w-full"></div>
                            <div class="border-t border-on-surface w-full"></div>
                            <div class="border-t border-on-surface w-full"></div>
                        </div>
                        @forelse ($weekAttendance as $day)
                            <div class="flex-1 flex flex-col items-center gap-sm h-full justify-end relative z-10">
                                <span class="text-[10px] font-bold {{ $day['is_today'] ? 'text-primary' : 'text-on-surface-variant' }} mb-xs">{{ $day['hours'] > 0 ? $fmt($day['hours']).'h' : '' }}</span>
                                <div class="w-full {{ $day['is_weekend'] ? 'bg-primary/40' : 'bg-primary' }} rounded-t-lg chart-bar" style="height: {{ max($day['pct'], 4) }}%;"></div>
                                <span class="text-label-md {{ $day['is_today'] ? 'font-bold text-primary' : 'text-on-surface-variant' }}">{{ $day['label'] }}</span>
                            </div>
                        @empty
                            <p class="text-body-md text-on-surface-variant m-auto">Chưa có dữ liệu chấm công.</p>
                        @endforelse
                    </div>
                </div>

                <!-- My KPIs -->
                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <div class="flex items-center justify-between mb-lg">
                        <h4 class="font-headline-md text-headline-md text-on-surface">KPI của tôi</h4>
                        <a href="{{ route('kpis.index') }}" class="text-primary font-label-md text-label-md hover:underline">Xem tất cả</a>
                    </div>
                    <div class="space-y-lg">
                        @forelse ($myKpis->take(4) as $kpi)
                            <a href="{{ route('kpis.show', $kpi) }}" class="block group">
                                <div class="flex items-center justify-between mb-xs">
                                    <span class="font-body-md text-body-md font-semibold text-on-surface group-hover:text-primary transition-colors">{{ $kpi->name }}</span>
                                    <span class="text-label-md font-bold">{{ $kpi->progress }}%</span>
                                </div>
                                <div class="w-full bg-surface-container rounded-full h-2">
                                    <div class="{{ $kpi->progress >= 70 ? 'bg-primary' : ($kpi->progress >= 40 ? 'bg-tertiary' : 'bg-error') }} h-2 rounded-full" style="width: {{ $kpi->progress }}%"></div>
                                </div>
                            </a>
                        @empty
                            <p class="text-body-md text-on-surface-variant">Bạn chưa được giao KPI nào.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-xl">
                <!-- My profile -->
                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <div class="flex flex-col items-center text-center">
                        <x-avatar :name="$employee?->name ?? auth()->user()->name" class="w-20 h-20 !rounded-2xl text-[28px] shadow-md mb-md" />
                        <h4 class="font-headline-md text-headline-md text-on-surface">{{ $employee?->name ?? auth()->user()->name }}</h4>
                        <p class="text-body-md text-on-surface-variant">{{ $employee?->position ?? 'Nhân viên' }}</p>
                    </div>
                    @if ($employee)
                        <div class="mt-lg pt-lg border-t border-outline-variant/30 space-y-sm">
                            <div class="flex items-center justify-between text-body-md">
                                <span class="text-on-surface-variant">Mã NV</span>
                                <span class="font-semibold">{{ $employee->code }}</span>
                            </div>
                            <div class="flex items-center justify-between text-body-md">
                                <span class="text-on-surface-variant">Phòng ban</span>
                                <span class="font-semibold">{{ $employee->department?->name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-body-md">
                                <span class="text-on-surface-variant">Ngày vào</span>
                                <span class="font-semibold">{{ $employee->join_date?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('employees.show', $employee) }}" class="block w-full mt-lg border border-outline-variant py-sm rounded-lg font-label-md text-label-md hover:bg-surface-variant transition-colors text-center">Xem hồ sơ đầy đủ</a>
                    @endif
                </div>

                <!-- My recent leaves -->
                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <div class="flex items-center justify-between mb-lg">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Đơn nghỉ của tôi</h4>
                        @if ($pendingCount > 0)
                            <span class="px-2 py-0.5 bg-tertiary-container text-on-tertiary-fixed-variant text-[10px] font-bold rounded-full">{{ $pendingCount }} CHỜ DUYỆT</span>
                        @endif
                    </div>
                    <div class="space-y-md">
                        @forelse ($recentLeaves as $leave)
                            <div class="flex items-center gap-md">
                                <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center">
                                    <span class="material-symbols-outlined text-on-secondary-fixed-variant">event_busy</span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-body-md text-body-md font-bold">{{ $leave->type_label }}</p>
                                    <p class="text-[12px] text-on-surface-variant">{{ $leave->start_date->format('d/m') }} - {{ $leave->end_date->format('d/m/Y') }}</p>
                                </div>
                                <x-status-badge :status="$leave->status" :label="$leave->status_label" />
                            </div>
                        @empty
                            <p class="text-body-md text-on-surface-variant">Bạn chưa có đơn nghỉ nào.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('leaves.index') }}" class="block w-full mt-lg text-primary font-label-md text-label-md hover:underline text-center">Xem tất cả</a>
                </div>

                <!-- Quick actions -->
                <div class="grid grid-cols-2 gap-md">
                    <a href="{{ route('attendance.index') }}" class="glass-card p-md rounded-xl text-center hover:bg-primary hover:text-on-primary transition-all cursor-pointer group">
                        <span class="material-symbols-outlined mb-xs block text-primary group-hover:text-on-primary">fingerprint</span>
                        <span class="text-label-md font-bold">Chấm công</span>
                    </a>
                    <a href="{{ route('leaves.index') }}" class="glass-card p-md rounded-xl text-center hover:bg-primary hover:text-on-primary transition-all cursor-pointer group">
                        <span class="material-symbols-outlined mb-xs block text-primary group-hover:text-on-primary">add_circle</span>
                        <span class="text-label-md font-bold">Tạo đơn</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.chart-bar').forEach(bar => {
            const finalHeight = bar.style.height;
            bar.style.height = '0';
            setTimeout(() => { bar.style.height = finalHeight; }, 100);
        });
    });
</script>
@endpush
