<?php

/**
 * @file: UserRequest.php
 * @description: التحقق من بيانات المستخدم (إنشاء/تحديث) - Auth & Identity Service
 * @module: AuthIdentity
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\AuthIdentity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? null;
        $passwordRule = $userId ? 'nullable' : 'required';

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $userId . ',user_id',
            'phone' => 'nullable|string|max:20',
            'password' => $passwordRule . '|string|min:8|confirmed',
            'role' => 'required|in:Customer,Mechanic,Dispatcher,Driver,FleetManager',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $userId . ',user_id',
            'license_type' => 'nullable|in:light,heavy,motorcycle',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المستخدم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة عند الإنشاء',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'role.required' => 'دور المستخدم مطلوب',
            'role.in' => 'دور المستخدم يجب أن يكون: driver, dispatcher, fleet_manager, mechanic',
            'employee_id.unique' => 'رقم الموظف مستخدم بالفعل',
            'license_type.in' => 'نوع الرخصة يجب أن يكون: light, heavy, motorcycle',
        ];
    }
}
