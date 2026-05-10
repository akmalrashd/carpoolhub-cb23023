@extends('layouts.app')

@section('content')
    <style>
        .trip-create-page {
            display: grid;
            gap: 12px;
        }

        .trip-create-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }

        .trip-title-card {
            position: relative;
            overflow: hidden;
        }

        .trip-title-card::after {
            content: "";
            position: absolute;
            right: -18px;
            top: -8px;
            width: 128px;
            height: 128px;
            background: no-repeat center/contain url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%230f172a' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 58c8-16 16-10 24-24 7-13 16-13 22-22'/%3E%3Ccircle cx='16' cy='60' r='5'/%3E%3Cpath d='M66 13l5 9h-10z' fill='%230f172a' stroke='none'/%3E%3Cpath d='M34 44l5-5M40 50l5-5'/%3E%3C/g%3E%3C/svg%3E");
            opacity: .08;
            transform: rotate(18deg);
            pointer-events: none;
        }

        .trip-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 28px;
            line-height: 1.1;
            color: #0f172a;
        }

        .trip-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .trip-create-card {
                padding: 16px;
            }
        }
    </style>

    <div class="trip-create-page">
        <section class="trip-create-card trip-title-card">
            <h1 class="trip-title">Cipta Trip</h1>
            <p class="trip-subtitle">Tetapkan laluan, jadual, dan penumpang untuk trip baharu.</p>
        </section>

        <section class="trip-create-card">
            <form action="{{ route('trips.store') }}" method="POST">
                @include('trips._form', ['submitLabel' => 'Cipta Trip', 'trip' => null, 'selectedParticipants' => []])
            </form>
        </section>
    </div>

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
