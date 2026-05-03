<?php

/**
 * @file UserRepository.php
 * @description مستودع بيانات المستخدمين — كل عمليات users table
 * @module AuthIdentity
 * @author Team Leader (Khalid)
 */

namespace App\Modules\AuthIdentity\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\AuthIdentity\Models\User;
use App\Modules\AuthIdentity\Models\Driver;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * البحث عن مستخدم بالإيميل
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * جلب المستخدمين النشطين
     */
    public function getActiveUsers(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * جلب السائقين النشطين
     */
    public function getDrivers(): Collection
    {
        return Driver::query()
            ->with('user:user_id,name,email,phone_no')
            ->get();
    }

    /**
     * جلب السائقين حسب الحالة (نشط/غير نشط)
     */
    public function getDriversByStatus(string $status): Collection
    {
        return Driver::query()->where('status', $status)
            ->with('user:user_id,name,email,phone_no')          
            ->get();
    }   

    /**
     * جلب الموزعين
     */
    public function getDispatchers(): Collection
    {
        return $this->model->active()->byRole('Dispatcher')->get();
    }

    /**
     * جلب مديري الأسطول
     */
    public function getFleetManagers(): Collection
    {
        return $this->model->active()->byRole('FleetManager')->get();
    }

    /**
     * جلب الميكانيكيين
     */
    public function getMechanics(): Collection
    {
        return $this->model->active()->byRole('Mechanic')->get();
    }

    /**
     * تحديث عدد محاولات الدخول الفاشلة
     */
    public function updateFailedAttempts(int $userId, int $attempts): bool
    {
        return $this->update($userId, ['failed_login_attempts' => $attempts]);
    }

    /**
     * قفل حساب المستخدم مؤقتاً
     */
    public function lockUser(int $userId, \DateTime $until): bool
    {
        return $this->update($userId, ['locked_until' => $until, 'is_active' => false]);
    }

    /**
     * تحديث آخر تسجيل دخول وإعادة تعيين محاولات الفشل
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login_at'          => now(),
            'failed_login_attempts'  => 0,
        ]);
    }

    /**
     * تغيير حالة المستخدم
     */
    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->update($userId, ['is_active' => $isActive]);
    }
}
