<?php

namespace App\Modules\AuthIdentity\Services;

use App\Modules\AuthIdentity\Repositories\UserRepository;
use App\Modules\LoggingAudit\Services\LogService;
use App\Modules\LoggingAudit\Services\AuditService;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * جلب جميع المستخدمين مع Pagination وفلاتر البحث
     */
    public function getAllUsers(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->userRepository->getModel()->newQuery();

        // 1. فلترة حسب الدور (Role)
        if (!empty($filters['role']) && strtolower($filters['role']) !== 'all') {
            $query->where('role', $filters['role']);
        }

        // 2. بحث نصي (Search)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
                // تم إزالة البحث بـ user_id لمنع خطأ تحويل النص لرقم في SQL Server
            });
        }

        // التعديل الأهم: SQL Server يطلب إضافة orderBy لعمل الـ Pagination
        return $query->orderBy('user_id', 'desc')->paginate($perPage);
    }

    public function getUserById(int $id)
    {
        return $this->userRepository->findByIdOrFail($id);
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->create($data);

        $this->auditService->log(
            'created',
            'user',
            $user->user_id,
            null,
            $data,
            null,
            'AuthIdentity'
        );
        $this->logService->info('[USER] User created', ['user_id' => $user->user_id], 'AuthIdentity');

        $payload = [
            'title' => 'Welcome to FleetOps',
            'message' => 'Your account has been created with role: ' . $user->role
        ];

        // إرسال الإشعار
        $this->notificationService->send($user->user_id, 'status_update', $payload);

        return $user;
    }

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
            $before,
            $after,
            null,
            'AuthIdentity'
        );

        return $after;
    }

    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findByIdOrFail($id);
        $before = $user->toArray();

        $user->tokens()->delete();

        $result = $this->userRepository->delete($id);

        $this->auditService->log(
            'deleted',
            'user',
            $id,
            $before,
            null,
            null,
            'AuthIdentity'
        );
        $this->logService->warning('[USER] User deleted', ['user_id' => $id], 'AuthIdentity');

        return $result;
    }

    public function getActiveUsers()
    {
        return $this->userRepository->getActiveUsers();
    }
    public function getDrivers()
    {
        return $this->userRepository->getDrivers();
    }
    public function getDriversByStatus(string $status)
    {
        return $this->userRepository->getDriversByStatus($status);
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