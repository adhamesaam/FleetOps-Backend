<?php

/**
 * @file: StartDemoMiddleware.php
 * @description: نموذج مكتمل للـ Middleware - StartFromHere Reference Module
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 الـ Middleware - ما هو ولماذا؟
 * ══════════════════════════════════════════════════════════════════════════════
 * الـ Middleware هو بواب تفتيش يقف قبل وصول الطلب (Request) إلى الـ Controller.
 * يمكنه:
 * 1. فحص الطلب (مثال: هل المستخدم مسجل دخول؟ هل لديه صلاحيات؟)
 * 2. تعديل الطلب (مثال: إضافة بيانات معينة للـ Request)
 * 3. رفض الطلب وإرجاع خطأ قبل أن يصل للكود الأساسي.
 * 
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [المستخدم/Postman] -> يرسل الطلب (Request) إلى مسار معين (Route).
 * 2. [الـ Route] -> يوجه الطلب للـ Middleware أولاً.
 * 3. [StartDemoMiddleware] -> يستلم الـ Request. في هذا المثال، سنقوم فقط 
 *    بطباعة رسالة توضيحية في الـ Log، ونمرر الطلب للطبقة التالية.
 * 4. [الـ Controller] -> يستلم الطلب في حال سمح الـ Middleware بمروره.
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Modules\StartFromHere\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StartDemoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. إجراء قبل وصول الطلب للـ Controller
        Log::info('[StartFromHere Module] تم مرور الطلب عبر الـ Middleware بنجاح!', [
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        // 2. السماح للطلب بالمرور إلى الطبقة التالية (Middleware آخر أو Controller)
        $response = $next($request);

        // 3. إجراء بعد الانتهاء من الـ Controller وقبل إرجاع الرد للمستخدم (اختياري)
        // Log::info('تم تجهيز الرد وجاري إرساله للمستخدم.');

        return $response;
    }
}
