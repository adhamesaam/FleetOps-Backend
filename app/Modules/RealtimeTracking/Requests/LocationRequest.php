<?php

/**
 * @file LocationRequest.php
 * @description Form Request for validating incoming driver location updates — RealtimeTracking Module
 * @module RealtimeTracking
 * @author Team Leader (Khalid)
 */

namespace App\Modules\RealtimeTracking\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'driver_id'  => 'required|integer|exists:drivers,driver_id',
            'vehicle_id' => 'required|integer|exists:vehicles,vehicle_id',
            'route_id'   => 'nullable|integer|exists:routes,route_id',
            'lat'        => 'required|numeric|between:-90,90',
            'lng'        => 'required|numeric|between:-180,180',
            'speed_kmh'  => 'nullable|numeric|min:0|max:300',
            'accuracy_m' => 'nullable|numeric|min:0',
            'heading'    => 'nullable|numeric|between:0,360',
            'recorded_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'خط العرض مطلوب',
            'lat.between'  => 'خط العرض يجب أن يكون بين -90 و 90',
            'lng.required' => 'خط الطول مطلوب',
            'lng.between'  => 'خط الطول يجب أن يكون بين -180 و 180',
        ];
    }
}
