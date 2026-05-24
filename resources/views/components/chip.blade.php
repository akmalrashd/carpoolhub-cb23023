@props([
    'active'  => false,
    'yellow'  => false,
    'href'    => null,
])

@php
    $classes = 'chip';
    if ($active) $classes .= ' active';
    if ($yellow) $classes .= ' yellow';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
