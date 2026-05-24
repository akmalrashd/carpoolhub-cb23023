@extends('layouts.app')

@section('content')
<form id="tripCreateForm" action="{{ route('trips.store') }}" method="POST">
    @csrf

    {{-- Page header --}}
    <div style="padding:20px 28px 0">
        <div style="font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.06em;text-transform:uppercase">New trip</div>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-top:6px">
            <div>
                <h1 style="margin:0;font-family:var(--font-display);font-size:28px;font-weight:800;color:var(--ink)">Create a trip</h1>
                <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Pick a Saved Route to autofill, or build from scratch.</p>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                <a href="{{ route('trips.index') }}" class="btn btn-ghost trip-create-cancel-top">Cancel</a>
                <button type="submit" class="btn btn-primary trip-publish-btn" data-trip-publish-button>Publish trip <i class="fa-solid fa-arrow-right" style="font-size:12px"></i></button>
            </div>
        </div>
    </div>

    @include('trips._form', ['submitLabel' => 'Publish trip', 'trip' => null, 'selectedParticipants' => []])
</form>

<script>
    (() => {
        window.addEventListener('pageshow', (event) => {
            const nav = performance.getEntriesByType('navigation')[0];
            const isBackForward = event.persisted || (nav && nav.type === 'back_forward');
            if (isBackForward) {
                window.location.reload();
            }
        });
    })();
</script>
@endsection
