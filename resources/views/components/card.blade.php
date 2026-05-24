@props([
    'pad'   => 'md',
    'quiet' => false,
    'highlight' => false,
])

@php
    $classes = 'card';
    if ($pad === 'sm') $classes .= ' card-pad-sm';
    elseif ($pad === 'lg') $classes .= ' card-pad-lg';
    elseif ($pad !== 'none') $classes .= ' card-pad';
    if ($quiet)     $classes .= ' card-quiet';
    if ($highlight) $classes .= ' card-highlight';
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
