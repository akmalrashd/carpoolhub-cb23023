@props([
    'icon'      => 'fa-solid fa-compass',
    'title'     => 'No items found',
    'body'      => '',
    'action'    => '',
    'actionUrl' => '',
    'actionId'  => '',
])

@php
    $baseStyle = 'text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;padding:48px 24px;width:100%;box-sizing:border-box;';
    $extraStyle = $attributes->get('style', '');
    $mergedStyle = $baseStyle . $extraStyle;
@endphp

<div {{ $attributes->except('style')->merge(['class' => 'ch-empty-state-card']) }} style="{{ $mergedStyle }}">
    <div class="ch-empty-state-icon-box">
        <i class="{{ $icon }}"></i>
    </div>
    <h3 class="ch-empty-state-title">{{ $title }}</h3>
    @if($body)
        <p class="ch-empty-state-body">{{ $body }}</p>
    @endif
    @if($actionUrl)
        <a href="{{ $actionUrl }}" class="ch-empty-state-btn">{{ $action }}</a>
    @elseif($action)
        <button type="button" @if($actionId) id="{{ $actionId }}" @endif class="ch-empty-state-btn">{{ $action }}</button>
    @endif
    @if($slot->isNotEmpty())
        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">{{ $slot }}</div>
    @endif
</div>
