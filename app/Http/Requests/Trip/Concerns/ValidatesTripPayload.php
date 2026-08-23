<?php

namespace App\Http\Requests\Trip\Concerns;

/**
 * Shared by StoreTripRequest and UpdateTripRequest, which carried the same
 * field rules and cross-field checks with only trip_type/include_driver_in_split
 * strictness differing — a rule change had to be made in both to actually apply.
 */
trait ValidatesTripPayload
{
    protected function baseTripRules(): array
    {
        return [
            'saved_route_id' => ['required', 'integer', 'exists:saved_routes,id'],
            'trip_datetime' => ['required', 'date'],
            'outbound_pickup_key' => ['required', 'in:point_a,point_b'],
            'outbound_destination_key' => ['required', 'in:point_a,point_b'],
            'return_pickup_key' => ['nullable', 'in:point_a,point_b'],
            'return_destination_key' => ['nullable', 'in:point_a,point_b'],
            'visibility' => ['required', 'in:private,public'],
            'seat_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_open_for_request' => ['nullable', 'boolean'],
            'public_note' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:draft,scheduled,recorded,cancelled,confirmed,completed'],
            'note' => ['nullable', 'string', 'max:1000'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    protected function applySharedTripValidation($validator): void
    {
        if ($this->input('visibility') === 'public' && ! $this->filled('seat_limit')) {
            $validator->errors()->add('seat_limit', 'Seat limit is required for public trips.');
        }

        if ($this->input('visibility') === 'public' && $this->filled('trip_datetime')) {
            try {
                $tripAt = \Illuminate\Support\Carbon::parse((string) $this->input('trip_datetime'), \App\Models\Trip::TIMEZONE);

                // On update, only re-check this once visibility or the date
                // actually changed — otherwise saving an untouched field
                // (e.g. a note) on an already-completed public trip would
                // fail against a past date nobody just chose.
                $existingTrip = $this->route('trip');
                $visibilityChanged = ! $existingTrip || $existingTrip->visibility !== 'public';
                $datetimeChanged = ! $existingTrip
                    || ! $existingTrip->trip_datetime
                    || $existingTrip->trip_datetime->format('Y-m-d H:i') !== $tripAt->format('Y-m-d H:i');

                if (($visibilityChanged || $datetimeChanged) && $tripAt->lessThanOrEqualTo(\App\Models\Trip::now())) {
                    $validator->errors()->add('trip_datetime', 'For public trips, date and time must be later than current time.');
                }
            } catch (\Throwable $exception) {
                // Base date validation rule handles invalid datetime format.
            }
        }

        if ($this->input('outbound_pickup_key') === $this->input('outbound_destination_key')) {
            $validator->errors()->add('outbound_destination_key', 'Pickup and destination must use different saved points.');
        }

        if ($this->input('trip_type') === 'two_way') {
            if (! $this->filled('return_pickup_key') || ! $this->filled('return_destination_key')) {
                $validator->errors()->add('return_pickup_key', 'Return trip pickup and destination are required for two-way trips.');
            } elseif ($this->input('return_pickup_key') === $this->input('return_destination_key')) {
                $validator->errors()->add('return_destination_key', 'Return pickup and destination must use different saved points.');
            }
        }
    }
}
