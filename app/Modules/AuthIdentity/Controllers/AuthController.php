<?php

/**
 * @file AuthController.php
 * @description متحكم المصادقة — login/logout/refresh/password مع Sanctum + Audit Logging
 * @module AuthIdentity
 * @author Team Leader (Khalid)
 */

namespace App\Modules\AuthIdentity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuthIdentity\Services\AuthService;
use App\Modules\AuthIdentity\Requests\LoginRequest;
use App\Modules\AuthIdentity\Requests\PasswordResetRequest;
use App\Modules\LoggingAudit\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected LogService $logService;

    public function __construct(AuthService $authService, LogService $logService)
    {
        $this->authService = $authService;
        $this->logService = $logService;
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Session Fixation Protection — regenerate before confirming identity
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            // استدعاء خدمة المصادقة لإنشاء التوكن
            $data = $this->authService->login($request->email, $request->password);

            // 🔴 التعديل هنا: التحقق من حالة المستخدم (is_active)
            if (!$data['user']->is_active) {
                // مسح أي توكن تم إنشاؤه للتو لزيادة الأمان وطرد المستخدم من كل الأجهزة
                if (method_exists($data['user'], 'tokens')) {
                    $data['user']->tokens()->delete();
                }

                // تسجيل المحاولة في سجلات الأمان
                $this->logService->logSecurity('inactive_user_login_attempt', [
                    'email' => $request->email,
                    'user_id' => $data['user']->user_id ?? null,
                    'ip' => $request->ip(),
                ], 'AuthIdentity');

                return response()->json([
                    'success' => false,
                    'message' => 'عفواً، تم إيقاف حسابك. يرجى التواصل مع الإدارة.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'token' => $data['token'],
                    'token_type' => 'Bearer',
                    'user' => [
                        'user_id' => $data['user']->user_id,
                        'name' => $data['user']->name,
                        'email' => $data['user']->email,
                        'role' => $data['user']->role,
                        'is_active' => $data['user']->is_active,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            $this->logService->logSecurity('failed_login_attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ], 'AuthIdentity');

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $this->authService->logoutAll($request->user());

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج من جميع الأجهزة',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $data = $this->authService->refreshToken($request->user());

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(PasswordResetRequest $request): JsonResponse
    {
        try {
            $this->authService->requestPasswordReset($request->email);

            return response()->json([
                'success' => true,
                'message' => 'إذا كان البريد مسجلاً، ستصلك رسالة إعادة التعيين.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->token,
                $request->email,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/v1/auth/change-password
     */
    public function changePassword(PasswordResetRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword(
                $request->user()->user_id,
                $request->current_password,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير كلمة المرور. يرجى تسجيل الدخول مجدداً.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_no' => $user->phone_no,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }
}
