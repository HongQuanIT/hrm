@extends('layouts.app')

@section('title', 'Giao dịch')
@section('page-title', 'Sổ giao dịch thu/chi')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Sổ giao dịch" subtitle="Ghi nhận các khoản thu, chi và nạp vốn">
            <button type="button" onclick="openModal('add-transaction')" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Thêm giao dịch</span>
            </button>
        </x-page-header>

        @include('finance._nav')
        @include('finance._flash')

        <!-- Bộ lọc -->
        <form method="GET" class="glass-card p-md rounded-xl shadow-sm mb-lg grid grid-cols-1 md:grid-cols-5 gap-sm items-end">
            <div>
                <label class="block text-[12px] text-on-surface-variant mb-xs">Quỹ</label>
                <select name="account_id" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                    <option value="">Tất cả</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}" @selected(request('account_id') == $acc->id)>{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[12px] text-on-surface-variant mb-xs">Loại</label>
                <select name="direction" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
                    <option value="">Tất cả</option>
                    <option value="income" @selected(request('direction') === 'income')>Thu</option>
                    <option value="expense" @selected(request('direction') === 'expense')>Chi</option>
                </select>
            </div>
            <div>
                <label class="block text-[12px] text-on-surface-variant mb-xs">Từ ngày</label>
                <input name="from" type="date" value="{{ request('from') }}" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
            </div>
            <div>
                <label class="block text-[12px] text-on-surface-variant mb-xs">Đến ngày</label>
                <input name="to" type="date" value="{{ request('to') }}" class="w-full h-10 px-sm border border-outline-variant rounded-lg text-body-md">
            </div>
            <div class="flex gap-sm">
                <button type="submit" class="flex-1 h-10 bg-primary text-on-primary rounded-lg text-label-md">Lọc</button>
                <a href="{{ route('finance.transactions.index') }}" class="h-10 px-md flex items-center border border-outline-variant rounded-lg text-label-md">Xoá</a>
            </div>
        </form>

        <!-- Bảng giao dịch -->
        <div class="glass-card rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-container-low text-on-surface-variant">
                        <tr class="text-left text-label-md">
                            <th class="px-lg py-md font-medium">Ngày</th>
                            <th class="px-lg py-md font-medium">Diễn giải</th>
                            <th class="px-lg py-md font-medium">Quỹ</th>
                            <th class="px-lg py-md font-medium">Danh mục</th>
                            <th class="px-lg py-md font-medium text-right">Số tiền</th>
                            <th class="px-lg py-md font-medium text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($transactions as $tx)
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="px-lg py-md text-body-md text-on-surface-variant whitespace-nowrap">{{ $tx->occurred_on->format('d/m/Y') }}</td>
                                <td class="px-lg py-md text-body-md text-on-surface">
                                    {{ $tx->description ?: '—' }}
                                    @if ($tx->is_contribution) <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-bold">NẠP VỐN</span> @endif
                                    @if ($tx->debt_id) <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-secondary-container text-on-secondary-fixed-variant">Công nợ</span> @endif
                                    @if ($tx->contributor_name) <span class="block text-[11px] text-outline">{{ $tx->contributor_name }}</span> @endif
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant">{{ $tx->account?->name }}</td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant">{{ $tx->category?->name ?: '—' }}</td>
                                <td class="px-lg py-md text-right font-bold whitespace-nowrap {{ $tx->direction === 'income' ? 'text-green-700' : 'text-error' }}">
                                    {{ $tx->direction === 'income' ? '+' : '−' }}{{ number_format($tx->amount, 0, ',', '.') }} ₫
                                </td>
                                <td class="px-lg py-md text-right whitespace-nowrap">
                                    <button type="button" onclick="openModal('edit-tx-{{ $tx->id }}')" class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant material-symbols-outlined text-[18px] align-middle">edit</button>
                                    <form method="POST" action="{{ route('finance.transactions.destroy', $tx) }}" class="inline" onsubmit="return confirm('Xoá giao dịch này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg text-error hover:bg-error-container material-symbols-outlined text-[18px] align-middle">delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-lg py-xl text-center text-on-surface-variant">Chưa có giao dịch nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                <div class="px-lg py-md border-t border-outline-variant">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
</div>

<x-finance-modal id="add-transaction" title="Thêm giao dịch">
    <form method="POST" action="{{ route('finance.transactions.store') }}">
        @csrf
        @include('finance._transaction-fields')
        <div class="flex justify-end gap-sm pt-md">
            <button type="button" onclick="closeModal('add-transaction')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu giao dịch</button>
        </div>
    </form>
</x-finance-modal>

@foreach ($transactions as $tx)
    <x-finance-modal id="edit-tx-{{ $tx->id }}" title="Sửa giao dịch">
        <form method="POST" action="{{ route('finance.transactions.update', $tx) }}">
            @csrf @method('PUT')
            @include('finance._transaction-fields', ['transaction' => $tx])
            <div class="flex justify-end gap-sm pt-md">
                <button type="button" onclick="closeModal('edit-tx-{{ $tx->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu</button>
            </div>
        </form>
    </x-finance-modal>
@endforeach
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById('modal-' + id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById('modal-' + id)?.classList.add('hidden'); }
    @if ($errors->any()) openModal('add-transaction'); @endif
</script>
@endpush
