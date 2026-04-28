<?php

/**
 * @file: StartRequest.php
 * @description: نموذج مكتمل للـ Request Validation - StartFromHere Reference Module
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 الـ Request - ما هو ولماذا؟
 * ══════════════════════════════════════════════════════════════════════════════
 * الـ FormRequest هو المسؤول عن:
 *   1. التحقق من بيانات الـ Request (Validation)
 *   2. تحديد من له صلاحية تنفيذ هذا الـ Request (Authorization)
 *
 * Laravel يرفض الـ Request تلقائياً *   - يُرجع أخطاء 422 Unprocessable Entity تلقائياً إذا فشلت الـ Rules.
 * 
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [المستخدم/Postman] -> يُرسل بيانات في الـ Body (مثل title, description).
 * 2. [الـ FormRequest] -> يستلم هذه البيانات قبل أن تصل للـ Controller ويعرضها على القواعد (Rules) المعرفة فيه.
 * 3. [في حالة الخطأ] -> يُرجع استجابة آلية (422 Error) تحتوي على الأخطاء ويُوقف المعالجة.
 * 4. [في حالة النجاح] -> يسمح للطلب بالمرور ويضع البيانات المُنقاة في دالة `validated()` ليأخذها الـ Controller.
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Modules\StartFromHere\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartRequest extends FormRequest
{
    /**
     * التحقق من الصلاحية
     * true = أي مستخدم مسجل دخوله يمكنه تنفيذ هذا الـ Request
     * false = لا أحد يمكنه تنفيذه (يُستخدم للاختبار)
     * يمكن إضافة logic مثل: return auth()->user()->hasPermission('create-items')
     */
    public function authorize(): bool
    {
        return true;
        // return auth()->user()->hasPermission('create-items');
    }

    /**
     * قواعد التحقق من البيانات
     * الـ Route Parameters تُستخدم لتفادي الـ unique validation على نفس السجل عند التحديث
     */
    public function rules(): array
    {
        // Get ID from route for unique validation (null on create, int on update)
        $startId = $this->route('id') ?? null;

        return [
            // required       = الحقل مطلوب
            // string         = نص
            // max:255        = أقصى طول 255 حرف
            // unique:table,column,ignore_id,ignore_column
            'title' => 'required|string|max:255|unique:starts,title,' . $startId . ',start_id',

            // nullable       = يمكن أن يكون فارغاً
            // string         = نص
            // max:1000       = أقصى 1000 حرف
            'description' => 'nullable|string|max:1000',

            // nullable       = اختياري
            // in:value1,...  = يجب أن يكون واحداً من هذه القيم
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * رسائل الخطأ المخصصة (بالعربية)
     */
    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'title.unique' => 'هذا العنوان مستخدم بالفعل',
            'title.max' => 'العنوان لا يتجاوز 255 حرفاً',
            'status.in' => 'الحالة يجب أن تكون: active أو inactive',
        ];
    }
}
