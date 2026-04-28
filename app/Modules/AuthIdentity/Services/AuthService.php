<?php

/**
 * @file AuthService.php
 * @description خدمة المصادقة — تسجيل دخول/خروج بـ Sanctum + حماية Brute-force
 * @module AuthIdentity
 * @author Team Leader (Khalid)
 */

namespace App\Modules\AuthIdentity\Services;

use App\Modules\AuthIdentity\Repositories\UserRepository;
use App\Modules\LoggingAudit\Services\LogService;
use App\Modules\LoggingAudit\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Exception;

class AuthService
{
    /** Max failed attempts before locking the account */
    protected const MAX_ATTEMPTS    = 5;

    /** Lock duration in minutes */
    protected const LOCK_MINUTES    = 15;

    protected UserRepository $userRepository;
    protected LogService     $logService;
    protected AuditService   $auditService;

    public function __construct(
        UserRepository $userRepository,
        LogService     $logService,
        AuditService   $auditService
    ) {
        $this->userRepository = $userRepository;
        $this->logService     = $logService;
        $this->auditService   = $auditService;
    }

    /**
     * تسجيل دخول المستخدم — يعيد توكن Sanctum + بيانات المستخدم
     *
     * @return array{token: string, token_type: string, user: \App\Modules\AuthIdentity\Models\User}
     * @throws Exception
     */
    public function login(string $email, string $password): array
    {
        // 1. Find user
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$user->is_active) {
            $this->logService->logSecurity('failed_login', [
                'email'  => $email,
                'reason' => $user ? 'account_inactive' : 'user_not_found',
            ]);
            throw new Exception('بيانات الدخول غير صحيحة');
        }

        // 2. Brute-force protection — check lock
        if (
            isset($user->locked_until) &&
            $user->locked_until !== null &&
            now()->lt($user->locked_until)
        ) {
            $this->logService->logSecurity('account_locked', [
                'user_id'     => $user->user_id,
                'locked_until' => $user->locked_until,
            ]);
            throw new Exception('الحساب مقفل مؤقتاً. يرجى المحاولة بعد ' . self::LOCK_MINUTES . ' دقيقة.');
        }

        // 3. Verify password
        if (!Hash::check($password, $user->password)) {
            $attempts = ($user->failed_login_attempts ?? 0) + 1;
            $this->userRepository->updateFailedAttempts($user->user_id, $attempts);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockUntil = now()->addMinutes(self::LOCK_MINUTES)->toDateTime();
                $this->userRepository->lockUser($user->user_id, $lockUntil);
                $this->logService->logSecurity('account_locked', [
                    'user_id'  => $user->user_id,
                    'attempts' => $attempts,
                ]);
                throw new Exception('تم قفل الحساب بسبب محاولات دخول متكررة. حاول بعد ' . self::LOCK_MINUTES . ' دقيقة.');
            }

            $this->logService->logSecurity('failed_login', [
                'user_id'  => $user->user_id,
                'attempts' => $attempts,
            ]);
            throw new Exception('بيانات الدخول غير صحيحة');
        }

        // 4. Reset failed attempts + update last login
        $this->userRepository->updateLastLogin($user->user_id);

        // 5. Revoke previous tokens (Commented out to allow multiple devices/sessions)
        // $user->tokens()->delete(); // Uncomment this line if you want to enforce single-device login
        $plainToken = $user->createToken('fleet_auth_token')->plainTextToken;

        // 6. Write audit log
        $this->auditService->log(
            action:     'login',
            entityType: 'user',
            entityId:   $user->user_id,
            afterState: ['email' => $user->email, 'role' => $user->role],
            userId:     $user->user_id,
            module:     'AuthIdentity'
        );

        return [
            'token'      => $plainToken,
            'token_type' => 'Bearer',
            'user'       => $user,
        ];
    }

    /**
     * تسجيل خروج الجهاز الحالي — يستقبل User object مباشرة من $request->user()
     */
    public function logout(\App\Modules\AuthIdentity\Models\User $user): bool
    {
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        $this->auditService->log('logout', 'user', $user->user_id, module: 'AuthIdentity');
        $this->logService->info('[AUTH] User logged out', ['user_id' => $user->user_id], 'AuthIdentity');

        return true;
    }

    /**
     * تسجيل خروج من جميع الأجهزة
     */
    public function logoutAll(\App\Modules\AuthIdentity\Models\User $user): bool
    {
        $user->tokens()->delete();

        $this->auditService->log('logout_all', 'user', $user->user_id, module: 'AuthIdentity');
        $this->logService->info('[AUTH] User logged out all devices', ['user_id' => $user->user_id], 'AuthIdentity');

        return true;
    }

    /**
     * تحديث التوكن (rotate)
     */
    public function refreshToken(\App\Modules\AuthIdentity\Models\User $user): array
    {
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
        $newToken = $user->createToken('fleet_auth_token')->plainTextToken;

        $this->logService->info('[AUTH] Token refreshed', ['user_id' => $user->user_id], 'AuthIdentity');

        return [
            'token'      => $newToken,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * طلب إعادة تعيين كلمة المرور — يرسل إيميل بالرابط
     */
    public function requestPasswordReset(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            // Return true to avoid user enumeration attacks
            return true;
        }

        $status = Password::broker()->sendResetLink(['email' => $email]);

        $this->logService->info('[AUTH] Password reset requested', ['email' => $email], 'AuthIdentity');

        return $status === Password::RESET_LINK_SENT;
    }

    /**
     * إعادة تعيين كلمة المرور بالتوكن
     */
    public function resetPassword(string $token, string $email, string $newPassword): bool
    {
        $status = Password::broker()->reset(
            ['email' => $email, 'password' => $newPassword, 'token' => $token],
            function ($user, $password) {
                $user->password = \Illuminate\Support\Facades\Hash::make($password);
                $user->save();
                $user->tokens()->delete(); // Revoke all tokens after reset
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new Exception('رابط إعادة التعيين غير صالح أو منتهي الصلاحية');
        }

        $this->logService->logSecurity('password_reset', ['email' => $email], 'AuthIdentity');

        return true;
    }

    /**
     * تغيير كلمة المرور للمستخدم المسجل
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findByIdOrFail($userId);

        if (!Hash::check($currentPassword, $user->password)) {
            throw new Exception('كلمة المرور الحالية غير صحيحة');
        }

        $this->userRepository->update($userId, [
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all tokens — force re-login on all devices
        $user->tokens()->delete();

        $this->logService->logSecurity('password_changed', ['user_id' => $userId], 'AuthIdentity');

        return true;
    }
}
