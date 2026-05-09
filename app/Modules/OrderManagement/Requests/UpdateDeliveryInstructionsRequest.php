<?php

namespace App\Modules\OrderManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDeliveryInstructionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ring_doorbell' => 'nullable|boolean',
            'leave_at_door' => 'nullable|boolean',
            'safe_place'    => 'nullable|boolean',
            'notes'         => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max'             => 'Delivery notes cannot exceed 500 characters.',
            'ring_doorbell.boolean' => 'Ring doorbell preference must be true or false.',
            'leave_at_door.boolean' => 'Leave at door preference must be true or false.',
            'safe_place.boolean'    => 'Safe place preference must be true or false.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The provided data is invalid.',
            'errors'  => $validator->errors()->toArray(),
            'data'    => [],
        ], 422));
    }
}
