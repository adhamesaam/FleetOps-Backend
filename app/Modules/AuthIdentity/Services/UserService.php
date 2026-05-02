<?php

/**
 * @file UserService.php
 * @description خدمة إدارة المستخدمين — CRUD كامل مع Logging/Audit
 * @module AuthIdentity
 * @author Team Leader (Khalid)
 */

namespace App\Modules\AuthIdentity\Services;

use App\Modules\AuthIdentity\Repositories\UserRepository;
use App\Modules\LoggingAudit\Services\LogService;
use App\Modules\LoggingAudit\Services\AuditService;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class UserService
{
    protected UserRepository $userRepository;
    protected LogService $logService;
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    public function __construct(
        UserRepository $userRepository,
        LogService $logService,
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->userRepository = $userRepository;
        $this->logService = $logService;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;

    }

    /**
     * جلب جميع المستخدمين مع Pagination
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    /**
     * جلب مستخدم واحد
     */
    public function getUserById(int $id)
    {
        return $this->userRepository->findByIdOrFail($id);
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->create($data);

        $this->auditService->log(
            'created',
            'user',
            $user->user_id,
            afterState: $data,
            module: 'AuthIdentity'
        );
        $this->logService->info('[USER] User created', ['user_id' => $user->user_id], 'AuthIdentity');
        $payload = [
            'title' => 'Welcome to FleetOps',
            'message' => 'Your account has been created with role: ' . $user->role
        ];

        $this->notificationService->send($user->user_id, 'status_update', $payload);

        return $user;
    }

    /**
     * تحديث بيانات مستخدم
     */
    public function updateUser(int $id, array $data)
    {
        $before = $this->userRepository->findByIdOrFail($id)->toArray();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $this->userRepository->update($id, $data);
        $after = $this->userRepository->findById($id)->toArray();

        $this->auditService->log(
            'updated',
            'user',
            $id,
            beforeState: $before,
            afterState: $after,
            module: 'AuthIdentity'
        );

        return $after;
    }

    /**
     * حذف مستخدم
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findByIdOrFail($id);
        $before = $user->toArray();

        // Revoke all Sanctum tokens before delete
        $user->tokens()->delete();

        $result = $this->userRepository->delete($id);

        $this->auditService->log(
            'deleted',
            'user',
            $id,
            beforeState: $before,
            module: 'AuthIdentity'
        );
        $this->logService->warning('[USER] User deleted', ['user_id' => $id], 'AuthIdentity');

        return $result;
    }

    // ─── Role-based Getters ───────────────────────────────────────────────────

    public function getActiveUsers()
    {
        return $this->userRepository->getActiveUsers();
    }

    public function getDrivers()
    {
        return $this->userRepository->getDrivers();
    }

    public function getDispatchers()
    {
        return $this->userRepository->getDispatchers();
    }

    public function getFleetManagers()
    {
        return $this->userRepository->getFleetManagers();
    }

    public function getMechanics()
    {
        return $this->userRepository->getMechanics();
    }
}
