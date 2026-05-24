@props([
    'variant' => 'primary',
    'size'    => 'md',
    'icon'    => null,
    'href'    => null,
    'type'    => 'button',
    'block'   => false,
])

@php
    $classes = 'btn btn-' . $variant;
    if ($size === 'sm') $classes .= ' btn-sm';
    if ($size === 'lg') $classes .= ' btn-lg';
    if ($block)         $classes .= ' btn-block';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<i class="{{ $icon }}"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<i class="{{ $icon }}"></i>@endif
        {{ $slot }}
    </button>
@endif
