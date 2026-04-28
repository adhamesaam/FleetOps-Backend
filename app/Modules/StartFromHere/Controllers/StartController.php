<?php

/**
 * @file: StartController.php
 * @description: نموذج مكتمل للـ Controller - StartFromHere Reference Module
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 الـ Controller - ما هو ولماذا؟
 * ══════════════════════════════════════════════════════════════════════════════
 * الـ Controller هو بوابة الـ API - يستقبل الـ Request ويُرجع الـ Response.
 *
 * المبدأ الأساسي: "Thin Controller"
 *   ✅ Controller يعمل:
 *       - استقبال الـ Request المُتحقق منه (FormRequest)
 *       - استدعاء الـ Service
 *       - إرجاع JSON Response بنفس الـ format
 *   ❌ Controller لا يعمل:
 *       - Business Logic
 *       - قواعد بيانات مباشرة
 *       - حسابات أو تحويلات بيانات
 *
 * Response Format الموحد:
 *   Success: ['success' => true,  'message' => '...', 'data' => $data]
 *   Error:   ['success' => false, 'message' => '...']
 * 
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [الـ Route] -> يوجه الطلب (Request) إلى الـ Controller بعد مروره بالـ Middleware.
 * 2. [الـ FormRequest] -> يتم التحقق من صحة البيانات (Validation) قبل دخولها الـ Controller.
 * 3. [الـ Controller] -> يستلم البيانات النظيفة، ويستدعي الـ Service (الـ Business Logic).
 * 4. [الـ Service] -> يقوم بمعالجة البيانات وإعادتها.
 * 5. [الـ Controller] -> يُغلف البيانات الناتجة في Response بصيغة JSON ويُعيدها للمستخدم/Postman.
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Modules\StartFromHere\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\StartFromHere\Services\StartService;
use App\Modules\StartFromHere\Requests\StartRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StartController extends Controller
{
    /**
     * نقوم بـ Inject الـ Service عبر الـ Constructor (Dependency Injection)
     * Laravel يُعطينا Instance منه تلقائياً لأننا سجلناه في ModuleServiceProvider
     */
    protected StartService $startService;

    public function __construct(StartService $startService)
    {
        $this->startService = $startService;
    }

    // ─── GET /api/v1/demo ─────────────────────────────────────────────────────

    /**
     * جلب كل السجلات مع Pagination
     * GET /api/v1/demo?per_page=15
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $records = $this->startService->getAllRecords($perPage);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب السجلات بنجاح',
                'data'    => $records,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب السجلات: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── GET /api/v1/demo/{id} ────────────────────────────────────────────────

    /**
     * جلب سجل واحد
     * GET /api/v1/demo/{id}
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $record = $this->startService->getRecordById($id);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب السجل بنجاح',
                'data'    => $record,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السجل غير موجود',
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── POST /api/v1/demo ────────────────────────────────────────────────────

    /**
     * إنشاء سجل جديد
     * POST /api/v1/demo
     * Body: {title, description?, status?}
     * @param StartRequest $request  ← الـ Validation يحدث تلقائياً هنا
     * @return JsonResponse
     */
    public function store(StartRequest $request): JsonResponse
    {
        try {
            // $request->validated() تُرجع فقط الحقول المُتحقق منها في StartRequest::rules()
            $record = $this->startService->createRecord($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء السجل بنجاح',
                'data'    => $record,
            ], 201); // 201 = Created

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإنشاء: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── PUT /api/v1/demo/{id} ────────────────────────────────────────────────

    /**
     * تحديث سجل
     * PUT /api/v1/demo/{id}
     * @param int $id
     * @param StartRequest $request
     * @return JsonResponse
     */
    public function update(int $id, StartRequest $request): JsonResponse
    {
        try {
            $record = $this->startService->updateRecord($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السجل بنجاح',
                'data'    => $record,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السجل غير موجود',
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── DELETE /api/v1/demo/{id} ─────────────────────────────────────────────

    /**
     * حذف سجل (Soft Delete)
     * DELETE /api/v1/demo/{id}
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->startService->deleteRecord($id);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السجل بنجاح',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السجل غير موجود',
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── GET /api/v1/demo/active ──────────────────────────────────────────────

    /**
     * جلب السجلات النشطة فقط
     * GET /api/v1/demo/active
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        try {
            $records = $this->startService->getActiveRecords();

            return response()->json([
                'success' => true,
                'message' => 'تم جلب السجلات النشطة',
                'data'    => $records,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── GET /api/v1/demo/search?keyword=xxx ──────────────────────────────────

    /**
     * البحث في السجلات
     * GET /api/v1/demo/search?keyword=test
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate(['keyword' => 'required|string|min:2']);

            $results = $this->startService->search($request->keyword);

            return response()->json([
                'success' => true,
                'message' => 'نتائج البحث',
                'data'    => $results,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'الكلمة المفتاحية يجب أن تكون حرفين على الأقل',
                'errors'  => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في البحث: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── POST /api/v1/demo/{id}/toggle-status ────────────────────────────────

    /**
     * تبديل حالة السجل (Active ↔ Inactive)
     * POST /api/v1/demo/{id}/toggle-status
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $record = $this->startService->toggleStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير الحالة إلى: ' . $record->status,
                'data'    => $record,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السجل غير موجود',
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }
}
