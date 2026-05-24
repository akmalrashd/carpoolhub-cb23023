@extends('layouts.app')

@section('content')
<form action="{{ route('trips.update', $trip) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Page header --}}
    <div style="padding:20px 28px 0">
        <div style="font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.06em;text-transform:uppercase">Edit trip</div>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-top:6px">
            <div>
                <h1 style="margin:0;font-family:var(--font-display);font-size:28px;font-weight:800;color:var(--ink)">Edit trip #{{ $trip->id }}</h1>
                <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Update the schedule, route direction, passengers, or other details.</p>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                <a href="{{ route('trips.index') }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes <i class="fa-solid fa-arrow-right" style="font-size:12px"></i></button>
            </div>
        </div>
    </div>

    @include('trips._form', ['submitLabel' => 'Save changes', 'trip' => $trip, 'selectedParticipants' => $selectedParticipants])
</form>
@endsection
