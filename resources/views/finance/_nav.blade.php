@php
    $tabs = [
        ['label' => 'Tổng quan', 'route' => 'finance.overview', 'pattern' => 'finance.overview', 'icon' => 'insights'],
        ['label' => 'Quỹ tiền', 'route' => 'finance.accounts.index', 'pattern' => 'finance.accounts.*', 'icon' => 'account_balance_wallet'],
        ['label' => 'Giao dịch', 'route' => 'finance.transactions.index', 'pattern' => 'finance.transactions.*', 'icon' => 'receipt_long'],
        ['label' => 'Công nợ', 'route' => 'finance.debts.index', 'pattern' => 'finance.debts.*', 'icon' => 'handshake'],
        ['label' => 'Danh mục', 'route' => 'finance.categories.index', 'pattern' => 'finance.categories.*', 'icon' => 'sell'],
    ];
@endphp
<div class="flex flex-wrap gap-xs mb-xl border-b border-outline-variant pb-md">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['pattern']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="flex items-center gap-xs px-md py-sm rounded-lg font-label-md text-label-md transition-colors {{ $active ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container' }}">
            <span class="material-symbols-outlined text-[20px]">{{ $tab['icon'] }}</span>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</div>
