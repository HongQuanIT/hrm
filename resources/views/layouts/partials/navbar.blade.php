@php $user = auth()->user(); @endphp
<header class="h-16 flex items-center justify-between px-md md:px-xl bg-surface/80 backdrop-blur-md border-b border-outline-variant sticky top-0 z-30">
    <div class="flex items-center gap-md">
        <div class="flex items-center gap-sm md:hidden">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-on-primary text-[20px]" style="font-variation-settings: 'FILL' 1;">bolt</span>
            </div>
            <span class="font-headline-md text-headline-md text-primary">PeoplePulse</span>
        </div>
        <h1 class="hidden md:block font-headline-md text-headline-md text-on-surface">@yield('page-title', View::getSection('title') ?? 'PeoplePulse')</h1>
    </div>
    <div class="flex items-center gap-md">
        <div class="relative group hidden sm:block">
            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
            <input type="text" placeholder="Tìm nhanh..."
                   class="pl-xl pr-md py-xs bg-surface-container-low border-none rounded-full w-56 focus:ring-2 focus:ring-primary/20 text-body-md transition-all">
        </div>
        <button class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-surface-container transition-all relative">
            <span class="material-symbols-outlined text-on-surface-variant">notifications</span>
            <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full border border-surface"></span>
        </button>
        <x-avatar :name="$user?->name ?? 'Admin'" class="w-9 h-9 border border-outline-variant" />
    </div>
</header>
