<?php

/**
 * @file: ClusterOrdersRequest.php
 * @description: التحقق من بيانات تجميع الطلبات - Route & Dispatch Service
 * @module: RouteDispatch
 */

namespace App\Modules\RouteDispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClusterOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'distinct', 'exists:order,OrderID'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_ids.required' => 'The order_ids field is required and must be a non-empty array.',
            'order_ids.array' => 'The order_ids field must be an array of integers.',
            'order_ids.min' => 'The order_ids field must contain at least one id.',
            'order_ids.*.integer' => 'Each order id must be an integer.',
            'order_ids.*.distinct' => 'Order ids must be unique.',
            'order_ids.*.exists' => 'One or more order ids do not exist.',
        ];
    }
}
