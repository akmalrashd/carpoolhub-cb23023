@props([
    'name' => '?',
    'size' => 'md',
    'tone' => 'default',
])

@php
    $initial = strtoupper(substr(trim($name), 0, 1)) ?: '?';
    $dim = match($size) {
        'sm'  => '28px',
        'lg'  => '44px',
        'xl'  => '56px',
        default => '36px',
    };
    $fs = match($size) {
        'sm'  => '11px',
        'lg'  => '17px',
        'xl'  => '22px',
        default => '13px',
    };
    $toneClass = match($tone) {
        'dark' => 'avatar dark',
        'gray' => 'avatar gray',
        default => 'avatar',
    };
@endphp

<span {{ $attributes->merge(['class' => $toneClass]) }}
      style="width:{{ $dim }};height:{{ $dim }};font-size:{{ $fs }};">
    {{ $initial }}
</span>
