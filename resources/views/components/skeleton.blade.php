@props([
    'rows'   => 3,
    'avatar' => false,
    'type'   => 'default',
])

<style>
@keyframes ch-shimmer {
    0%   { background-position: -800px 0; }
    100% { background-position:  800px 0; }
}
.sk {
    background: linear-gradient(90deg,
        var(--hairline) 0%,
        var(--canvas-2, #FAF8F2) 40%,
        var(--hairline) 80%
    );
    background-size: 1200px 100%;
    animation: ch-shimmer 1.6s ease-in-out infinite;
    border-radius: var(--r-sm);
    display: block;
}
</style>

@if($type === 'stat-card')
    {{-- Stat card skeleton --}}
    <div {{ $attributes->merge(['class' => 'card']) }} style="padding:18px;display:grid;gap:10px;position:relative;">
        <span class="sk" style="height:11px;width:55%;"></span>
        <span class="sk" style="height:28px;width:35%;margin-top:4px;border-radius:var(--r-md);"></span>
        <span class="sk" style="height:10px;width:40%;"></span>
        <span class="sk" style="width:32px;height:32px;border-radius:var(--r-sm);position:absolute;top:16px;right:16px;"></span>
    </div>

@elseif($type === 'trip-card')
    {{-- Explore trip card grid skeleton --}}
    <div {{ $attributes->merge(['class' => 'card']) }} style="padding:16px;display:grid;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="sk" style="width:38px;height:38px;border-radius:999px;flex-shrink:0;"></span>
            <div style="flex:1;display:grid;gap:6px;">
                <span class="sk" style="height:12px;width:55%;"></span>
                <span class="sk" style="height:10px;width:35%;"></span>
            </div>
            <span class="sk" style="width:56px;height:22px;border-radius:var(--r-pill);"></span>
        </div>
        <div style="display:grid;gap:6px;padding:8px 0;">
            <span class="sk" style="height:11px;width:85%;"></span>
            <span class="sk" style="height:11px;width:70%;"></span>
        </div>
        <div style="display:flex;gap:8px;">
            <span class="sk" style="height:26px;flex:1;border-radius:var(--r-pill);"></span>
            <span class="sk" style="height:26px;flex:1;border-radius:var(--r-pill);"></span>
            <span class="sk" style="height:26px;flex:1;border-radius:var(--r-pill);"></span>
        </div>
    </div>

@elseif($type === 'notification')
    {{-- Notification row skeleton --}}
    <div {{ $attributes->merge([]) }} style="display:grid;gap:0;">
        @for($i = 0; $i < $rows; $i++)
            <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid var(--hairline);">
                <span class="sk" style="width:34px;height:34px;border-radius:999px;flex-shrink:0;margin-top:2px;"></span>
                <div style="flex:1;display:grid;gap:7px;padding-top:4px;">
                    <span class="sk" style="height:11px;width:{{ 55 + ($i * 11 % 30) }}%;"></span>
                    <span class="sk" style="height:10px;width:{{ 30 + ($i * 7 % 20) }}%;"></span>
                </div>
            </div>
        @endfor
    </div>

@elseif($type === 'typing')
    {{-- AI chat typing bubble --}}
    <div {{ $attributes->merge([]) }} style="display:flex;align-items:flex-end;gap:8px;padding:4px 0;">
        <span style="width:28px;height:28px;border-radius:999px;background:var(--surface-2);border:1px solid var(--hairline);display:grid;place-items:center;flex-shrink:0;">
            <i class="fa-solid fa-robot" style="font-size:12px;color:var(--muted);"></i>
        </span>
        <div style="background:var(--surface);border:1px solid var(--hairline);border-radius:18px 18px 18px 4px;padding:12px 16px;display:flex;align-items:center;gap:5px;box-shadow:var(--shadow-1);">
            <span style="width:7px;height:7px;border-radius:999px;background:var(--muted-2);animation:ch-typing-dot 1.2s ease-in-out 0s infinite;display:block;"></span>
            <span style="width:7px;height:7px;border-radius:999px;background:var(--muted-2);animation:ch-typing-dot 1.2s ease-in-out 0.2s infinite;display:block;"></span>
            <span style="width:7px;height:7px;border-radius:999px;background:var(--muted-2);animation:ch-typing-dot 1.2s ease-in-out 0.4s infinite;display:block;"></span>
        </div>
    </div>
    <style>
    @keyframes ch-typing-dot {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30%            { transform: translateY(-5px); opacity: 1; }
    }
    </style>

@else
    {{-- Default: trip row list --}}
    <div {{ $attributes->merge(['class' => 'card']) }}>
        @for($i = 0; $i < $rows; $i++)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:{{ $i < $rows - 1 ? '1px solid var(--hairline)' : 'none' }};">
                @if($avatar)
                    <span class="sk" style="width:36px;height:36px;border-radius:999px;flex-shrink:0;"></span>
                @endif
                <div style="flex:1;display:grid;gap:8px;">
                    <span class="sk" style="height:13px;width:{{ 58 + ($i * 13 % 28) }}%;"></span>
                    <span class="sk" style="height:11px;width:{{ 32 + ($i * 9 % 22) }}%;"></span>
                </div>
                <span class="sk" style="height:24px;width:60px;border-radius:var(--r-pill);flex-shrink:0;"></span>
            </div>
        @endfor
    </div>
@endif
