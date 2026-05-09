<?php

/**
 * @file: FuelInvoiceRequest.php
 * @description: التحقق من بيانات فاتورة الوقود - Reporting & Analytics Service
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FuelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_plate'  => 'required|string|max:20|exists:vehicles,VehicleLicense',
            'fill_date'      => 'required|date|before_or_equal:today',
            'liters_filled'  => 'required|numeric|min:0.1|max:500',
            'total_cost_egp' => 'required|numeric|min:0|max:100000',
            'odometer_km'    => 'required|numeric|min:0|max:9999999',
            'supplier'       => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_plate.required'  => 'رقم لوحة المركبة مطلوب.',
            'vehicle_plate.exists'    => 'رقم اللوحة غير موجود في النظام.',
            'fill_date.required'      => 'تاريخ التعبئة مطلوب.',
            'fill_date.before_or_equal' => 'تاريخ التعبئة لا يمكن أن يكون في المستقبل.',
            'liters_filled.required'  => 'كمية الوقود مطلوبة.',
            'liters_filled.min'       => 'كمية الوقود يجب أن تكون أكبر من 0.',
            'total_cost_egp.required' => 'التكلفة الإجمالية مطلوبة.',
            'odometer_km.required'    => 'قراءة العداد مطلوبة.',
        ];
    }
}
