@extends('layouts.app')

@section('title', 'Tài chính')
@section('page-title', 'Tổng quan tài chính')

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Tổng quan tài chính" subtitle="Dòng vốn, chi phí và công nợ của công ty">
            <a href="{{ route('finance.accounts.index') }}" class="flex items-center gap-xs bg-primary text-on-primary px-md py-sm rounded-lg font-label-md text-label-md shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[20px]">savings</span>
                <span>Quản lý quỹ</span>
            </a>
        </x-page-header>

        @include('finance._nav')

        @php
            $balance = $summary['balance'];
            $balanceNegative = $balance < 0;
        @endphp

        <!-- Chỉ số cốt lõi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md mb-xl">
            <div class="{{ $balanceNegative ? 'bg-error-container text-on-error-container' : 'bg-primary-container text-on-primary-container' }} p-lg rounded-xl shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start mb-sm">
                    <div class="p-xs bg-white/20 rounded-lg">
                        <span class="material-symbols-outlined">account_balance</span>
                    </div>
                    @if ($balanceNegative)
                        <span class="px-2 py-0.5 bg-error text-on-error text-[10px] font-bold rounded-full">ĐANG ÂM</span>
                    @endif
                </div>
                <div>
                    <p class="font-label-md text-label-md opacity-80 uppercase tracking-wider">Số dư hiện có</p>
                    <h3 class="font-display-lg text-display-lg">{{ number_format($balance, 0, ',', '.') }} ₫</h3>
                    @if ($balanceNegative)
                        <p class="text-[12px] mt-xs">Chi vượt vốn — có thể do ứng tiền cá nhân.</p>
                    @endif
                </div>
            </div>

            <div class="glass-card p-lg rounded-xl shadow-sm flex flex-col justify-between">
                <div class="p-xs bg-green-100 text-green-700 rounded-lg w-fit mb-sm"><span class="material-symbols-outlined">south_west</span></div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng đã nạp</p>
                    <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ number_format($summary['contributed'], 0, ',', '.') }} ₫</h3>
                </div>
            </div>

            <div class="glass-card p-lg rounded-xl shadow-sm flex flex-col justify-between">
                <div class="p-xs bg-error-container text-on-error-container rounded-lg w-fit mb-sm"><span class="material-symbols-outlined">north_east</span></div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng đã chi</p>
                    <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ number_format($summary['spent'], 0, ',', '.') }} ₫</h3>
                </div>
            </div>

            <div class="glass-card p-lg rounded-xl shadow-sm flex flex-col justify-between">
                <div class="p-xs bg-secondary-container text-on-secondary-fixed-variant rounded-lg w-fit mb-sm"><span class="material-symbols-outlined">add_card</span></div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng thu khác</p>
                    <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ number_format($summary['other_income'], 0, ',', '.') }} ₫</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl">
            <!-- Biểu đồ dòng tiền -->
            <div class="lg:col-span-8 glass-card p-lg rounded-xl shadow-sm">
                <div class="flex items-center justify-between mb-lg">
                    <h4 class="font-headline-md text-headline-md text-on-surface">Dòng tiền 6 tháng</h4>
                    <div class="flex items-center gap-md text-[12px]">
                        <span class="flex items-center gap-xs"><span class="w-3 h-3 rounded-sm bg-primary"></span>Thu/Nạp</span>
                        <span class="flex items-center gap-xs"><span class="w-3 h-3 rounded-sm bg-error"></span>Chi</span>
                    </div>
                </div>
                <div class="h-64 flex items-end justify-between gap-md px-sm">
                    @foreach ($cashflow as $m)
                        <div class="flex-1 flex flex-col items-center gap-sm h-full justify-end">
                            <div class="w-full flex items-end justify-center gap-1 h-full">
                                <div class="w-1/2 bg-primary rounded-t chart-bar" style="height: {{ max((int) round($m['income'] / $cashflowMax * 100), 2) }}%;" title="Thu: {{ number_format($m['income'], 0, ',', '.') }} ₫"></div>
                                <div class="w-1/2 bg-error rounded-t chart-bar" style="height: {{ max((int) round($m['expense'] / $cashflowMax * 100), 2) }}%;" title="Chi: {{ number_format($m['expense'], 0, ',', '.') }} ₫"></div>
                            </div>
                            <span class="text-label-md text-on-surface-variant">{{ $m['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Công nợ -->
            <div class="lg:col-span-4 space-y-xl">
                <div class="grid grid-cols-2 gap-md">
                    <div class="glass-card p-md rounded-xl shadow-sm">
                        <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Phải thu</p>
                        <h3 class="font-headline-md text-headline-md text-green-700">{{ number_format($receivableOutstanding, 0, ',', '.') }} ₫</h3>
                    </div>
                    <div class="glass-card p-md rounded-xl shadow-sm">
                        <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Phải trả</p>
                        <h3 class="font-headline-md text-headline-md text-error">{{ number_format($payableOutstanding, 0, ',', '.') }} ₫</h3>
                    </div>
                </div>

                <div class="glass-card p-lg rounded-xl shadow-sm">
                    <div class="flex items-center justify-between mb-lg">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Công nợ sắp đến hạn</h4>
                        <a href="{{ route('finance.debts.index') }}" class="text-primary text-label-md hover:underline">Xem tất cả</a>
                    </div>
                    <div class="space-y-md">
                        @forelse ($upcomingDebts as $debt)
                            <div class="flex items-center gap-md">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $debt->type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-error-container text-on-error-container' }}">
                                    <span class="material-symbols-outlined text-[20px]">{{ $debt->type === 'receivable' ? 'call_received' : 'call_made' }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-body-md text-body-md font-bold truncate">{{ $debt->partner_name }}</p>
                                    <p class="text-[12px] {{ $debt->is_overdue ? 'text-error font-bold' : 'text-on-surface-variant' }}">
                                        {{ $debt->type_label }} • {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : 'Không hạn' }}
                                        @if ($debt->is_overdue) (quá hạn) @endif
                                    </p>
                                </div>
                                <span class="font-body-md text-body-md font-bold">{{ number_format($debt->remaining_amount, 0, ',', '.') }} ₫</span>
                            </div>
                        @empty
                            <p class="text-body-md text-on-surface-variant">Không có công nợ đang mở.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.chart-bar').forEach(bar => {
            const h = bar.style.height;
            bar.style.height = '0';
            setTimeout(() => { bar.style.height = h; }, 100);
        });
    });
</script>
@endpush
