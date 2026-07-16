@extends('layouts.app')

@section('title', 'Tổng quan')
@section('page-title', 'Tổng quan')

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Chào buổi sáng, {{ auth()->user()->name }}" subtitle="Hôm nay là {{ \Carbon\Carbon::now()->translatedFormat('l, \n\g\à\y d \t\h\á\n\g m, Y') }}">
            <a href="{{ route('leaves.calendar') }}" class="flex items-center gap-xs bg-surface border border-outline-variant px-md py-sm rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                <span>Lịch nghỉ phép</span>
            </a>
            <a href="{{ route('employees.create') }}" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Thêm nhân viên</span>
            </a>
        </x-page-header>

        <!-- Stats Bento Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-md mb-xl">
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-primary-container/20 text-primary rounded-lg">
                        <span class="material-symbols-outlined">badge</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng nhân viên</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface">{{ $totalEmployees }}</h3>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-green-100 text-green-700 rounded-lg">
                        <span class="material-symbols-outlined">how_to_reg</span>
                    </div>
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Đi làm</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface">{{ $workingToday }}</h3>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm border-l-4 border-l-tertiary">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-tertiary-fixed text-on-tertiary-fixed-variant rounded-lg">
                        <span class="material-symbols-outlined">event_busy</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Nghỉ phép</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface">{{ $onLeaveToday }}</h3>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-error-container text-on-error-container rounded-lg">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Đi muộn</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface">{{ $lateToday }}</h3>
                </div>
            </div>
            <div class="bg-primary-container p-lg rounded-xl flex flex-col justify-between shadow-lg text-on-primary-container">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-white/20 rounded-lg">
                        <span class="material-symbols-outlined">trending_up</span>
                    </div>
                </div>
                <div>
                    <p class="font-label-md text-label-md opacity-80 uppercase tracking-wider">KPI trung bình</p>
                    <h3 class="font-display-lg text-display-lg">{{ $avgKpi }}%</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl">
            <div class="lg:col-span-8 space-y-xl">
                <!-- 7-Day Attendance Chart -->
                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <div class="flex items-center justify-between mb-xl">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Chấm công 7 ngày</h4>
                    </div>
                    <div class="h-64 flex items-end justify-between gap-base md:gap-md px-md relative">
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none py-md px-md opacity-10">
                            <div class="border-t border-on-surface w-full"></div>
                            <div class="border-t border-on-surface w-full"></div>
                            <div class="border-t border-on-surface w-full"></div>
                            <div class="border-t border-on-surface w-full"></div>
                        </div>
                        @foreach ($weekAttendance as $day)
                            <div class="flex-1 flex flex-col items-center gap-sm h-full justify-end relative z-10">
                                <span class="text-[10px] font-bold {{ $day['is_today'] ? 'text-primary' : 'text-on-surface-variant' }} mb-xs">{{ $day['count'] }}</span>
                                <div class="w-full {{ $day['is_weekend'] ? 'bg-primary/40' : 'bg-primary' }} rounded-t-lg chart-bar" style="height: {{ max($day['pct'], 4) }}%;"></div>
                                <span class="text-label-md {{ $day['is_today'] ? 'font-bold text-primary' : 'text-on-surface-variant' }}">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-xl">
                    <!-- Leave Ratio -->
                    <div class="glass-card p-lg rounded-xl shadow-sm flex flex-col">
                        <h4 class="font-headline-md text-headline-md text-on-surface mb-lg">Tỷ lệ nghỉ phép</h4>
                        <div class="flex-1 flex items-center justify-center relative">
                            <svg class="w-40 h-40 transform -rotate-90">
                                <circle class="text-surface-container" cx="80" cy="80" fill="transparent" r="70" stroke="currentColor" stroke-width="12"></circle>
                                <circle class="text-tertiary" cx="80" cy="80" fill="transparent" r="70" stroke="currentColor" stroke-dasharray="440" stroke-dashoffset="{{ 440 - (440 * min($leavePct, 100) / 100) }}" stroke-width="12"></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-headline-lg font-bold">{{ $leavePct }}%</span>
                                <span class="text-label-md text-on-surface-variant">Tháng này</span>
                            </div>
                        </div>
                        <div class="mt-lg space-y-sm">
                            <div class="flex items-center justify-between text-body-md">
                                <div class="flex items-center gap-xs">
                                    <div class="w-3 h-3 rounded-full bg-tertiary"></div>
                                    <span>Đã nghỉ</span>
                                </div>
                                <span class="font-bold">{{ rtrim(rtrim(number_format($leaveUsed, 1), '0'), '.') }} ngày</span>
                            </div>
                            <div class="flex items-center justify-between text-body-md">
                                <div class="flex items-center gap-xs">
                                    <div class="w-3 h-3 rounded-full bg-surface-container"></div>
                                    <span>Hạn mức</span>
                                </div>
                                <span class="font-bold">{{ $leaveQuota }} ngày</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="glass-card p-lg rounded-xl shadow-sm">
                        <h4 class="font-headline-md text-headline-md text-on-surface mb-lg">Đơn nghỉ phép gần đây</h4>
                        <div class="space-y-md">
                            @forelse ($recentLeaves as $leave)
                                <div class="flex items-center gap-md">
                                    <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center">
                                        <span class="material-symbols-outlined text-on-secondary-fixed-variant">event_busy</span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-body-md text-body-md font-bold">{{ $leave->type_label }}</p>
                                        <p class="text-[12px] text-on-surface-variant">{{ $leave->employee?->name }} • {{ $leave->created_at->diffForHumans() }}</p>
                                    </div>
                                    <x-status-badge :status="$leave->status" :label="$leave->status_label" />
                                </div>
                            @empty
                                <p class="text-body-md text-on-surface-variant">Chưa có đơn nghỉ phép nào.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('leaves.index') }}" class="block w-full mt-lg text-primary font-label-md text-label-md hover:underline text-center">Xem tất cả</a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-xl">
                <!-- Số dư tài chính (M10) -->
                @php $balNeg = $finance['balance'] < 0; @endphp
                <a href="{{ route('finance.overview') }}" class="block {{ $balNeg ? 'bg-error-container text-on-error-container' : 'bg-primary-container text-on-primary-container' }} p-lg rounded-xl shadow-sm">
                    <div class="flex items-center justify-between mb-sm">
                        <div class="flex items-center gap-sm">
                            <span class="material-symbols-outlined">account_balance</span>
                            <span class="font-label-md text-label-md uppercase tracking-wider opacity-80">Số dư hiện có</span>
                        </div>
                        @if ($balNeg)
                            <span class="px-2 py-0.5 bg-error text-on-error text-[10px] font-bold rounded-full">ĐANG ÂM</span>
                        @endif
                    </div>
                    <h3 class="font-display-lg text-display-lg mb-sm">{{ number_format($finance['balance'], 0, ',', '.') }} ₫</h3>
                    <div class="flex items-center gap-lg text-[12px] opacity-90">
                        <span>Đã nạp: <b>{{ number_format($finance['contributed'], 0, ',', '.') }} ₫</b></span>
                        <span>Đã chi: <b>{{ number_format($finance['spent'], 0, ',', '.') }} ₫</b></span>
                    </div>
                    @if ($balNeg)
                        <p class="text-[12px] mt-sm">Chi vượt vốn — có thể do ứng tiền cá nhân.</p>
                    @endif
                </a>

                <!-- Notifications -->
                <div class="bg-surface-container-high p-lg rounded-xl shadow-sm border border-outline-variant/30">
                    <div class="flex items-center justify-between mb-lg">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Cần xử lý</h4>
                        @if ($pendingLeaves > 0)
                            <span class="px-2 py-0.5 bg-error text-on-error text-[10px] font-bold rounded-full">{{ $pendingLeaves }} MỚI</span>
                        @endif
                    </div>
                    <div class="space-y-md">
                        <div class="p-md bg-surface rounded-lg border-l-4 border-l-primary shadow-sm">
                            <p class="font-body-md text-body-md font-bold mb-xs">Đơn nghỉ phép chờ duyệt</p>
                            <p class="text-[12px] text-on-surface-variant">Có {{ $pendingLeaves }} đơn đang chờ bạn phê duyệt.</p>
                            <div class="mt-sm flex justify-end">
                                <a href="{{ route('leaves.index') }}" class="text-primary font-label-md text-label-md font-bold">Xử lý ngay</a>
                            </div>
                        </div>
                        <div class="p-md bg-surface rounded-lg border-l-4 border-l-tertiary shadow-sm">
                            <p class="font-body-md text-body-md font-bold mb-xs">Đi muộn hôm nay</p>
                            <p class="text-[12px] text-on-surface-variant">{{ $lateToday }} nhân viên đi muộn trong ngày.</p>
                        </div>
                    </div>
                </div>

                <!-- New Employees -->
                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <h4 class="font-headline-md text-headline-md text-on-surface mb-lg">Nhân viên mới</h4>
                    <div class="space-y-lg">
                        @forelse ($newEmployees as $emp)
                            <a href="{{ route('employees.show', $emp) }}" class="flex items-center gap-md">
                                <x-avatar :name="$emp->name" class="w-12 h-12" />
                                <div class="flex-1">
                                    <p class="font-body-md text-body-md font-bold">{{ $emp->name }}</p>
                                    <p class="text-[12px] text-on-surface-variant">{{ $emp->position }} • {{ $emp->department?->name }}</p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-[11px] text-on-surface-variant">Tham gia</span>
                                    <span class="text-label-md font-bold text-primary">{{ $emp->join_date?->format('d/m/Y') }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="text-body-md text-on-surface-variant">Chưa có nhân viên.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('employees.index') }}" class="block w-full mt-lg border border-outline-variant py-sm rounded-lg font-label-md text-label-md hover:bg-surface-variant transition-colors text-center">Xem tất cả nhân viên</a>
                </div>

                <div class="grid grid-cols-2 gap-md">
                    <a href="{{ route('leaves.index') }}" class="glass-card p-md rounded-xl text-center hover:bg-primary hover:text-on-primary transition-all cursor-pointer group">
                        <span class="material-symbols-outlined mb-xs block text-primary group-hover:text-on-primary">add_circle</span>
                        <span class="text-label-md font-bold">Tạo đơn</span>
                    </a>
                    <a href="{{ route('kpis.index') }}" class="glass-card p-md rounded-xl text-center hover:bg-primary hover:text-on-primary transition-all cursor-pointer group">
                        <span class="material-symbols-outlined mb-xs block text-primary group-hover:text-on-primary">analytics</span>
                        <span class="text-label-md font-bold">Xem KPI</span>
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
