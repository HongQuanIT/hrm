@props(['title' => '', 'subtitle' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center justify-between gap-md mb-xl']) }}>
    <div>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $title }}</h2>
        @if ($subtitle)
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $subtitle }}</p>
        @endif
    </div>
    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center gap-sm">
            {{ $slot }}
        </div>
    @endif
</div>
