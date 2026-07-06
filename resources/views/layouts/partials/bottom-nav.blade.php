@php
    $bottomNav = [
        ['label' => 'Tổng quan', 'icon' => 'dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ['label' => 'Nhân viên', 'icon' => 'groups', 'route' => 'employees.index', 'pattern' => 'employees.*'],
        ['label' => 'Chấm công', 'icon' => 'fingerprint', 'route' => 'attendance.index', 'pattern' => 'attendance.*'],
        ['label' => 'Nghỉ phép', 'icon' => 'event_busy', 'route' => 'leaves.index', 'pattern' => 'leaves.*'],
    ];
    if (auth()->check() && auth()->user()->isSuperAdmin()) {
        $bottomNav[] = ['label' => 'Cài đặt', 'icon' => 'settings', 'route' => 'settings.index', 'pattern' => 'settings.*'];
    } else {
        $bottomNav[] = ['label' => 'KPI', 'icon' => 'analytics', 'route' => 'kpis.index', 'pattern' => 'kpis.*'];
    }
@endphp
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-sm pb-safe pt-xs bg-surface border-t border-outline-variant shadow-lg rounded-t-xl">
    @foreach ($bottomNav as $item)
        @php $isActive = request()->routeIs($item['pattern']); @endphp
        <a href="{{ route($item['route']) }}"
           class="flex flex-col items-center justify-center px-4 py-1 rounded-full {{ $isActive ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant' }}">
            <span class="material-symbols-outlined" @if($isActive) style="font-variation-settings: 'FILL' 1;" @endif>{{ $item['icon'] }}</span>
            <span class="font-label-md text-label-md">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
