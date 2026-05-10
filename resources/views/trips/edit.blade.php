@extends('layouts.app')

@section('content')
    <style>
        .trip-edit-page {
            display: grid;
            gap: 12px;
        }

        .trip-edit-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }

        .trip-edit-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 28px;
            line-height: 1.1;
            color: #0f172a;
        }

        .trip-edit-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .trip-edit-card {
                padding: 16px;
            }
        }
    </style>

    <div class="trip-edit-page">
        <section class="trip-edit-card">
            <h1 class="trip-edit-title">Sunting Trip #{{ $trip->id }}</h1>
            <p class="trip-edit-subtitle">Kemaskini jadual, penumpang, atau butiran laluan untuk trip ini.</p>
        </section>

        <section class="trip-edit-card">
            <form action="{{ route('trips.update', $trip) }}" method="POST">
                @method('PUT')
                @include('trips._form', ['submitLabel' => 'Kemaskini Trip', 'trip' => $trip, 'selectedParticipants' => $selectedParticipants])
            </form>
        </section>
    </div>
@endsection
