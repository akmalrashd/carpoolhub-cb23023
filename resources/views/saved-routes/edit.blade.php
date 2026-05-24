@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
    .sr-form-actions {
        display: flex;
        gap: 8px;
    }
    .sr-mobile-form-actions {
        display: none;
    }

    @media (max-width: 1023px) {
        .sr-desktop-form-actions {
            display: none;
        }

        .sr-mobile-form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 0 28px 28px;
        }

        .sr-form-actions .btn {
            width: 100%;
            min-height: 48px;
            justify-content: center;
        }
    }
</style>

<div style="padding:20px 28px 0">
    <div style="font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.06em;text-transform:uppercase">Saved Routes</div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-top:6px;flex-wrap:wrap">
        <div>
            <h1 style="margin:0;font-family:var(--font-display);font-size:28px;font-weight:800">Edit Saved Route</h1>
            <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Update your route details and map points.</p>
        </div>
        <div class="sr-form-actions sr-desktop-form-actions">
            <a href="{{ route('saved-routes.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" form="route-form" class="btn btn-primary">Update</button>
        </div>
    </div>
</div>

<form id="route-form" method="POST" action="{{ route('saved-routes.update', $savedRoute->id) }}">
    @csrf
    @method('PUT')
    @include('saved-routes._form')
    <div class="sr-form-actions sr-mobile-form-actions">
        <a href="{{ route('saved-routes.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Update</button>
    </div>
</form>
@endsection
