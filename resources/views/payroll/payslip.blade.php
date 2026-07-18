@extends('layouts.app')

@section('title', 'Phiếu lương')
@section('page-title', 'Phiếu lương')

@section('content')
@php
    $fmtDay = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
@endphp
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-3xl mx-auto">
        <x-page-header title="Phiếu lương — {{ $payslip->employee?->name }}" subtitle="{{ $period->label }} · {{ $period->status_label }}">
            @can('admin')
                <a href="{{ route('payroll.periods.show', $period) }}" class="flex items-center gap-xs border border-outline-variant px-md py-sm rounded-lg text-label-md">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span><span>Về kỳ lương</span>
                </a>
            @else
                <a href="{{ route('payroll.my') }}" class="flex items-center gap-xs border border-outline-variant px-md py-sm rounded-lg text-label-md">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span><span>Phiếu của tôi</span>
                </a>
            @endcan
        </x-page-header>

        @include('payroll._flash')

        <div class="glass-card rounded-xl shadow-sm p-lg mb-lg">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-md text-center">
                <div><p class="text-[12px] text-on-surface-variant">Ngày đi làm</p><p class="font-bold text-on-surface mt-xs">{{ $fmtDay($payslip->present_days) }}</p></div>
                <div><p class="text-[12px] text-on-surface-variant">Phép có lương</p><p class="font-bold text-on-surface mt-xs">{{ $fmtDay($payslip->paid_leave_days) }}</p></div>
                <div><p class="text-[12px] text-on-surface-variant">Không lương / Vắng</p><p class="font-bold text-error mt-xs">{{ $fmtDay($payslip->unpaid_leave_days) }} / {{ $fmtDay($payslip->absent_days) }}</p></div>
                <div><p class="text-[12px] text-on-surface-variant">Ngày tính lương</p><p class="font-bold text-primary mt-xs">{{ $fmtDay($payslip->paid_days) }}/{{ $payslip->days_in_month }}</p></div>
            </div>
            @if ($payslip->note)
                <div class="mt-md text-[12px] text-amber-600 flex items-center gap-xs"><span class="material-symbols-outlined text-[16px]">warning</span>{{ $payslip->note }}</div>
            @endif
        </div>

        <div class="glass-card rounded-xl shadow-sm overflow-hidden mb-lg">
            <div class="px-lg py-md border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-medium text-on-surface">Chi tiết khoản</h3>
                @if ($canEdit)
                    <button type="button" onclick="openModal('add-item')" class="flex items-center gap-xs text-primary text-label-md">
                        <span class="material-symbols-outlined text-[18px]">add</span>Thêm khoản
                    </button>
                @endif
            </div>
            <table class="w-full">
                <tbody class="divide-y divide-outline-variant">
                    @foreach ($payslip->items->sortBy('type') as $item)
                        <tr class="hover:bg-surface-container-lowest">
                            <td class="px-lg py-md text-body-md text-on-surface">
                                {{ $item->label }}
                                @unless ($item->is_system)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-secondary-container text-on-secondary-fixed-variant">Thủ công</span>@endunless
                            </td>
                            <td class="px-lg py-md text-right font-medium whitespace-nowrap {{ $item->type === 'earning' ? 'text-green-700' : 'text-error' }}">
                                {{ $item->type === 'earning' ? '+' : '−' }}{{ number_format($item->amount, 0, ',', '.') }} ₫
                            </td>
                            <td class="px-lg py-md text-right w-10">
                                @if ($canEdit && ! $item->is_system)
                                    <form method="POST" action="{{ route('payroll.payslips.items.destroy', [$period, $payslip, $item]) }}" onsubmit="return confirm('Xoá khoản này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg text-error hover:bg-error-container material-symbols-outlined text-[18px] align-middle">delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-surface-container-low">
                    <tr>
                        <td class="px-lg py-sm text-body-md text-on-surface-variant">Tổng thu nhập</td>
                        <td class="px-lg py-sm text-right font-medium text-green-700 whitespace-nowrap">+{{ number_format($payslip->gross_amount, 0, ',', '.') }} ₫</td><td></td>
                    </tr>
                    <tr>
                        <td class="px-lg py-sm text-body-md text-on-surface-variant">Tổng khấu trừ</td>
                        <td class="px-lg py-sm text-right font-medium text-error whitespace-nowrap">−{{ number_format($payslip->deduction_total, 0, ',', '.') }} ₫</td><td></td>
                    </tr>
                    <tr class="border-t-2 border-outline-variant">
                        <td class="px-lg py-md text-body-lg font-bold text-on-surface">Thực nhận</td>
                        <td class="px-lg py-md text-right text-headline-md font-bold text-primary whitespace-nowrap">{{ number_format($payslip->net_amount, 0, ',', '.') }} ₫</td><td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($payslip->bank_snapshot)
            <p class="text-[12px] text-on-surface-variant">Tài khoản nhận: {{ $payslip->bank_snapshot }}</p>
        @endif
    </div>
</div>

@if ($canEdit)
<x-finance-modal id="add-item" title="Thêm khoản">
    <form method="POST" action="{{ route('payroll.payslips.items.store', [$period, $payslip]) }}" class="space-y-md">
        @csrf
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Loại</label>
            <select name="type" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                <option value="earning">Khoản cộng (thưởng, phụ cấp…)</option>
                <option value="deduction">Khoản trừ (tạm ứng, phạt…)</option>
            </select>
        </div>
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Diễn giải</label>
            <input type="text" name="label" value="{{ old('label') }}" maxlength="255" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md" placeholder="VD: Thưởng dự án">
        </div>
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Số tiền (₫)</label>
            <input type="number" name="amount" value="{{ old('amount') }}" min="1" step="1000" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
        </div>
        <div class="flex justify-end gap-sm pt-md">
            <button type="button" onclick="closeModal('add-item')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu khoản</button>
        </div>
    </form>
</x-finance-modal>
@endif
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById('modal-' + id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById('modal-' + id)?.classList.add('hidden'); }
    @if ($errors->any()) openModal('add-item'); @endif
</script>
@endpush
