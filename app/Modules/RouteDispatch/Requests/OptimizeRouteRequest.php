<?php

namespace App\Modules\RouteDispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptimizeRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clusters' => 'required|array|min:1',
            'clusters.*.zone' => 'required|string',
            'clusters.*.orders_ids' => 'required|array|min:1',
            'clusters.*.orders_ids.*' => 'integer|min:1',
            'start_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'clusters.required' => 'clusters array is required',
            'clusters.*.zone.required' => 'each cluster must include a zone',
            'clusters.*.orders_ids.required' => 'each cluster must include orders_ids array',
            'start_date.date' => 'start_date must be a valid date and time',
        ];
    }
}
