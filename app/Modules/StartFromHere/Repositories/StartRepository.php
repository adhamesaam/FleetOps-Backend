<?php

/**
 * @file: StartRepository.php
 * @description: نموذج مكتمل للـ Repository - StartFromHere Reference Module
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 الـ Repository - ما هو ولماذا؟
 * ══════════════════════════════════════════════════════════════════════════════
 * الـ Repository هو الطبقة المسؤولة عن التعامل مع قاعدة البيانات فقط.
 * لا يحتوي على Business Logic - ذلك دور الـ Service.
 *
 * الـ BaseRepository يوفر:
 *   - findById($id)           ← جلب سجل بالـ ID (أو null)
 *   - findByIdOrFail($id)     ← جلب سجل بالـ ID (أو Exception)
 *   - all()                   ← جلب كل السجلات
 *   - paginate($perPage)      ← جلب سجلات بـ Pagination
 *   - create($data)           ← إنشاء سجل
 *   - update($id, $data)      ← تحديث سجل
 *   - delete($id)             ← حذف سجل (Soft Delete إذا موجود)
 *   - advancedFilter($filters)← بحث متقدم بفلاتر متعددة
 * 
 * المبدأ:
 *   ✅ يقبل Model في الـ Constructor
 *   ✅ يوفر دوال (Methods) تنفذ استعلامات محددة (find, create, delete, إلخ)
 * 
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [الـ Service] -> يطلب بيانات أو يرسل بيانات للحفظ ويستدعي الـ Repository.
 * 2. [الـ Repository] -> يستخدم الـ Model (كـ Eloquent) لتكوين الجملة الاستعلامية (SQL Query).
 * 3. [قاعدة البيانات] -> تُنفذ الطلب وتُعيد النتيجة.
 * 4. [الـ Repository] -> يُغلف النتيجة كـ Collection أو Model Instance ويعيدها للـ Service دون أن يكترث بالـ Business Logic.
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Modules\StartFromHere\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\StartFromHere\Models\Start;
use Illuminate\Database\Eloquent\Collection;

class StartRepository extends BaseRepository
{
    /**
     * Constructor - تمرير الـ Model للـ BaseRepository
     * الـ Model يتم Inject تلقائياً عبر الـ ModuleServiceProvider
     */
    public function __construct(Start $model)
    {
        parent::__construct($model);
    }

    // ─── Custom Methods (ما يحتاجه هذا الـ Repository بالتحديد) ───────────────

    /**
     * جلب السجلات النشطة فقط
     * @return Collection
     */
    public function getActiveRecords(): Collection
    {
        return $this->model->active()->orderBy('created_at', 'desc')->get();
    }

    /**
     * البحث في العناوين والأوصاف
     * @param string $keyword
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function searchByKeyword(string $keyword)
    {
        return $this->model
            ->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', "%{$keyword}%")
                      ->orWhere('description', 'LIKE', "%{$keyword}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * تحديث حالة سجل (Active/Inactive)
     * @param int $id
     * @param string $status ('active' | 'inactive')
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->model->where('start_id', $id)->update(['status' => $status]);
    }
}
