<?php

/**
 * @file: StartService.php
 * @description: نموذج مكتمل للـ Service - StartFromHere Reference Module
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 الـ Service - ما هو ولماذا؟
 * ══════════════════════════════════════════════════════════════════════════════
 * الـ Service هو المسؤول عن الـ Business Logic.
 * يتلقى البيانات من الـ Controller، ويتعامل مع الـ Repository للبيانات.
 *
 * المبدأ: "Fat Service, Thin Controller"
 *   ✅ Controller  ← يستقبل الـ Request ويُرجع Response فقط
 *   ✅ Service     ← ينفذ Business Logic
 *   - يمكن استخدامه من أي مكان (Controller, Console Command, Job)
 * 
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [الـ Controller] -> يطلب من الـ Service تنفيذ مهمة محددة ويمرر له البيانات (مثل إنشاء سجل).
 * 2. [الـ Service] -> يقوم بتطبيق أي قواعد عمل (Business Rules) مثل التحقق من الأرصدة أو تغيير حالة شيء معين.
 * 3. [الـ Repository] -> يستدعيه الـ Service لحفظ أو جلب البيانات من قاعدة البيانات بعد الانتهاء من المعالجة.
 * 4. [الـ Service] -> يُعيد النتيجة النهائية كبيانات جاهزة للـ Controller لعرضها.
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Modules\StartFromHere\Services;

use App\Modules\StartFromHere\Repositories\StartRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class StartService
{
    protected StartRepository $startRepository;

    /**
     * Constructor - الـ DI يحقن الـ StartRepository تلقائياً
     * يجب تسجيل StartRepository كـ Singleton في ModuleServiceProvider
     */
    public function __construct(StartRepository $startRepository)
    {
        $this->startRepository = $startRepository;
    }

    // ─── READ Operations ──────────────────────────────────────────────────────

    /**
     * جلب كل السجلات مع Pagination
     * @param int $perPage  عدد السجلات في الصفحة
     * @return LengthAwarePaginator
     */
    public function getAllRecords(int $perPage = 15): LengthAwarePaginator
    {
        return $this->startRepository->paginate($perPage);
    }

    /**
     * جلب سجل واحد بالـ ID
     * @param int $id
     * @return \App\Modules\StartFromHere\Models\Start
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  إذا لم يُوجد السجل
     */
    public function getRecordById(int $id)
    {
        // findByIdOrFail يُرجع 404 تلقائياً إذا لم يُوجد السجل
        return $this->startRepository->findByIdOrFail($id);
    }

    /**
     * جلب السجلات النشطة فقط
     */
    public function getActiveRecords()
    {
        return $this->startRepository->getActiveRecords();
    }

    /**
     * البحث في السجلات
     * @param string $keyword
     */
    public function search(string $keyword)
    {
        return $this->startRepository->searchByKeyword($keyword);
    }

    // ─── WRITE Operations ─────────────────────────────────────────────────────

    /**
     * إنشاء سجل جديد
     * @param array $data  (validated data from StartRequest)
     * @return \App\Modules\StartFromHere\Models\Start
     * @throws Exception
     */
    public function createRecord(array $data)
    {
        // إضافة ID المستخدم الحالي
        $data['created_by'] = auth()->id();

        return $this->startRepository->create($data);
    }

    /**
     * تحديث سجل
     * @param int $id
     * @param array $data  (validated data from StartRequest)
     * @return \App\Modules\StartFromHere\Models\Start
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws Exception
     */
    public function updateRecord(int $id, array $data)
    {
        // التحقق من وجود السجل أولاً (يُرجع Exception إذا لم يُوجد)
        $this->startRepository->findByIdOrFail($id);

        // التحديث
        $this->startRepository->update($id, $data);

        // إرجاع السجل المحدث
        return $this->startRepository->findByIdOrFail($id);
    }

    /**
     * حذف سجل (Soft Delete)
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteRecord(int $id): bool
    {
        // التحقق من وجود السجل قبل الحذف
        $this->startRepository->findByIdOrFail($id);

        return $this->startRepository->delete($id);
    }

    /**
     * تغيير حالة سجل (تفعيل/إلغاء تفعيل)
     * @param int $id
     * @return \App\Modules\StartFromHere\Models\Start
     * @throws Exception
     */
    public function toggleStatus(int $id)
    {
        $record = $this->startRepository->findByIdOrFail($id);

        $newStatus = $record->isActive() ? 'inactive' : 'active';
        $this->startRepository->updateStatus($id, $newStatus);

        return $this->startRepository->findByIdOrFail($id);
    }
}
