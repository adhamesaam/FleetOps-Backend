<?php
/**
 * @file SystemAuditMiddleware.php
 * @description Intercepts all non-GET requests and writes a structured audit log entry
 *              to the system_logs table via the SystemLogRepository.
 * @module LoggingAudit
 * @author Team Leader (Khalid)
 */
namespace App\Modules\LoggingAudit\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Modules\LoggingAudit\Repositories\SystemLogRepository; // التعديل هنا: استدعينا الـ Repository
use Symfony\Component\HttpFoundation\Response;

class SystemAuditMiddleware
{
    // التعديل هنا: عرفنا المتغير بتاع الـ Repository
    protected SystemLogRepository $systemLogRepository;

    // التعديل هنا: عملنا Inject للـ Repository جوه الـ Constructor
    public function __construct(SystemLogRepository $systemLogRepository)
    {
        $this->systemLogRepository = $systemLogRepository;
    }

    /**
     * Handle an incoming request.
     * Only logs mutating requests (POST, PUT, PATCH, DELETE).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Record start time before delegating to the next middleware/controller
        $startTime = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        // Skip read-only requests – no audit trail needed
        if ($request->isMethod('GET')) {
            return $response;
        }

        try {
            $statusCode = $response->getStatusCode();
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $level = $statusCode >= 400 ? 'error' : 'info';

            // Resolve or generate a correlation/trace ID for cross-service tracing
            $correlationId = $request->header('X-Correlation-ID')
                ?? $request->header('X-Request-ID')
                ?? (string) Str::uuid();

            // Build sanitised context – strip sensitive fields
            $payload = $request->except([
                'password',
                'password_confirmation',
                'current_password',
                'new_password',
                'token',
                'secret',
            ]);

            $context = [
                'payload' => $payload,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $statusCode,
                'method' => $request->method(),
            ];

            // Resolve the authenticated user's ID (Sanctum token or session)
            $userId = Auth::check() ? Auth::id() : null;

            // التعديل هنا: استخدمنا الـ Repository ودالة writeLog بدل SystemLog::create
            $this->systemLogRepository->writeLog([
                'level' => $level,
                'channel' => 'audit',
                'message' => sprintf(
                    '[AUDIT] %s %s - HTTP %d',
                    $request->method(),
                    $request->getPathInfo(),
                    $statusCode
                ),
                'context' => $context,
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'module' => 'LoggingAudit',
                'request_uri' => $request->getRequestUri(),
                'duration_ms' => $durationMs,
            ]);

        } catch (\Throwable $e) {
            // Never let audit logging break the primary request cycle
            // Silently fail – production systems must not expose internal errors here
            logger()->error('[SystemAuditMiddleware] Failed to write audit log: ' . $e->getMessage());
        }

        return $response;
    }
}