@extends('layouts.app')

@section('title', 'Quỹ tiền')
@section('page-title', 'Quỹ tiền')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Quỹ tiền" subtitle="Tiền mặt & tài khoản ngân hàng của công ty">
            <button type="button" onclick="toggleAddAccount()" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Thêm quỹ</span>
            </button>
        </x-page-header>

        @include('finance._nav')
        @include('finance._flash')

        <!-- Tổng hợp -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-md mb-xl">
            <div class="glass-card p-lg rounded-xl shadow-sm">
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Số dư hiện có</p>
                <h3 class="font-headline-lg text-headline-lg {{ $summary['balance'] < 0 ? 'text-error' : 'text-on-surface' }}">{{ number_format($summary['balance'], 0, ',', '.') }} ₫</h3>
            </div>
            <div class="glass-card p-lg rounded-xl shadow-sm">
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng đã nạp</p>
                <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ number_format($summary['contributed'], 0, ',', '.') }} ₫</h3>
            </div>
            <div class="glass-card p-lg rounded-xl shadow-sm">
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng đã chi</p>
                <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ number_format($summary['spent'], 0, ',', '.') }} ₫</h3>
            </div>
        </div>

        <!-- Form thêm quỹ -->
        <form method="POST" action="{{ route('finance.accounts.store') }}" id="add-account-form" class="hidden mb-lg bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            @csrf
            @include('finance._account-fields')
            <div class="flex justify-end gap-sm mt-md">
                <button type="button" onclick="toggleAddAccount()" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu quỹ</button>
            </div>
        </form>

        <!-- Danh sách quỹ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            @forelse ($accounts as $account)
                <div class="glass-card p-lg rounded-xl shadow-sm {{ $account->is_active ? '' : 'opacity-60' }}">
                    <div class="flex items-start justify-between mb-md">
                        <div class="flex items-center gap-md">
                            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined">{{ $account->type === 'bank' ? 'account_balance' : 'payments' }}</span>
                            </div>
                            <div>
                                <p class="font-body-md text-body-md font-bold text-on-surface">{{ $account->name }}</p>
                                <p class="text-[12px] text-on-surface-variant">
                                    {{ $account->type_label }}
                                    @if ($account->bank_name) • {{ $account->bank_name }} @endif
                                    @if (! $account->is_active) • Ngừng hoạt động @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-md">
                        <p class="text-[12px] text-on-surface-variant">Số dư hiện tại</p>
                        <p class="font-headline-md text-headline-md {{ $account->balance < 0 ? 'text-error' : 'text-on-surface' }}">{{ number_format($account->balance, 0, ',', '.') }} ₫</p>
                        <p class="text-[11px] text-outline">Đầu kỳ: {{ number_format($account->opening_balance, 0, ',', '.') }} ₫</p>
                    </div>
                    <div class="flex flex-wrap gap-xs">
                        <button type="button" onclick="openModal('deposit-{{ $account->id }}')" class="flex items-center gap-xs px-sm py-1.5 rounded-lg bg-green-100 text-green-700 text-label-md"><span class="material-symbols-outlined text-[18px]">add</span>Nạp tiền</button>
                        <button type="button" onclick="openModal('adjust-{{ $account->id }}')" class="flex items-center gap-xs px-sm py-1.5 rounded-lg bg-surface-container text-on-surface-variant text-label-md"><span class="material-symbols-outlined text-[18px]">tune</span>Điều chỉnh</button>
                        <button type="button" onclick="openModal('edit-{{ $account->id }}')" class="flex items-center gap-xs px-sm py-1.5 rounded-lg bg-surface-container text-on-surface-variant text-label-md"><span class="material-symbols-outlined text-[18px]">edit</span>Sửa</button>
                        <form method="POST" action="{{ route('finance.accounts.destroy', $account) }}" onsubmit="return confirm('Xoá quỹ này?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="flex items-center gap-xs px-sm py-1.5 rounded-lg text-error hover:bg-error-container text-label-md"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                        </form>
                    </div>
                </div>

                <!-- Modal nạp tiền -->
                <x-finance-modal id="deposit-{{ $account->id }}" title="Nạp tiền vào {{ $account->name }}">
                    <form method="POST" action="{{ route('finance.accounts.deposit', $account) }}" class="space-y-md">
                        @csrf
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số tiền nạp (₫)</label>
                            <input name="amount" type="text" inputmode="numeric" required class="money-input w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Ngày</label>
                            <input name="occurred_on" type="date" value="{{ now()->toDateString() }}" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Người/nguồn nạp (tuỳ chọn)</label>
                            <input name="contributor_name" type="text" placeholder="VD: Giám đốc góp vốn" class="w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div class="flex justify-end gap-sm pt-sm">
                            <button type="button" onclick="closeModal('deposit-{{ $account->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Nạp tiền</button>
                        </div>
                    </form>
                </x-finance-modal>

                <!-- Modal điều chỉnh số dư -->
                <x-finance-modal id="adjust-{{ $account->id }}" title="Điều chỉnh số dư {{ $account->name }}">
                    <form method="POST" action="{{ route('finance.accounts.adjust', $account) }}" class="space-y-md">
                        @csrf
                        <p class="text-[12px] text-on-surface-variant">Số dư hiện tại: <b>{{ number_format($account->balance, 0, ',', '.') }} ₫</b>. Nhập số dư thực tế, hệ thống sẽ tạo giao dịch chênh lệch.</p>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số dư thực tế (₫)</label>
                            <input name="target_balance" type="text" inputmode="numeric" value="{{ (int) $account->balance }}" required class="money-input w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Ngày</label>
                            <input name="occurred_on" type="date" value="{{ now()->toDateString() }}" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Lý do (tuỳ chọn)</label>
                            <input name="description" type="text" placeholder="VD: Kiểm kê quỹ" class="w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div class="flex justify-end gap-sm pt-sm">
                            <button type="button" onclick="closeModal('adjust-{{ $account->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Điều chỉnh</button>
                        </div>
                    </form>
                </x-finance-modal>

                <!-- Modal sửa quỹ -->
                <x-finance-modal id="edit-{{ $account->id }}" title="Sửa quỹ {{ $account->name }}">
                    <form method="POST" action="{{ route('finance.accounts.update', $account) }}" class="space-y-md">
                        @csrf @method('PUT')
                        @include('finance._account-fields', ['account' => $account])
                        <div class="flex justify-end gap-sm pt-sm">
                            <button type="button" onclick="closeModal('edit-{{ $account->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu</button>
                        </div>
                    </form>
                </x-finance-modal>
            @empty
                <div class="md:col-span-2 glass-card p-xl rounded-xl text-center text-on-surface-variant">
                    Chưa có quỹ nào. Bấm "Thêm quỹ" để bắt đầu.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleAddAccount() {
        document.getElementById('add-account-form').classList.toggle('hidden');
    }
    function openModal(id) {
        document.getElementById('modal-' + id)?.classList.remove('hidden');
    }
    function closeModal(id) {
        document.getElementById('modal-' + id)?.classList.add('hidden');
    }
    @if ($errors->any()) document.getElementById('add-account-form')?.classList.remove('hidden'); @endif
</script>
@endpush
