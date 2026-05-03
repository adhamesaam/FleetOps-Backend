<?php

namespace App\Modules\AuthIdentity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuthIdentity\Services\UserService;
use App\Modules\AuthIdentity\Requests\UserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $filters = $request->only(['search', 'role']);
            $users = $this->userService->getAllUsers($perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            return response()->json(['success' => true, 'data' => $user], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }
    }

    public function store(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if (isset($data['status'])) {
                $data['is_active'] = ($data['status'] === 'active');
                unset($data['status']);
            }
            if (isset($data['phone'])) {
                $data['phone_no'] = $data['phone'];
                unset($data['phone']);
            }

            $user = $this->userService->createUser($data);
            return response()->json(['success' => true, 'message' => 'تم إنشاء المستخدم بنجاح', 'data' => $user], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function update(int $id, UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if (isset($data['status'])) {
                $data['is_active'] = ($data['status'] === 'active');
                unset($data['status']);
            }
            if (isset($data['phone'])) {
                $data['phone_no'] = $data['phone'];
                unset($data['phone']);
            }

            $user = $this->userService->updateUser($id, $data);
            return response()->json(['success' => true, 'message' => 'تم تحديث المستخدم بنجاح', 'data' => $user], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);
            return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح'], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function active(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getActiveUsers()], 200);
    }
    public function drivers(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getDrivers()], 200);
    }
    public function driversByStatus(string $status): JsonResponse
    {
        try {
            $validStatuses = ['Available', 'available', 'OffShift', 'offshift', 'OnShift', 'onshift'];
            if (!in_array($status, $validStatuses)) {
                return response()->json(['success' => false, 'message' => 'Invalid status value'], 400);
            }
            return response()->json(['success' => true, 'data' => $this->userService->getDriversByStatus($status)], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }
    public function dispatchers(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getDispatchers()], 200);
    }
    public function fleetManagers(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getFleetManagers()], 200);
    }
    public function mechanics(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getMechanics()], 200);
    }
}