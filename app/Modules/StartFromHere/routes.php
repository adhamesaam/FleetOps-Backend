<?php

/**
 * @file: routes.php
 * @description: نموذج مكتمل للـ Routes - StartFromHere Reference Module
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 الـ Routes - قواعد مهمة
 * ══════════════════════════════════════════════════════════════════════════════
 * ✅ الـ Routes المتخصصة (مثل /active, /search) يجب أن تأتي قبل /{id}
 *    وإلا سيعتبر Laravel كلمة "active" هي الـ ID!
 *
 * ✅ استخدم ->where('id', '[0-9]+') للتأكد أن الـ ID أرقام فقط
 *
 * ✅ HTTP Methods:
 *    GET    = جلب بيانات (لا يُغيّر شيء)
 *    POST   = إنشاء جديد
 *    PUT    = تحديث كامل
 *    PATCH  = تحديث جزئي (لحقل واحد أو أكثر)
 *    DELETE = حذف
 * ══════════════════════════════════════════════════════════════════════════════
 */

use Illuminate\Support\Facades\Route;
use App\Modules\StartFromHere\Controllers\StartController;
// use App\Modules\StartFromHere\Middleware\StartDemoMiddleware;
use App\Modules\LoggingAudit\Middlewares\SystemAuditMiddleware;

/*
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [المستخدم/Postman] -> يطلب مسار معين (مثال: GET /api/v1/demo).
 * 2. [routes.php] -> يستلم الطلب ويطابق المسار، ثم يوجهه للملف المطلوب.
 * 3. [Middleware] -> يمر الطلب أولاً عبر StartDemoMiddleware (والبوابات الأخرى كـ auth).
 * 4. [Controller] -> إذا مر بسلام، يصل الطلب إلى الدالة المحددة في StartController.
 */
///Route::prefix('api/v1')->middleware(['auth:sanctum', StartDemoMiddleware::class])->group(function () {
Route::prefix('api/v1')->middleware([SystemAuditMiddleware::class])->group(function () {

    Route::prefix('demo')->group(function () {

        //POST /api/v1/demo/hello
        Route::post('/hello', [StartController::class, 'hello'])
            ->name('demo.hello');



        // ══════════════════════════════════════════════════════
        // ⚠️  SPECIALIZED ROUTES MUST COME BEFORE /{id}  ⚠️
        // ══════════════════════════════════════════════════════

        // GET /api/v1/demo/active
        Route::get('/active', [StartController::class, 'active'])
            ->name('demo.active');

        // GET /api/v1/demo/search?keyword=xxx
        Route::get('/search', [StartController::class, 'search'])
            ->name('demo.search');

        // ══════════════════════════════════════════════════════
        // CRUD Routes
        // ══════════════════════════════════════════════════════

        // GET  /api/v1/demo         (list all with pagination)
        Route::get('/', [StartController::class, 'index'])
            ->name('demo.index');

        // POST /api/v1/demo         (create new)
        Route::post('/', [StartController::class, 'store'])
            ->name('demo.store');

        // GET  /api/v1/demo/{id}    (show single)
        Route::get('/{id}', [StartController::class, 'show'])
            ->name('demo.show')
            ->where('id', '[0-9]+'); // Only numeric IDs

        // PUT  /api/v1/demo/{id}    (update)
        Route::put('/{id}', [StartController::class, 'update'])
            ->name('demo.update')
            ->where('id', '[0-9]+');

        // DELETE /api/v1/demo/{id}  (soft delete)
        Route::delete('/{id}', [StartController::class, 'destroy'])
            ->name('demo.destroy')
            ->where('id', '[0-9]+');

        // ══════════════════════════════════════════════════════
        // Custom Action Routes (nested under /{id})
        // ══════════════════════════════════════════════════════

        // POST /api/v1/demo/{id}/toggle-status   (toggle active/inactive)
        Route::post('/{id}/toggle-status', [StartController::class, 'toggleStatus'])
            ->name('demo.toggle-status')
            ->where('id', '[0-9]+');
    });
});
