@extends('layouts.app')

@section('title', 'Phiếu lương của tôi')
@section('page-title', 'Phiếu lương của tôi')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-2xl mx-auto">
        <x-page-header title="Phiếu lương của tôi" subtitle="Danh sách phiếu lương đã duyệt/đã chi" />

        @include('payroll._flash')

        @if (! $employee)
            <div class="glass-card rounded-xl p-lg text-center text-on-surface-variant">
                Tài khoản của bạn chưa được gắn với hồ sơ nhân viên nên chưa có phiếu lương.
            </div>
        @elseif ($payslips->isEmpty())
            <div class="glass-card rounded-xl p-lg text-center text-on-surface-variant">
                Chưa có phiếu lương nào được duyệt.
            </div>
        @else
            <div class="space-y-md">
                @foreach ($payslips as $slip)
                    <a href="{{ route('payroll.payslips.show', [$slip->period, $slip]) }}" class="glass-card rounded-xl p-lg flex items-center justify-between hover:bg-surface-container-lowest transition-colors">
                        <div>
                            <p class="font-medium text-on-surface">{{ $slip->period->label }}</p>
                            <p class="text-[12px] text-on-surface-variant mt-xs">Ngày công {{ rtrim(rtrim(number_format($slip->paid_days, 1), '0'), '.') }}/{{ $slip->days_in_month }} · {{ $slip->period->status_label }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-headline-md text-headline-md text-primary">{{ number_format($slip->net_amount, 0, ',', '.') }} ₫</p>
                            <p class="text-[12px] text-on-surface-variant">thực nhận</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
