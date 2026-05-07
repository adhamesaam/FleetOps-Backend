<?php

namespace App\Modules\ReportingAnalytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateIncidentReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Typically handled by Sanctum/Middleware, so we return true here
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'driver_id'   => 'required|integer|exists:drivers,driver_id',
            'vehicle_id'  => 'required|integer|exists:vehicles,vehicle_id',
            'type'        => 'required|string|max:100',
            'severity'    => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'photo_urls'  => 'nullable|array',
            'incident_ts' => 'nullable|date',
        ];
    }
    
    /**
     * Custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'driver_id.required'   => 'The driver ID is required.',
            'driver_id.exists'     => 'The selected driver does not exist.',
            'vehicle_id.required'  => 'The vehicle ID is required.',
            'vehicle_id.exists'    => 'The selected vehicle does not exist.',
            'severity.in'          => 'The severity must be one of: low, medium, high, critical.',
            'photo_urls.array'     => 'Photo URLs must be provided as an array.',
        ];
    }
}
