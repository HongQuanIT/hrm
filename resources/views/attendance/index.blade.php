@extends('layouts.app')

@section('title', 'Chấm công')
@section('page-title', 'Chấm công & Thời gian')

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Chấm công & Thời gian" subtitle="{{ \Carbon\Carbon::now()->translatedFormat('l, \n\g\à\y d \t\h\á\n\g m, Y') }}" />

        <div class="grid grid-cols-1 md:grid-cols-12 gap-lg">
            <!-- Quick Stats -->
            <div class="md:col-span-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
                <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
                    <div class="w-12 h-12 rounded-full bg-secondary-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-secondary-container">calendar_today</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant font-label-md text-label-md">Ngày công ({{ $selectedLabel }})</p>
                        <p class="font-headline-md text-headline-md text-on-surface">{{ $workedDays }} / {{ $standardDays }}</p>
                    </div>
                </div>
                <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
                    <div class="w-12 h-12 rounded-full bg-tertiary-fixed flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-tertiary-fixed-variant">schedule</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant font-label-md text-label-md">Đi muộn ({{ $selectedLabel }})</p>
                        <p class="font-headline-md text-headline-md text-on-surface">{{ $lateCount }} lần</p>
                    </div>
                </div>
                <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
                    <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-primary-fixed-variant">work_history</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant font-label-md text-label-md">Tăng ca</p>
                        <p class="font-headline-md text-headline-md text-on-surface">{{ $overtimeHours }}h</p>
                    </div>
                </div>
                <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
                    <div class="w-12 h-12 rounded-full bg-error-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-error-container">person_off</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant font-label-md text-label-md">Nghỉ phép còn lại</p>
                        <p class="font-headline-md text-headline-md text-on-surface">{{ $leaveBalance }} ngày</p>
                    </div>
                </div>
            </div>

            <!-- Check-in/out card -->
            <div class="md:col-span-5 flex flex-col gap-lg">
                <div class="bg-primary text-on-primary p-xl rounded-2xl shadow-lg relative overflow-hidden">
                    <div class="absolute top-[-20%] right-[-10%] w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-[-10%] left-[-5%] w-32 h-32 bg-primary-container/30 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <h3 class="font-label-md text-label-md text-on-primary/80 uppercase tracking-widest mb-xs">Giờ hiện tại</h3>
                        <div class="text-[52px] font-bold tracking-tighter mb-sm" id="real-time-clock">--:--:--</div>
                        <div class="flex flex-wrap items-center justify-center gap-xs mb-xl">
                            <span class="flex items-center gap-xs bg-white/10 px-md py-xs rounded-full">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                <span class="font-label-md text-label-md">Giờ làm: {{ $workStart }}–{{ $workEnd }}</span>
                            </span>
                            <span class="flex items-center gap-xs bg-white/10 px-md py-xs rounded-full">
                                <span class="material-symbols-outlined text-[16px]">lock_open</span>
                                <span class="font-label-md text-label-md">Mở check-in: {{ $checkinOpen }}</span>
                            </span>
                            <span class="flex items-center gap-xs bg-white/10 px-md py-xs rounded-full">
                                <span class="material-symbols-outlined text-[16px]">timer_off</span>
                                <span class="font-label-md text-label-md">Hạn check-in: {{ $checkinDeadline }}</span>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-md w-full">
                            <form method="POST" action="{{ route('attendance.checkin') }}">
                                @csrf
                                <button type="submit" @disabled($todayOnLeave || ($todayRecord && $todayRecord->check_in))
                                    class="w-full bg-white text-primary py-md rounded-xl font-headline-md text-headline-md shadow-sm hover:bg-primary-fixed transition-colors active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Check In
                                </button>
                            </form>
                            <form method="POST" action="{{ route('attendance.checkout') }}">
                                @csrf
                                <button type="submit" @disabled($todayOnLeave || ! $todayRecord || ! $todayRecord->check_in || $todayRecord->check_out)
                                    class="w-full bg-white/20 text-on-primary border border-white/30 py-md rounded-xl font-headline-md text-headline-md hover:bg-white/30 transition-colors active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Check Out
                                </button>
                            </form>
                        </div>
                        <div class="mt-lg flex flex-col items-center gap-xs text-on-primary/70">
                            @if ($todayOnLeave)
                                <p class="text-[12px] flex items-center gap-xs bg-white/10 px-md py-xs rounded-full">
                                    <span class="material-symbols-outlined text-[16px]">beach_access</span>
                                    Hôm nay bạn đang nghỉ phép đã duyệt — không cần chấm công.
                                </p>
                            @elseif ($todayRecord && $todayRecord->check_in)
                                <p class="text-[12px]">Hôm nay: vào {{ \Illuminate\Support\Str::of($todayRecord->check_in)->substr(0,5) }}
                                    @if($todayRecord->check_out) • ra {{ \Illuminate\Support\Str::of($todayRecord->check_out)->substr(0,5) }} @endif
                                    @if($todayRecord->late_minutes > 0) • <span class="font-semibold">muộn {{ $todayRecord->late_minutes }}′</span> @endif
                                </p>
                            @else
                                <p class="text-[12px] italic">Bạn chưa chấm công hôm nay.</p>
                            @endif
                            <p class="text-[11px] text-on-primary/60 flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[14px]">info</span>
                                Quên check-out sẽ không được tính công; sang ngày mới cần check-in lại.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Trend -->
                <div class="bg-white p-lg rounded-2xl border border-outline-variant shadow-sm flex-1">
                    <div class="flex justify-between items-center mb-lg">
                        <h3 class="font-headline-md text-headline-md text-on-surface">Xu hướng chuyên cần</h3>
                    </div>
                    <div class="h-48 w-full flex items-end gap-xs">
                        @foreach ($trend as $t)
                            <div class="flex-1 {{ $loop->last ? 'bg-primary' : 'bg-primary-fixed' }} rounded-t-sm relative group" style="height: {{ max($t['pct'], 4) }}%"></div>
                        @endforeach
                    </div>
                    <div class="flex justify-between mt-sm text-[11px] text-on-surface-variant font-medium px-xs">
                        @foreach ($trend as $t)
                            <span>{{ $t['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- History table -->
            <div class="md:col-span-7 bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">
                <div class="p-lg border-b border-outline-variant flex flex-col sm:flex-row sm:justify-between sm:items-center gap-sm">
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ $showAllHistory ? 'Lịch sử chấm công toàn công ty' : 'Lịch sử chấm công chi tiết' }}</h3>
                    <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-outline text-[20px]">calendar_month</span>
                        <input type="month" name="thang" value="{{ $selectedValue }}" max="{{ $currentMonthValue }}"
                               onchange="this.form.submit()"
                               class="px-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                    </form>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Ngày</th>
                                @if($showAllHistory)
                                    <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Nhân viên</th>
                                @endif
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Vào</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Ra</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng giờ</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30">
                            @forelse ($records as $record)
                                <tr class="hover:bg-surface-container-lowest transition-colors">
                                    <td class="px-lg py-md">
                                        <div class="flex flex-col">
                                            <span class="font-body-md text-body-md text-on-surface font-medium">{{ $record->work_date->isToday() ? 'Hôm nay' : $record->work_date->translatedFormat('l') }}</span>
                                            <span class="text-[12px] text-on-surface-variant">{{ $record->work_date->format('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    @if($showAllHistory)
                                        <td class="px-lg py-md font-body-md text-body-md">{{ $record->employee?->name }}</td>
                                    @endif
                                    <td class="px-lg py-md font-body-md text-body-md">{{ $record->check_in ? \Illuminate\Support\Str::of($record->check_in)->substr(0,5) : '--:--' }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md {{ $record->check_out ? '' : 'text-outline' }}">{{ $record->check_out ? \Illuminate\Support\Str::of($record->check_out)->substr(0,5) : '--:--' }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md">{{ $record->total_hours }}</td>
                                    <td class="px-lg py-md">
                                        @if ($record->status === 'late' && $record->late_level > 0)
                                            @php
                                                $lateClasses = [
                                                    1 => 'bg-yellow-100 text-yellow-700',
                                                    2 => 'bg-orange-100 text-orange-700',
                                                    3 => 'bg-red-100 text-red-700',
                                                ][$record->late_level];
                                            @endphp
                                            <span title="{{ $record->late_level_label }}" class="inline-flex items-center gap-1 px-sm py-xs rounded-full font-label-md text-[11px] font-bold {{ $lateClasses }}">
                                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                                {{ $record->late_level_label }} {{ $record->late_minutes }}′
                                            </span>
                                        @else
                                            <x-status-badge :status="$record->status" :label="$record->status_label" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-lg py-xl text-center text-on-surface-variant">Chưa có dữ liệu chấm công.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-md bg-surface-container border-t border-outline-variant flex justify-center">
                    {{ $records->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateClock() {
        const now = new Date();
        const el = document.getElementById('real-time-clock');
        if (el) {
            el.textContent = [now.getHours(), now.getMinutes(), now.getSeconds()]
                .map(n => String(n).padStart(2, '0')).join(':');
        }
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
@endpush
