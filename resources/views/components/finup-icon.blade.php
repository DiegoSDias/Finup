@props(['name' => 'plus'])
@php
    $path = match ($name) {
        'edit' => 'm15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15v5Z',
        'trash' => 'M3 6h18M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7m4-7v7',
        'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
        'up' => 'M12 21V3m-7 7 7-7 7 7',
        'down' => 'M12 3v18m-7-7 7 7 7-7',
        'card' => 'M2 5h20v14H2zM2 10h20M6 15h3',
        'alert' => 'm12 3 10 18H2L12 3Zm0 6v5m0 3v.5',
        'data' => 'M4 6c0-5 16-5 16 0s-16 5-16 0Zm0 0v12c0 5 16 5 16 0V6M4 12c0 5 16 5 16 0',
        default => 'M12 4v16M4 12h16',
    };
@endphp
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
