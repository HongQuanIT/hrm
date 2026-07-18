@extends('layouts.app')

@section('title', $period->label)
@section('page-title', $period->label)

@section('content')
@php
    $netTotal = $period->payslips->sum('net_amount');
    $grossTotal = $period->payslips->sum('gross_amount');
    $deductionTotal = $period->payslips->sum('deduction_total');
@endphp
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="{{ $period->label }}" subtitle="{{ $period->days_in_month }} ngày trong kỳ · {{ $period->status_label }}">
            <a href="{{ route('payroll.periods.index') }}" class="flex items-center gap-xs border border-outline-variant px-md py-sm rounded-lg text-label-md">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span><span>Danh sách kỳ</span>
            </a>
            @if (! $period->is_locked)
                <form method="POST" action="{{ route('payroll.periods.calculate', $period) }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">calculate</span><span>Tính lương</span>
                    </button>
                </form>
            @endif
            @if ($period->status === 'calculated')
                <form method="POST" action="{{ route('payroll.periods.approve', $period) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="flex items-center gap-xs bg-tertiary text-on-tertiary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">verified</span><span>Duyệt</span>
                    </button>
                </form>
            @endif
            @if ($period->status === 'approved')
                <form method="POST" action="{{ route('payroll.periods.reopen', $period) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="flex items-center gap-xs border border-outline-variant px-md py-sm rounded-lg text-label-md">
                        <span class="material-symbols-outlined text-[20px]">lock_open</span><span>Mở lại</span>
                    </button>
                </form>
                <button type="button" onclick="openModal('pay-period')" class="flex items-center gap-xs bg-green-600 text-white px-md py-sm rounded-lg font-label-md text-label-md shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">payments</span><span>Chi lương</span>
                </button>
            @endif
        </x-page-header>

        @include('payroll._flash')

        @if ($period->status === 'paid' && $period->transaction)
            <div class="mb-lg bg-green-50 text-green-800 px-lg py-md rounded-xl flex items-center gap-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span>Đã chi lương ngày {{ $period->transaction->occurred_on?->format('d/m/Y') }} — giao dịch #{{ $period->transaction->id }} trong Tài chính.</span>
            </div>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-md mb-lg">
            <div class="glass-card p-md rounded-xl">
                <p class="text-[12px] text-on-surface-variant">Số phiếu</p>
                <p class="font-headline-md text-headline-md text-on-surface mt-xs">{{ $period->payslips->count() }}</p>
            </div>
            <div class="glass-card p-md rounded-xl">
                <p class="text-[12px] text-on-surface-variant">Tổng thu nhập</p>
                <p class="font-headline-md text-headline-md text-on-surface mt-xs">{{ number_format($grossTotal, 0, ',', '.') }} ₫</p>
            </div>
            <div class="glass-card p-md rounded-xl">
                <p class="text-[12px] text-on-surface-variant">Tổng khấu trừ</p>
                <p class="font-headline-md text-headline-md text-error mt-xs">{{ number_format($deductionTotal, 0, ',', '.') }} ₫</p>
            </div>
            <div class="glass-card p-md rounded-xl">
                <p class="text-[12px] text-on-surface-variant">Tổng thực nhận</p>
                <p class="font-headline-md text-headline-md text-primary mt-xs">{{ number_format($netTotal, 0, ',', '.') }} ₫</p>
            </div>
        </div>

        <div class="glass-card rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-container-low text-on-surface-variant">
                        <tr class="text-left text-label-md whitespace-nowrap">
                            <th class="px-lg py-md font-medium">Nhân viên</th>
                            <th class="px-lg py-md font-medium text-center">Công / Nghỉ / Vắng</th>
                            <th class="px-lg py-md font-medium text-center">Ngày tính lương</th>
                            <th class="px-lg py-md font-medium text-right">Thu nhập</th>
                            <th class="px-lg py-md font-medium text-right">Khấu trừ</th>
                            <th class="px-lg py-md font-medium text-right">Thực nhận</th>
                            <th class="px-lg py-md font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($period->payslips as $slip)
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="px-lg py-md text-body-md text-on-surface">
                                    <a href="{{ route('payroll.payslips.show', [$period, $slip]) }}" class="font-medium hover:text-primary">{{ $slip->employee?->name ?? '—' }}</a>
                                    @if ($slip->note)<span class="block text-[11px] text-amber-600">{{ $slip->note }}</span>@endif
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant text-center whitespace-nowrap">
                                    {{ rtrim(rtrim(number_format($slip->present_days, 1), '0'), '.') }} /
                                    {{ rtrim(rtrim(number_format($slip->paid_leave_days, 1), '0'), '.') }} /
                                    {{ rtrim(rtrim(number_format($slip->absent_days, 1), '0'), '.') }}
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface text-center font-medium">{{ rtrim(rtrim(number_format($slip->paid_days, 1), '0'), '.') }}/{{ $slip->days_in_month }}</td>
                                <td class="px-lg py-md text-body-md text-on-surface text-right whitespace-nowrap">{{ number_format($slip->gross_amount, 0, ',', '.') }}</td>
                                <td class="px-lg py-md text-body-md text-error text-right whitespace-nowrap">{{ $slip->deduction_total > 0 ? '−' . number_format($slip->deduction_total, 0, ',', '.') : '—' }}</td>
                                <td class="px-lg py-md text-body-md text-primary text-right font-bold whitespace-nowrap">{{ number_format($slip->net_amount, 0, ',', '.') }} ₫</td>
                                <td class="px-lg py-md text-right">
                                    <a href="{{ route('payroll.payslips.show', [$period, $slip]) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-surface-container text-on-surface-variant material-symbols-outlined text-[18px]">chevron_right</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">Chưa có phiếu lương. Bấm "Tính lương" để lập phiếu cho nhân viên đang làm việc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-[12px] text-on-surface-variant mt-sm">Cột "Công / Nghỉ / Vắng" = ngày đi làm / ngày phép có lương / ngày vắng.</p>
    </div>
</div>

@if ($period->status === 'approved')
<x-finance-modal id="pay-period" title="Chi lương kỳ này">
    <form method="POST" action="{{ route('payroll.periods.pay', $period) }}" class="space-y-md">
        @csrf
        <div class="bg-surface-container-low rounded-lg p-md text-body-md">
            Tổng thực nhận: <span class="font-bold text-primary">{{ number_format($netTotal, 0, ',', '.') }} ₫</span>
        </div>
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Chi từ quỹ</label>
            <select name="account_id" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                @foreach ($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ number_format($acc->balance, 0, ',', '.') }} ₫)</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Ngày chi</label>
            <input type="date" name="occurred_on" value="{{ now()->toDateString() }}" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
        </div>
        <p class="text-[12px] text-on-surface-variant">Sẽ sinh một giao dịch chi danh mục "Lương" và khoá kỳ.</p>
        <div class="flex justify-end gap-sm pt-md">
            <button type="button" onclick="closeModal('pay-period')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
            <button type="submit" class="px-lg py-sm bg-green-600 text-white rounded-lg font-medium text-label-md">Xác nhận chi</button>
        </div>
    </form>
</x-finance-modal>
@endif
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById('modal-' + id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById('modal-' + id)?.classList.add('hidden'); }
    @if ($errors->any()) openModal('pay-period'); @endif
</script>
@endpush
