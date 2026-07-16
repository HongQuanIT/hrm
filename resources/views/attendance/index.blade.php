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

            @if ($showAllHistory)
            <!-- Monthly summary per employee (admin only) -->
            <div class="md:col-span-12 md:order-3 bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant flex flex-col lg:flex-row lg:justify-between lg:items-center gap-md">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-on-surface">Bảng công tháng — toàn công ty</h3>
                        <p class="text-[12px] text-on-surface-variant mt-xs">Tổng kết chấm công của từng nhân viên trong {{ $selectedLabel }}. Bấm vào một dòng để xem chi tiết ngày-theo-ngày bên dưới.</p>
                    </div>
                    <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-sm">
                        <div class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-outline text-[20px]">calendar_month</span>
                            <input type="month" name="thang" value="{{ $selectedValue }}" max="{{ $currentMonthValue }}"
                                   onchange="this.form.submit()"
                                   class="px-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-2 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                            <input type="text" name="q" value="{{ $summarySearch }}" placeholder="Tìm tên / mã NV"
                                   class="pl-8 pr-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white w-full sm:w-48">
                        </div>
                        <select name="phong_ban" onchange="this.form.submit()"
                                class="px-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                            <option value="">Tất cả phòng ban</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" @selected($summaryDept === $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-lg py-1.5 rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-opacity">Lọc</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Nhân viên</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Phòng ban</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Công (ngày)</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Đi muộn</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Vắng</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Nghỉ phép</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Tăng ca</th>
                                <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Tổng giờ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30">
                            @forelse ($employeeSummaries as $sum)
                                @php $emp = $sum['employee']; @endphp
                                <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer {{ $filteredEmployee && $filteredEmployee->id === $emp->id ? 'bg-primary-fixed/40' : '' }}"
                                    onclick="window.location='{{ route('attendance.index', ['thang' => $selectedValue, 'nhan_vien' => $emp->id, 'q' => $summarySearch, 'phong_ban' => $summaryDept ?: null]) }}'">
                                    <td class="px-lg py-md">
                                        <div class="flex flex-col">
                                            <span class="font-body-md text-body-md text-on-surface font-medium">{{ $emp->name }}</span>
                                            <span class="text-[12px] text-on-surface-variant">{{ $emp->code }}{{ $emp->position ? ' • ' . $emp->position : '' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface-variant">{{ $emp->department?->name ?? '—' }}</td>
                                    <td class="px-lg py-md text-center font-body-md text-body-md text-on-surface font-medium">{{ $sum['worked_days'] }} / {{ $standardDays }}</td>
                                    <td class="px-lg py-md text-center">
                                        @if ($sum['late_days'] > 0)
                                            <span class="inline-flex items-center gap-1 px-sm py-xs rounded-full font-label-md text-[11px] font-bold bg-orange-100 text-orange-700" title="Tổng {{ $sum['late_minutes_total'] }} phút muộn">
                                                {{ $sum['late_days'] }} lần • {{ $sum['late_minutes_total'] }}′
                                            </span>
                                        @else
                                            <span class="text-outline">—</span>
                                        @endif
                                    </td>
                                    <td class="px-lg py-md text-center">
                                        @if ($sum['absent_days'] > 0)
                                            <span class="inline-flex items-center px-sm py-xs rounded-full font-label-md text-[11px] font-bold bg-red-100 text-red-700">{{ $sum['absent_days'] }}</span>
                                        @else
                                            <span class="text-outline">—</span>
                                        @endif
                                    </td>
                                    <td class="px-lg py-md text-center font-body-md text-body-md text-on-surface-variant">{{ $sum['leave_days'] ?: '—' }}</td>
                                    <td class="px-lg py-md text-center font-body-md text-body-md text-on-surface-variant">{{ $sum['overtime_hours'] > 0 ? $sum['overtime_hours'] . 'h' : '—' }}</td>
                                    <td class="px-lg py-md text-right font-body-md text-body-md text-on-surface">{{ intdiv($sum['total_minutes'], 60) }}h {{ str_pad($sum['total_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}m</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-lg py-xl text-center text-on-surface-variant">Không tìm thấy nhân viên phù hợp.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Check-in/out card -->
            <div class="md:col-span-5 md:order-1 flex flex-col gap-lg">
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
            </div>

            <!-- History table (cạnh thẻ check-in, chiều cao khớp thẻ check-in) -->
            <div class="md:col-span-7 md:order-2 relative min-h-0">
                <div class="md:absolute md:inset-0 bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">
                <div class="p-lg border-b border-outline-variant flex flex-col sm:flex-row sm:justify-between sm:items-center gap-sm">
                    <div class="flex items-center gap-sm flex-wrap">
                        <h3 class="font-headline-md text-headline-md text-on-surface">
                            @if (isset($filteredEmployee) && $filteredEmployee)
                                Lịch sử: {{ $filteredEmployee->name }}
                            @else
                                {{ $showAllHistory ? 'Lịch sử chấm công toàn công ty' : 'Lịch sử chấm công chi tiết' }}
                            @endif
                        </h3>
                        @if (isset($filteredEmployee) && $filteredEmployee)
                            <a href="{{ route('attendance.index', ['thang' => $selectedValue]) }}"
                               class="inline-flex items-center gap-1 px-sm py-xs rounded-full bg-surface-container-low text-on-surface-variant text-[12px] hover:bg-surface-container transition-colors">
                                <span class="material-symbols-outlined text-[14px]">close</span> Bỏ lọc
                            </a>
                        @endif
                    </div>
                    <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-xs">
                        @if (isset($filteredEmployee) && $filteredEmployee)
                            <input type="hidden" name="nhan_vien" value="{{ $filteredEmployee->id }}">
                        @endif
                        <span class="material-symbols-outlined text-outline text-[20px]">calendar_month</span>
                        <input type="month" name="thang" value="{{ $selectedValue }}" max="{{ $currentMonthValue }}"
                               onchange="this.form.submit()"
                               class="px-md py-1.5 rounded-lg border border-outline-variant focus:border-primary outline-none text-body-md bg-white">
                    </form>
                </div>
                <div class="flex-1 overflow-auto min-h-0">
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
