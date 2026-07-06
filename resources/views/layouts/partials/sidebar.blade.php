@php
    $mainNav = [
        ['label' => 'Tổng quan', 'icon' => 'dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ['label' => 'Nhân viên', 'icon' => 'groups', 'route' => 'employees.index', 'pattern' => 'employees.*'],
        ['label' => 'Chấm công', 'icon' => 'fingerprint', 'route' => 'attendance.index', 'pattern' => 'attendance.*'],
        ['label' => 'Nghỉ phép', 'icon' => 'event_busy', 'route' => 'leaves.index', 'pattern' => 'leaves.*'],
        ['label' => 'KPI', 'icon' => 'analytics', 'route' => 'kpis.index', 'pattern' => 'kpis.*'],
    ];
    $systemNav = [
        ['label' => 'Cài đặt', 'icon' => 'settings', 'route' => 'settings.index', 'pattern' => 'settings.*'],
    ];
    $user = auth()->user();
@endphp
<aside class="hidden md:flex flex-col w-[260px] h-screen bg-surface-container-lowest border-r border-outline-variant fixed left-0 top-0 z-40">
    <div class="px-lg py-xl flex items-center gap-sm">
        <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-on-primary">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">bolt</span>
        </div>
        <span class="font-headline-lg text-headline-lg text-primary tracking-tight">PeoplePulse</span>
    </div>
    <nav class="flex-1 px-md space-y-base overflow-y-auto custom-scrollbar">
        <div class="px-md py-xs text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">Trình điều khiển</div>
        @foreach ($mainNav as $item)
            @php $isActive = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-md px-md py-sm rounded-xl transition-all group {{ $isActive ? 'sidebar-item-active font-semibold' : 'text-on-surface-variant hover:bg-surface-container' }}">
                <span class="material-symbols-outlined {{ $isActive ? '' : 'group-hover:text-primary' }}" @if($isActive) style="font-variation-settings: 'FILL' 1;" @endif>{{ $item['icon'] }}</span>
                <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
            </a>
        @endforeach
        @can('admin')
        <div class="pt-lg px-md py-xs text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">Hệ thống</div>
        @foreach ($systemNav as $item)
            @php $isActive = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-md px-md py-sm rounded-xl transition-all group {{ $isActive ? 'sidebar-item-active font-semibold' : 'text-on-surface-variant hover:bg-surface-container' }}">
                <span class="material-symbols-outlined {{ $isActive ? '' : 'group-hover:text-primary' }}" @if($isActive) style="font-variation-settings: 'FILL' 1;" @endif>{{ $item['icon'] }}</span>
                <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
            </a>
        @endforeach
        @endcan
    </nav>
    <div class="p-md border-t border-outline-variant">
        <form method="POST" action="{{ route('logout') }}" class="mb-sm">
            @csrf
            <button type="submit" class="w-full flex items-center gap-sm px-md py-sm rounded-xl text-on-surface-variant hover:bg-surface-container transition-all">
                <span class="material-symbols-outlined">logout</span>
                <span class="font-body-md text-body-md">Đăng xuất</span>
            </button>
        </form>
        <div class="flex items-center gap-md p-xs rounded-xl bg-surface-container-low">
            <x-avatar :name="$user?->name ?? 'Admin'" class="w-10 h-10 border-2 border-primary-fixed" />
            <div class="flex-1 overflow-hidden">
                <p class="font-body-md text-body-md font-bold truncate">{{ $user?->name ?? 'Admin User' }}</p>
                <p class="text-[12px] text-on-surface-variant truncate">{{ $user?->role_label ?? 'Người dùng' }}</p>
            </div>
        </div>
    </div>
</aside>
