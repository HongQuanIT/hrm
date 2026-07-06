@extends('layouts.app')

@section('title', 'Lịch nghỉ phép')
@section('page-title', 'Lịch nghỉ phép')

@php
    $typeColor = [
        'monthly' => ['border' => 'border-green-500', 'bg' => 'bg-green-500/10', 'text' => 'text-green-700'],
        'annual' => ['border' => 'border-primary', 'bg' => 'bg-primary/10', 'text' => 'text-on-primary-fixed-variant'],
        'sick' => ['border' => 'border-tertiary', 'bg' => 'bg-tertiary/10', 'text' => 'text-on-tertiary-fixed-variant'],
        'unpaid' => ['border' => 'border-secondary', 'bg' => 'bg-secondary/10', 'text' => 'text-on-secondary-fixed-variant'],
        'maternity' => ['border' => 'border-error', 'bg' => 'bg-error/10', 'text' => 'text-error'],
        'remote' => ['border' => 'border-secondary', 'bg' => 'bg-secondary/10', 'text' => 'text-on-secondary-fixed-variant'],
    ];
@endphp

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto">
        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
            <div class="flex items-center gap-md">
                <a href="{{ route('leaves.index') }}" class="flex items-center gap-xs text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span class="font-body-md">Danh sách đơn</span>
                </a>
            </div>
            <div class="flex items-center gap-md">
                <div class="flex items-center gap-xs">
                    <a href="{{ route('leaves.calendar', ['month' => $prev->month, 'year' => $prev->year]) }}" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-variant transition-colors text-on-surface-variant">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <span class="font-headline-md min-w-[160px] text-center text-on-surface">Tháng {{ $current->format('m, Y') }}</span>
                    <a href="{{ route('leaves.calendar', ['month' => $next->month, 'year' => $next->year]) }}" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-variant transition-colors text-on-surface-variant">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-lg mb-lg bg-surface-container-low rounded-xl p-md border border-outline-variant/50">
            @foreach (\App\Models\LeaveRequest::TYPE_LABELS as $key => $label)
                <div class="flex items-center gap-xs">
                    <div class="w-3 h-3 rounded-full {{ str_replace('border-', 'bg-', $typeColor[$key]['border']) }}"></div>
                    <span class="font-body-md text-body-md text-on-surface">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <!-- Calendar -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-md md:p-lg overflow-x-auto">
            <div class="min-w-[760px]">
                <div class="grid grid-cols-7 border-b border-outline-variant pb-md">
                    @foreach (['T2','T3','T4','T5','T6','T7','CN'] as $i => $wd)
                        <div class="text-center font-label-md text-on-surface-variant uppercase tracking-widest py-sm {{ $i === 5 ? 'text-primary' : ($i === 6 ? 'text-error' : '') }}">{{ $wd }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7 divide-x divide-y divide-outline-variant border-x border-b border-outline-variant">
                    @foreach ($cells as $cell)
                        @if (is_null($cell))
                            <div class="min-h-[120px] p-sm bg-surface-container-low opacity-40"></div>
                        @else
                            <div class="min-h-[120px] p-sm {{ $cell['is_today'] ? 'bg-primary/5' : '' }}">
                                <span class="text-label-md font-bold {{ $cell['is_today'] ? 'text-primary' : 'text-on-surface' }}">{{ $cell['day'] }}</span>
                                <div class="mt-sm space-y-1">
                                    @foreach ($cell['leaves'] as $leave)
                                        @php $c = $typeColor[$leave->type] ?? $typeColor['annual']; @endphp
                                        <div class="{{ $c['bg'] }} border-l-4 {{ $c['border'] }} {{ $c['text'] }} p-xs rounded-r-md text-[11px] font-medium truncate" title="{{ $leave->employee?->name }} - {{ $leave->type_label }}">
                                            {{ $leave->employee?->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
