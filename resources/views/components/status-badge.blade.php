@props(['status', 'label' => null])
@php
    $map = [
        // employee
        'active' => 'bg-green-100 text-green-700',
        'on_leave' => 'bg-orange-100 text-orange-700',
        'resigned' => 'bg-red-100 text-red-700',
        // attendance
        'on_time' => 'bg-primary-fixed text-on-primary-fixed-variant',
        'late' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        'absent' => 'bg-error-container text-on-error-container',
        'leave' => 'bg-error-container text-on-error-container',
        'working' => 'bg-secondary-container text-on-secondary-fixed-variant',
        // leave / generic
        'pending' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        'approved' => 'bg-secondary-container text-on-secondary-container',
        'rejected' => 'bg-error-container text-on-error-container',
        'cancelled' => 'bg-surface-container-highest text-on-surface-variant',
        // kpi
        'on_track' => 'bg-green-100 text-green-700',
        'in_progress' => 'bg-orange-100 text-orange-700',
        'behind' => 'bg-red-100 text-red-700',
        'done' => 'bg-green-100 text-green-700',
    ];
    $classes = $map[$status] ?? 'bg-surface-container-highest text-on-surface-variant';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center px-sm py-xs rounded-full font-label-md text-[11px] font-bold $classes"]) }}>
    {{ $label ?? $status }}
</span>
