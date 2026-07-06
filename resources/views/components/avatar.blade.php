@props(['name' => '', 'class' => 'w-10 h-10'])
@php
    $clean = trim($name) !== '' ? trim($name) : 'NA';
    $parts = preg_split('/\s+/', $clean);
    $initials = count($parts) >= 2
        ? mb_substr($parts[count($parts) - 2], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1)
        : mb_substr($clean, 0, 2);
    $initials = mb_strtoupper($initials);
    $palette = [
        ['bg' => '#dbe1ff', 'fg' => '#00174b'],
        ['bg' => '#d0e1fb', 'fg' => '#0b1c30'],
        ['bg' => '#ffdbcd', 'fg' => '#360f00'],
        ['bg' => '#d3e4fe', 'fg' => '#38485d'],
        ['bg' => '#e7e7f3', 'fg' => '#434655'],
    ];
    $color = $palette[abs(crc32($clean)) % count($palette)];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-full overflow-hidden flex items-center justify-center font-bold shrink-0 ' . $class]) }}
     style="background-color: {{ $color['bg'] }}; color: {{ $color['fg'] }};">
    <span class="text-[0.85em] leading-none">{{ $initials }}</span>
</div>
