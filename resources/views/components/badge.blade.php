@props([
    'tone' => 'default',
    'dot'  => false,
    'size' => 'md',
])

@php
    $classes = 'badge';
    if ($tone !== 'default') $classes .= ' badge-' . $tone;
    if ($size === 'lg')      $classes .= ' badge-lg';
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)<span class="dot"></span>@endif
    {{ $slot }}
</span>
