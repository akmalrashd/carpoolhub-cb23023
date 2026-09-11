{{--
    The header/profile-dropdown avatar — kept as its own tiny component
    (rather than the general <x-avatar>) because the header uses a rounded-
    square shape (.avatar-initial, border-radius ~9-11px) to match the other
    header icon buttons, not the circular shape <x-avatar> renders. Same
    underlying data as everywhere else though: real photo when the account
    has one (this used to never check — every page load showed the initial
    even for users with a photo), otherwise the shared per-account colour.
--}}
@php
    $u = auth()->user();
@endphp
<span {{ $attributes->merge(['class' => 'avatar-initial']) }}
      @unless($u?->profile_photo_url) style="background:{{ $u?->avatar_color ?? 'var(--ink)' }};" @endunless>
    @if($u?->profile_photo_url)
        <img src="{{ $u->profile_photo_url }}" alt="{{ $u->name }}">
    @else
        {{ $u?->avatar_initial ?? 'U' }}
    @endif
</span>
