<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'saved_route_id' => ['required', 'integer', 'exists:saved_routes,id'],
            'trip_datetime' => ['required', 'date'],
            'trip_type' => ['nullable', 'in:one_way,two_way'],
            'outbound_pickup_key' => ['required', 'in:point_a,point_b'],
            'outbound_destination_key' => ['required', 'in:point_a,point_b'],
            'return_pickup_key' => ['nullable', 'in:point_a,point_b'],
            'return_destination_key' => ['nullable', 'in:point_a,point_b'],
            'visibility' => ['required', 'in:private,public'],
            'seat_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_open_for_request' => ['nullable', 'boolean'],
            'public_note' => ['nullable', 'string', 'max:500'],
            'include_driver_in_split' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,scheduled,recorded,cancelled,confirmed,completed'],
            'note' => ['nullable', 'string', 'max:1000'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('visibility') === 'public' && ! $this->filled('seat_limit')) {
                $validator->errors()->add('seat_limit', 'Seat limit is required for public trips.');
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
        });
    }
}
