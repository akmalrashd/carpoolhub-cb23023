{{--
    Canonical avatar: real photo when the account has one, otherwise a
    single initial on a colour picked deterministically from the user's id
    (App\Support\Avatar — see there for why: was ~30 separate ad-hoc
    reimplementations before this, with different initial lengths and
    colour rules per page, and two disagreeing hash functions on the same
    admin page).

    Usage:
      <x-avatar :user="$trip->driver" size="lg" />
      <x-avatar :name="$row->name" :id="$row->user_id" :src="$row->photo_url" />
--}}
@props([
    'user' => null,
    'name' => null,
    'id' => null,
    'src' => null,
    'size' => 'md',
])

@php
    $resolvedName = $name ?? $user?->name ?? '?';
    $resolvedSrc  = $src ?? $user?->profile_photo_url ?? null;
    $resolvedId   = $id ?? $user?->id;

    $initial = \App\Support\Avatar::initial($resolvedName);
    $color   = \App\Support\Avatar::color($resolvedId);

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
@endphp

<span {{ $attributes->merge(['class' => 'cp-avatar']) }}
      style="width:{{ $dim }};height:{{ $dim }};font-size:{{ $fs }};{{ $resolvedSrc ? '' : 'background:'.$color.';color:#fff;' }}">
    @if($resolvedSrc)
        {{-- Some photo sources (e.g. hotlinked Google account avatars) can
             intermittently fail to load client-side even though the URL is
             valid — fall back to the initial avatar instead of a broken image icon. --}}
        <img src="{{ $resolvedSrc }}" alt="{{ $resolvedName }}"
             onerror="var p=this.parentElement;this.remove();p.style.background={{ Js::from($color) }};p.style.color='#fff';p.textContent={{ Js::from($initial) }};">
    @else
        {{ $initial }}
    @endif
</span>
