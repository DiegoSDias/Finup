@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-green-500 px-1 py-2 text-sm font-medium text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-green-400'
            : 'inline-flex items-center border-b-2 border-transparent px-1 py-2 text-sm font-medium text-gray-200 transition-colors hover:border-green-500 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-green-400';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
