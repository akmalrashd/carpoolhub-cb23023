@props([
    'icon'  => 'fa-solid fa-inbox',
    'title' => 'Nothing here yet',
    'body'  => '',
])

@php
    $baseStyle = 'text-align:center;display:flex;flex-direction:column;align-items:center;gap:12px;padding:40px 24px;';
    $extraStyle = $attributes->get('style', '');
    $mergedStyle = $baseStyle . $extraStyle;
@endphp

<div {{ $attributes->except('style')->merge(['class' => 'card card-pad-lg']) }} style="{{ $mergedStyle }}">
    <span style="width:52px;height:52px;border-radius:var(--r-lg);background:var(--surface-2);border:1px solid var(--hairline);display:grid;place-items:center;color:var(--muted);font-size:22px;">
        <i class="{{ $icon }}"></i>
    </span>
    <div>
        <div style="font-family:var(--font-display);font-weight:700;font-size:16px;color:var(--ink);margin-bottom:4px;">{{ $title }}</div>
        @if($body)
            <div style="font-size:13px;color:var(--muted);max-width:300px;margin:0 auto;">{{ $body }}</div>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">{{ $slot }}</div>
    @endif
</div>
