@props([
    'name' => '',
    'size' => 'md',
])

@php
    $dim = match($size) {
        'xs' => '12px',
        'sm' => '14px',
        'lg' => '20px',
        'xl' => '24px',
        default => '16px',
    };
@endphp

<i class="{{ $name }}" {{ $attributes }} style="font-size:{{ $dim }};width:{{ $dim }};text-align:center;flex-shrink:0;{{ $attributes->get('style', '') }}"></i>
