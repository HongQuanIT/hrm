@extends('layouts.app')

@section('title', 'Công nợ')
@section('page-title', 'Công nợ')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Công nợ" subtitle="Khoản phải thu & phải trả">
            <button type="button" onclick="openModal('add-debt')" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Thêm công nợ</span>
            </button>
        </x-page-header>

        @include('finance._nav')
        @include('finance._flash')

        <!-- Bộ lọc -->
        <form method="GET" class="flex flex-wrap gap-sm mb-lg">
            <select name="type" onchange="this.form.submit()" class="h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                <option value="">Tất cả loại</option>
                <option value="receivable" @selected(request('type') === 'receivable')>Phải thu</option>
                <option value="payable" @selected(request('type') === 'payable')>Phải trả</option>
            </select>
            <select name="status" onchange="this.form.submit()" class="h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                <option value="">Tất cả trạng thái</option>
                @foreach (\App\Models\FinanceDebt::STATUS_LABELS as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if (request('type') || request('status'))
                <a href="{{ route('finance.debts.index') }}" class="h-10 px-md flex items-center border border-outline-variant rounded-lg text-label-md">Xoá lọc</a>
            @endif
        </form>

        <div class="glass-card rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-container-low text-on-surface-variant">
                        <tr class="text-left text-label-md">
                            <th class="px-lg py-md font-medium">Đối tác</th>
                            <th class="px-lg py-md font-medium">Loại</th>
                            <th class="px-lg py-md font-medium text-right">Tổng</th>
                            <th class="px-lg py-md font-medium text-right">Còn lại</th>
                            <th class="px-lg py-md font-medium">Hạn</th>
                            <th class="px-lg py-md font-medium">Trạng thái</th>
                            <th class="px-lg py-md font-medium text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($debts as $debt)
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="px-lg py-md text-body-md text-on-surface font-medium">
                                    {{ $debt->partner_name }}
                                    @if ($debt->partner_contact) <span class="block text-[11px] text-outline">{{ $debt->partner_contact }}</span> @endif
                                </td>
                                <td class="px-lg py-md text-body-md {{ $debt->type === 'receivable' ? 'text-green-700' : 'text-error' }}">{{ $debt->type_label }}</td>
                                <td class="px-lg py-md text-right text-body-md whitespace-nowrap">{{ number_format($debt->amount, 0, ',', '.') }} ₫</td>
                                <td class="px-lg py-md text-right text-body-md font-bold whitespace-nowrap">{{ number_format($debt->remaining_amount, 0, ',', '.') }} ₫</td>
                                <td class="px-lg py-md text-body-md whitespace-nowrap {{ $debt->is_overdue ? 'text-error font-bold' : 'text-on-surface-variant' }}">
                                    {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-lg py-md"><x-status-badge :status="$debt->status" :label="$debt->status_label" /></td>
                                <td class="px-lg py-md text-right whitespace-nowrap">
                                    @if (! in_array($debt->status, ['paid', 'cancelled']))
                                        <button type="button" onclick="openModal('pay-{{ $debt->id }}')" class="px-sm py-1.5 rounded-lg bg-primary/10 text-primary text-label-md">Thanh toán</button>
                                        <button type="button" onclick="openModal('edit-debt-{{ $debt->id }}')" class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant material-symbols-outlined text-[18px] align-middle">edit</button>
                                        <form method="POST" action="{{ route('finance.debts.cancel', $debt) }}" class="inline" onsubmit="return confirm('Huỷ công nợ này?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant material-symbols-outlined text-[18px] align-middle" title="Huỷ">block</button>
                                        </form>
                                    @endif
                                    @if (! $debt->transactions()->exists())
                                        <form method="POST" action="{{ route('finance.debts.destroy', $debt) }}" class="inline" onsubmit="return confirm('Xoá công nợ này?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded-lg text-error hover:bg-error-container material-symbols-outlined text-[18px] align-middle">delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">Chưa có công nợ nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($debts->hasPages())
                <div class="px-lg py-md border-t border-outline-variant">{{ $debts->links() }}</div>
            @endif
        </div>
    </div>
</div>

<x-finance-modal id="add-debt" title="Thêm công nợ">
    <form method="POST" action="{{ route('finance.debts.store') }}">
        @csrf
        @include('finance._debt-fields')
        <div class="flex justify-end gap-sm pt-md">
            <button type="button" onclick="closeModal('add-debt')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu công nợ</button>
        </div>
    </form>
</x-finance-modal>

@foreach ($debts as $debt)
    @if (! in_array($debt->status, ['paid', 'cancelled']))
        <x-finance-modal id="pay-{{ $debt->id }}" title="Thanh toán: {{ $debt->partner_name }}">
            <form method="POST" action="{{ route('finance.debts.pay', $debt) }}" class="space-y-md">
                @csrf
                <p class="text-[12px] text-on-surface-variant">Còn lại: <b>{{ number_format($debt->remaining_amount, 0, ',', '.') }} ₫</b></p>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Quỹ {{ $debt->type === 'payable' ? 'chi tiền' : 'nhận tiền' }} *</label>
                    <select name="account_id" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số tiền (₫) *</label>
                    <input name="amount" type="text" inputmode="numeric" value="{{ (int) $debt->remaining_amount }}" required class="money-input w-full h-11 px-md border border-outline-variant rounded-lg">
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Ngày *</label>
                    <input name="occurred_on" type="date" value="{{ now()->toDateString() }}" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
                </div>
                <div class="flex justify-end gap-sm pt-sm">
                    <button type="button" onclick="closeModal('pay-{{ $debt->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                    <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Ghi nhận</button>
                </div>
            </form>
        </x-finance-modal>

        <x-finance-modal id="edit-debt-{{ $debt->id }}" title="Sửa công nợ">
            <form method="POST" action="{{ route('finance.debts.update', $debt) }}">
                @csrf @method('PUT')
                @include('finance._debt-fields', ['debt' => $debt])
                <div class="flex justify-end gap-sm pt-md">
                    <button type="button" onclick="closeModal('edit-debt-{{ $debt->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                    <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu</button>
                </div>
            </form>
        </x-finance-modal>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById('modal-' + id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById('modal-' + id)?.classList.add('hidden'); }
    @if ($errors->any()) openModal('add-debt'); @endif
</script>
@endpush
