@props(['kpi', 'phase', 'status', 'icon', 'label', 'primary' => false, 'muted' => false])
@php
    if ($primary) {
        $btn = 'bg-primary text-on-primary hover:opacity-90 shadow-sm';
    } elseif ($muted) {
        $btn = 'border border-outline-variant text-on-surface-variant hover:bg-surface-container-high';
    } else {
        $btn = 'border border-primary/40 text-primary hover:bg-primary/5';
    }
@endphp
<form method="POST" action="{{ route('kpis.phases.status', [$kpi, $phase]) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" value="{{ $status }}">
    <button type="submit" class="flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold transition-all active:scale-95 {{ $btn }}">
        <span class="material-symbols-outlined text-sm">{{ $icon }}</span> {{ $label }}
    </button>
</form>
