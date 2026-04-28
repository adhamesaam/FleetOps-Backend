<?php

/**
 * @file: Start.php
 * @description: نموذج Eloquent للموديول التجريبي - نموذج مكتمل للمرجعية
 * @module: StartFromHere
 * @author: Team Leader (Khalid)
 *
 * ══════════════════════════════════════════════════════════════════════════════
 * 📖 موديول المرجعية - StartFromHere
 * ══════════════════════════════════════════════════════════════════════════════
 * هذا الموديول هو نموذج مكتمل يُستخدم كمرجع لأي مطور يريد تنفيذ موديول جديد.
 * كل ملف هنا مكتوب بالكامل بدون TODO لتوضيح الطريقة الصحيحة.
 *
 * هيكل الموديول:
 *   Models/Start.php               ← الـ Model  (أنت هنا)
 *   Repositories/StartRepository.php ← الـ Repository
 *   Services/StartService.php      ← الـ Service
 *   Controllers/StartController.php← الـ Controller
 *   Requests/StartRequest.php      ← الـ Request Validation
 *   routes.php                     ← الـ Routes
 * 
 * 🔄 رحلة البيانات (Data Journey):
 * 1. [الـ Repository] -> يستدعي هذا الـ Model لجلب أو تعديل البيانات.
 * 2. [الـ Model] -> يترجم الأوامر إلى استعلامات SQL ويسألها لقاعدة البيانات (جدول starts).
 * 3. [قاعدة البيانات] -> تُرجع النتيجة للـ Model.
 * 4. [الـ Model] -> يحول النتيجة إلى كائن (Object) ذكي يمكن استخدامه بسهولة ويُعيده للـ Repository.
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Modules\StartFromHere\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Start extends Model
{
    use HasFactory, SoftDeletes;

    // ─── Table Configuration ──────────────────────────────────────────────────

    protected $table     = 'starts';
    protected $primaryKey = 'start_id';
    public $incrementing  = true;
    protected $keyType    = 'int';

    // ─── Mass Assignable Fields ───────────────────────────────────────────────

    protected $fillable = [
        'title',
        'description',
        'status',       // active | inactive
        'created_by',
    ];

    // ─── Hidden Fields (never returned in JSON) ───────────────────────────────

    protected $hidden = ['deleted_at'];

    // ─── Casting (ensure correct types in PHP) ────────────────────────────────

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ─── Default Values ───────────────────────────────────────────────────────

    protected $attributes = [
        'status' => 'active',
    ];

    // ─── Query Scopes ─────────────────────────────────────────────────────────

    /**
     * Filter by active status
     * Usage: Start::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter by inactive status
     * Usage: Start::inactive()->get()
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
