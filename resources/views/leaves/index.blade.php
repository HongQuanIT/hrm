@extends('layouts.app')

@section('title', 'Nghỉ phép')
@section('page-title', 'Quản lý nghỉ phép')

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Quản lý nghỉ phép" subtitle="Kiểm tra số dư phép và theo dõi các yêu cầu">
            <a href="{{ route('leaves.calendar') }}" class="flex items-center gap-xs bg-surface border border-outline-variant px-md py-sm rounded-full font-body-md text-body-md hover:bg-surface-container transition-all">
                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                <span>Lịch nghỉ phép</span>
            </a>
            <button type="button" onclick="openLeaveModal()" class="flex items-center gap-xs bg-primary text-on-primary px-lg py-sm rounded-full font-body-md text-body-md shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">
                <span class="material-symbols-outlined">add</span>
                <span>Tạo đơn mới</span>
            </button>
        </x-page-header>

        @php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 1), '0'), '.'); @endphp
        <!-- Bento Stats -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-md mb-xl">
        @if ($isAdmin)
            <div class="glass-card p-lg rounded-xl flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Đơn chờ duyệt</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-tertiary">{{ $pendingCount }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">đơn</span>
                </div>
                <div class="mt-auto pt-md flex items-center text-on-primary-fixed-variant text-[12px]">
                    <span class="material-symbols-outlined text-[16px] mr-1">pending_actions</span>
                    <span>Cần xử lý</span>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Đã duyệt (tháng này)</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-on-surface">{{ $fmt($approvedThisMonth) }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">ngày</span>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col border-primary/20 bg-primary/5">
                <span class="font-label-md text-label-md text-primary mb-sm uppercase tracking-wider">Đang nghỉ hôm nay</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-primary">{{ $onLeaveToday }}</span>
                    <span class="font-body-md text-body-md text-primary/70">người</span>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Tổng đã dùng (năm)</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-on-surface">{{ $fmt($usedThisYear) }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">ngày</span>
                </div>
            </div>
        @else
            <div class="glass-card p-lg rounded-xl flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Tổng ngày phép (tháng)</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-primary">{{ $monthlyQuota }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">ngày</span>
                </div>
                <div class="mt-auto pt-md flex items-center text-on-primary-fixed-variant text-[12px]">
                    <span class="material-symbols-outlined text-[16px] mr-1">info</span>
                    <span>{{ \Carbon\Carbon::now()->translatedFormat('\T\h\á\n\g m/Y') }}</span>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Đã sử dụng</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-on-surface">{{ $fmt($usedThisMonth) }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">ngày</span>
                </div>
                <div class="mt-md w-full bg-outline-variant h-1.5 rounded-full overflow-hidden">
                    <div class="{{ $usedThisMonth > $monthlyQuota ? 'bg-error' : 'bg-primary' }} h-full" style="width: {{ $monthlyQuota > 0 ? min(100, round($usedThisMonth / $monthlyQuota * 100)) : ($usedThisMonth > 0 ? 100 : 0) }}%"></div>
                </div>
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col {{ $balance < 0 ? 'border-error/30 bg-error/5' : 'border-primary/20 bg-primary/5' }}">
                <span class="font-label-md text-label-md {{ $balance < 0 ? 'text-error' : 'text-primary' }} mb-sm uppercase tracking-wider">Số dư hiện tại</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg {{ $balance < 0 ? 'text-error' : 'text-primary' }}">{{ $fmt($balance) }}</span>
                    <span class="font-body-md text-body-md {{ $balance < 0 ? 'text-error/70' : 'text-primary/70' }}">ngày</span>
                </div>
                @if ($balance < 0)
                    <div class="mt-auto pt-md flex items-center text-error text-[12px]">
                        <span class="material-symbols-outlined text-[16px] mr-1">warning</span>
                        <span>Đã nghỉ vượt định mức</span>
                    </div>
                @endif
            </div>
            <div class="glass-card p-lg rounded-xl flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Đang chờ duyệt</span>
                <div class="flex items-baseline gap-xs">
                    <span class="font-display-lg text-display-lg text-tertiary">{{ $pendingCount }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">đơn</span>
                </div>
            </div>
        @endif
        </section>

        <div class="space-y-lg">
            <!-- Pending -->
            <section class="bg-surface rounded-xl border border-outline-variant overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center bg-surface-container-lowest">
                    <h2 class="font-headline-md text-headline-md text-on-surface">Đơn chờ phê duyệt</h2>
                    <span class="px-sm py-xs bg-tertiary-container text-on-tertiary-fixed-variant rounded-full text-[12px] font-medium">{{ $pendingCount }} yêu cầu</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-high">
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Nhân viên</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Loại nghỉ</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Thời gian</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Số ngày</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Lý do</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($pending as $leave)
                                <tr class="hover:bg-surface-container transition-colors">
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface">{{ $leave->employee?->name }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface">{{ $leave->type_label }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface-variant">{{ $leave->start_date->format('d/m') }} - {{ $leave->end_date->format('d/m/Y') }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface">{{ rtrim(rtrim(number_format($leave->days, 1), '0'), '.') }} ngày</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface-variant italic">{{ $leave->reason ?? '—' }}</td>
                                    <td class="px-lg py-md">
                                        @can('admin')
                                        <div class="flex items-center gap-sm">
                                            <form method="POST" action="{{ route('leaves.status', $leave) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button class="text-primary hover:underline text-[12px] font-medium">Duyệt</button>
                                            </form>
                                            <form method="POST" action="{{ route('leaves.status', $leave) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="text-error hover:underline text-[12px] font-medium">Từ chối</button>
                                            </form>
                                        </div>
                                        @elseif($myEmployeeId && $leave->employee_id === $myEmployeeId)
                                        <form method="POST" action="{{ route('leaves.cancel', $leave) }}" onsubmit="return confirm('Huỷ đơn nghỉ này?')">
                                            @csrf @method('PATCH')
                                            <button class="text-error hover:underline text-[12px] font-medium flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[16px]">cancel</span> Huỷ đơn
                                            </button>
                                        </form>
                                        @else
                                        <span class="text-outline text-[12px]">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-lg py-lg text-center text-on-surface-variant">Không có đơn nào đang chờ.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- History -->
            <section class="bg-surface rounded-xl border border-outline-variant overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                    <h2 class="font-headline-md text-headline-md text-on-surface">Lịch sử nghỉ phép</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low">
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Nhân viên</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Loại nghỉ</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Thời gian</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Số ngày</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Người duyệt</th>
                                <th class="px-lg py-sm font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($history as $leave)
                                <tr class="hover:bg-surface-container transition-colors">
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface">{{ $leave->employee?->name }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface">{{ $leave->type_label }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface-variant">{{ $leave->start_date->format('d/m') }} - {{ $leave->end_date->format('d/m/Y') }}</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface">{{ rtrim(rtrim(number_format($leave->days, 1), '0'), '.') }} ngày</td>
                                    <td class="px-lg py-md font-body-md text-body-md text-on-surface-variant">{{ $leave->approver_name ?? '—' }}</td>
                                    <td class="px-lg py-md"><x-status-badge :status="$leave->status" :label="$leave->status_label" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-lg py-lg text-center text-on-surface-variant">Chưa có lịch sử nghỉ phép.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-lg py-md bg-surface-container-low">{{ $history->links() }}</div>
            </section>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="fixed inset-0 z-[60] flex items-center justify-center p-md bg-on-surface/40 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300" id="leaveModal">
    <div class="bg-surface w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300" id="leaveModalContainer">
        <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
            <h3 class="font-headline-md text-headline-md text-on-surface">Tạo đơn nghỉ phép mới</h3>
            <button type="button" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-surface-container transition-colors" onclick="closeLeaveModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form class="p-lg space-y-lg" method="POST" action="{{ route('leaves.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant px-1">Loại nghỉ phép</label>
                    <select name="type" class="form-select rounded-lg border-outline-variant focus:ring-primary/20 focus:border-primary font-body-md text-body-md">
                        @foreach (\App\Models\LeaveRequest::TYPE_LABELS as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', 'monthly') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-outline px-1 flex items-start gap-1">
                        <span class="material-symbols-outlined text-[14px] mt-px">info</span>
                        <span>Với <span class="font-medium">Nghỉ phép tháng</span>: nếu vượt quá số dư còn lại, phần vượt sẽ tự động ghi nhận là <span class="font-medium">Nghỉ không lương</span>.</span>
                    </p>
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant px-1">Số dư còn lại (tháng)</label>
                    <input class="bg-surface-container rounded-lg border-outline-variant font-body-md text-body-md {{ $myBalance < 0 ? 'text-error' : 'text-primary' }} font-semibold" readonly type="text" value="{{ rtrim(rtrim(number_format($myBalance, 1), '0'), '.') }} ngày">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant px-1">Từ ngày</label>
                    <input name="start_date" required class="form-input rounded-lg border-outline-variant focus:ring-primary/20 focus:border-primary font-body-md text-body-md" type="date">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant px-1">Đến ngày</label>
                    <input name="end_date" required class="form-input rounded-lg border-outline-variant focus:ring-primary/20 focus:border-primary font-body-md text-body-md" type="date">
                </div>
            </div>
            <div class="flex flex-col gap-xs">
                <label class="font-label-md text-label-md text-on-surface-variant px-1">Lý do nghỉ</label>
                <textarea name="reason" class="form-textarea rounded-lg border-outline-variant focus:ring-primary/20 focus:border-primary font-body-md text-body-md resize-none" placeholder="Nhập lý do chi tiết..." rows="3"></textarea>
            </div>
            <div class="flex justify-end gap-md pt-md">
                <button type="button" class="px-lg py-sm rounded-full font-body-md text-body-md text-on-surface-variant hover:bg-surface-container transition-colors" onclick="closeLeaveModal()">Hủy bỏ</button>
                <button type="submit" class="bg-primary text-on-primary px-xl py-sm rounded-full font-body-md text-body-md shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">Gửi yêu cầu</button>
            </div>
        </form>
    </div>
</div>

<button class="md:hidden fixed bottom-24 right-lg w-14 h-14 bg-primary text-on-primary rounded-full shadow-2xl flex items-center justify-center z-40 active:scale-90 transition-transform" onclick="openLeaveModal()">
    <span class="material-symbols-outlined">add</span>
</button>
@endsection

@push('scripts')
<script>
    function openLeaveModal() {
        document.getElementById('leaveModal').classList.remove('opacity-0', 'pointer-events-none');
        document.getElementById('leaveModalContainer').classList.replace('scale-95', 'scale-100');
    }
    function closeLeaveModal() {
        document.getElementById('leaveModal').classList.add('opacity-0', 'pointer-events-none');
        document.getElementById('leaveModalContainer').classList.replace('scale-100', 'scale-95');
    }
    document.getElementById('leaveModal').addEventListener('click', function (e) {
        if (e.target === this) closeLeaveModal();
    });
    @if ($errors->any()) openLeaveModal(); @endif
</script>
@endpush
