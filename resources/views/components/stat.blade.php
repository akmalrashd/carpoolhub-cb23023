@props([
    'label' => '',
    'value' => '',
    'delta' => null,
    'icon'  => null,
])

<div {{ $attributes->merge(['class' => 'card card-pad-sm']) }} style="display:flex;flex-direction:column;gap:6px;">
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:12px;font-weight:700;color:var(--muted);letter-spacing:0.04em;text-transform:uppercase;">{{ $label }}</span>
        @if($icon)
            <span style="width:32px;height:32px;border-radius:var(--r-sm);background:var(--ch-yellow-tint);display:grid;place-items:center;color:var(--ch-yellow-ink);font-size:14px;">
                <i class="{{ $icon }}"></i>
            </span>
        @endif
    </div>
    <div style="font-family:var(--font-display);font-size:26px;font-weight:800;color:var(--ink);letter-spacing:-0.02em;line-height:1.1;">
        {{ $value }}
    </div>
    @if($delta !== null)
        <div style="font-size:12px;color:{{ str_starts_with($delta, '-') ? 'var(--danger)' : 'var(--success)' }};">
            {{ $slot->isNotEmpty() ? $slot : $delta }}
        </div>
    @elseif($slot->isNotEmpty())
        <div style="font-size:12px;color:var(--muted);">{{ $slot }}</div>
    @endif
</div>
