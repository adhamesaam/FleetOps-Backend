<?php

namespace App\Modules\OrderManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProofOfDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => 'required|exists:users,user_id',
            'lat'       => 'required|numeric',
            'lng'       => 'required|numeric',
            'signature' => 'nullable|string',   // base64 encoded signature image
        ];
    }
}
