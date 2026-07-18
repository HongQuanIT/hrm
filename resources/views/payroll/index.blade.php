@extends('layouts.app')

@section('title', 'Lương')
@section('page-title', 'Bảng lương')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Kỳ lương" subtitle="Tạo kỳ lương theo tháng, tính lương, duyệt và chi qua Tài chính">
            <button type="button" onclick="openModal('add-period')" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Tạo kỳ lương</span>
            </button>
        </x-page-header>

        @include('payroll._flash')

        <div class="glass-card rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-container-low text-on-surface-variant">
                        <tr class="text-left text-label-md">
                            <th class="px-lg py-md font-medium">Kỳ</th>
                            <th class="px-lg py-md font-medium text-center">Số phiếu</th>
                            <th class="px-lg py-md font-medium text-right">Tổng thực nhận</th>
                            <th class="px-lg py-md font-medium text-center">Trạng thái</th>
                            <th class="px-lg py-md font-medium text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($periods as $period)
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="px-lg py-md text-body-md text-on-surface font-medium">
                                    <a href="{{ route('payroll.periods.show', $period) }}" class="hover:text-primary">{{ $period->label }}</a>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant text-center">{{ $period->payslips_count }}</td>
                                <td class="px-lg py-md text-body-md text-on-surface text-right font-bold whitespace-nowrap">{{ number_format($period->net_total ?? 0, 0, ',', '.') }} ₫</td>
                                <td class="px-lg py-md text-center">
                                    @php
                                        $badge = [
                                            'draft' => 'bg-surface-container text-on-surface-variant',
                                            'calculated' => 'bg-secondary-container text-on-secondary-fixed-variant',
                                            'approved' => 'bg-tertiary-container text-on-tertiary-fixed-variant',
                                            'paid' => 'bg-green-100 text-green-700',
                                        ][$period->status] ?? 'bg-surface-container';
                                    @endphp
                                    <span class="text-[11px] px-2 py-0.5 rounded-full font-bold {{ $badge }}">{{ $period->status_label }}</span>
                                </td>
                                <td class="px-lg py-md text-right whitespace-nowrap">
                                    <a href="{{ route('payroll.periods.show', $period) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-surface-container text-on-surface-variant material-symbols-outlined text-[18px]">visibility</a>
                                    @if ($period->status !== 'paid')
                                        <form method="POST" action="{{ route('payroll.periods.destroy', $period) }}" class="inline" onsubmit="return confirm('Xoá kỳ lương này?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded-lg text-error hover:bg-error-container material-symbols-outlined text-[18px] align-middle">delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-lg py-xl text-center text-on-surface-variant">Chưa có kỳ lương nào. Bấm "Tạo kỳ lương" để bắt đầu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($periods->hasPages())
                <div class="px-lg py-md border-t border-outline-variant">{{ $periods->links() }}</div>
            @endif
        </div>
    </div>
</div>

<x-finance-modal id="add-period" title="Tạo kỳ lương">
    <form method="POST" action="{{ route('payroll.periods.store') }}" class="space-y-md">
        @csrf
        <div class="grid grid-cols-2 gap-sm">
            <div>
                <label class="block text-[12px] text-on-surface-variant mb-xs">Tháng</label>
                <select name="month" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected(old('month', now()->month) == $m)>Tháng {{ $m }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-[12px] text-on-surface-variant mb-xs">Năm</label>
                <input type="number" name="year" value="{{ old('year', now()->year) }}" min="2020" max="2100" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
            </div>
        </div>
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Số ngày trong kỳ (để trống = tự tính theo lịch)</label>
            <input type="number" name="days_in_month" value="{{ old('days_in_month') }}" min="28" max="31" placeholder="28–31" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
        </div>
        <div>
            <label class="block text-[12px] text-on-surface-variant mb-xs">Ghi chú</label>
            <input type="text" name="note" value="{{ old('note') }}" maxlength="500" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
        </div>
        <div class="flex justify-end gap-sm pt-md">
            <button type="button" onclick="closeModal('add-period')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Tạo kỳ</button>
        </div>
    </form>
</x-finance-modal>
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById('modal-' + id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById('modal-' + id)?.classList.add('hidden'); }
    @if ($errors->any()) openModal('add-period'); @endif
</script>
@endpush
