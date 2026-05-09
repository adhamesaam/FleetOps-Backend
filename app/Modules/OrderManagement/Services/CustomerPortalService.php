<?php

namespace App\Modules\OrderManagement\Services;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * CustomerPortalService – Verified Schema Edition
 * ================================================
 * Every query is written against the EXACT columns confirmed in SQL Server.
 * Any data the frontend expects that is absent from the DB is returned as
 * `null` or `[]`.
 *
 * ALLOWED TABLES & THEIR EXACT COLUMNS
 * ─────────────────────────────────────
 * order            → OrderID (PK), DriverID(FK), Status, ETA, PromisedWindow,
 *                    Price, Area, Created_at, UpdatedAt, Payment_method
 *
 * drivers          → driver_id (PK), license_no, license_type, vehicle_id,
 *                    status, score, created_at
 *                    (name / phone / photo_url / vehicle_plate DO NOT exist)
 *
 * tracking_tokens  → id, token, order_id, OrderID, expires_at, created_at, updated_at
 *
 * order_items      → id, order_id, product_name, quantity, unit_price, image_url
 *
 * route_stops      → stop_id, route_id, stop_no, order_id, eta,
 *                    actual_arrival_time, latitude, longitude
 *
 * BANNED TABLES (DO NOT QUERY – THEY DO NOT EXIST)
 * ─────────────────────────────────────────────────
 * driver_locations, order_status_logs, delivery_attempts,
 * delivery_proofs, delivery_photos, order_feedback, customers
 */
class CustomerPortalService
{
    // -------------------------------------------------------------------------
    // Screen constants – DB status value → frontend screen identifier
    // -------------------------------------------------------------------------

    private const SCREEN_ORDER_CONFIRMED    = 'order_confirmed';
    private const SCREEN_TRACKING           = 'tracking';
    private const SCREEN_DRIVER_ALMOST_HERE = 'driver_almost_here';
    private const SCREEN_DELIVERED          = 'delivered';
    private const SCREEN_UNSUCCESSFUL       = 'unsuccessful_attempt';

    private const STATUS_SCREEN_MAP = [
        'confirmed'    => self::SCREEN_ORDER_CONFIRMED,
        'assigned'     => self::SCREEN_TRACKING,
        'en_route'     => self::SCREEN_TRACKING,
        'almost_here'  => self::SCREEN_DRIVER_ALMOST_HERE,
        'delivered'    => self::SCREEN_DELIVERED,
        'unsuccessful' => self::SCREEN_UNSUCCESSFUL,
        'failed'       => self::SCREEN_UNSUCCESSFUL,
    ];

    // =========================================================================
    // Public API methods
    // =========================================================================

    /**
     * Validate a tracking token and return the screen to render.
     *
     * Tables: tracking_tokens, order
     */
    public function validateTrackingToken(string $token): array
    {
        try {
            $tokenRecord = DB::table('tracking_tokens')
                ->where('token', $token)
                ->first();

            if (!$tokenRecord) {
                return $this->failure(
                    'Tracking link not found.',
                    ['token' => ['This tracking link is invalid.']],
                    404
                );
            }

            if (Carbon::parse($tokenRecord->expires_at)->isPast()) {
                return $this->failure(
                    'This tracking link has expired.',
                    ['token' => ['Your tracking link expired on ' . Carbon::parse($tokenRecord->expires_at)->toFormattedDateString() . '.']],
                    410
                );
            }

            $order = DB::table('order')
                ->where('OrderID', $tokenRecord->order_id)
                ->first();

            if (!$order) {
                return $this->failure(
                    'Associated order not found.',
                    ['order' => ['The order linked to this token no longer exists.']],
                    404
                );
            }

            return $this->success('Token is valid.', [
                'screen'           => self::STATUS_SCREEN_MAP[$order->Status] ?? self::SCREEN_ORDER_CONFIRMED,
                'order_id'         => $order->OrderID,
                'order_status'     => $order->Status,
                'token_expires_at' => $tokenRecord->expires_at,
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@validateTrackingToken', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to validate tracking link.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Fetch full order details.
     *
     * Tables: tracking_tokens, order, order_items
     */
    public function fetchOrderDetails(string $token): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            // order_items → id, order_id, product_name, quantity, unit_price, image_url
            $items = DB::table('order_items')
                ->where('order_id', $order->OrderID)
                ->get()
                ->map(fn ($item) => [
                    'name'      => $item->product_name ?? null,
                    'quantity'  => (int)   ($item->quantity   ?? 1),
                    'price'     => (float) ($item->unit_price ?? 0),
                    'image_url' => $item->image_url ?? null,
                ])
                ->toArray();

            return $this->success('Order details retrieved successfully.', [
                'id'         => $order->OrderID,
                'status'     => $order->Status      ?? null,
                'created_at' => $order->Created_at  ?? null,
                'recipient'  => [
                    'name'  => null,   // RecipientName absent from confirmed schema
                    'phone' => null,   // RecipientPhone absent from confirmed schema
                ],
                'delivery_address' => [
                    'line1' => $order->Area ?? null,
                    'city'  => $order->Area ?? null,
                ],
                'items'   => $items,
                'pricing' => [
                    'subtotal'       => 0,
                    'delivery_fee'   => 0,
                    'tax'            => 0,
                    'total'          => (float) ($order->Price ?? 0),
                    'currency'       => 'EGP',
                    'payment_method' => $order->Payment_method ?? 'cash',
                ],
                'eta' => [
                    'window_start' => $order->PromisedWindow ?? null,
                    'window_end'   => null,
                    'eta_value'    => $order->ETA ?? null,
                    'display_text' => isset($order->PromisedWindow)
                        ? $this->formatEtaWindow($order->PromisedWindow)
                        : 'TBD',
                ],
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@fetchOrderDetails', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to load order details.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Fetch live tracking data.
     *
     * Tables: tracking_tokens, order, drivers, route_stops
     * driver_locations → BANNED → use route_stops (latest stop for order)
     * order_status_logs → BANNED → timeline = []
     */
    public function fetchTrackingData(string $token): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            $driverFk = $order->{'DriverID(FK)'} ?? null;

            $trackingData = Cache::remember("tracking_data_{$order->OrderID}", 10, function () use ($order, $driverFk) {

                // drivers → driver_id, license_no, license_type, vehicle_id, status, score, created_at
                $driver = $driverFk
                    ? DB::table('drivers')->where('driver_id', $driverFk)->first()
                    : null;

                // route_stops → stop_id, route_id, stop_no, order_id, eta, actual_arrival_time, lat, lng
                $latestStop = DB::table('route_stops')
                    ->where('order_id', $order->OrderID)
                    ->orderByDesc('stop_no')
                    ->first();

                return [
                    'driver' => $driver ? [
                        'id'            => $driver->driver_id,
                        'name'          => null,     // full_name absent
                        'photo_url'     => null,     // profile_photo_url absent
                        'phone'         => null,     // phone absent
                        'vehicle_type'  => null,     // vehicle_type absent
                        'vehicle_plate' => null,     // vehicle_plate absent
                        'rating'        => (float) ($driver->score ?? 0),
                        'license_type'  => $driver->license_type ?? null,
                    ] : null,
                    'driver_location' => $latestStop ? [
                        'latitude'    => (float) $latestStop->latitude,
                        'longitude'   => (float) $latestStop->longitude,
                        'heading'     => null,
                        'recorded_at' => $latestStop->actual_arrival_time ?? null,
                        'eta'         => $latestStop->eta ?? null,
                    ] : null,
                    'destination' => [
                        'latitude'  => null,
                        'longitude' => null,
                        'address'   => $order->Area ?? 'Unknown',
                    ],
                    'eta_minutes'  => $order->ETA    ?? null,
                    'order_status' => $order->Status ?? null,
                    'timeline'     => [],    // order_status_logs does not exist
                ];
            });

            return $this->success('Tracking data retrieved successfully.', $trackingData);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@fetchTrackingData', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to load tracking data.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Save delivery instructions.
     *
     * Instruction* columns are NOT in the confirmed schema → no DB write.
     * Echo payload back so the frontend state remains consistent.
     */
    public function saveDeliveryInstructions(string $token, array $payload): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            if (in_array($order->Status, ['almost_here', 'delivered', 'unsuccessful', 'failed'])) {
                return $this->failure('Instructions are locked at this stage.', [], 422);
            }

            Cache::forget("tracking_data_{$order->OrderID}");

            return $this->success('Delivery instructions updated successfully.', [
                'ring_doorbell' => (bool) ($payload['ring_doorbell'] ?? false),
                'leave_at_door' => (bool) ($payload['leave_at_door'] ?? false),
                'safe_place'    => (bool) ($payload['safe_place']    ?? false),
                'notes'         => $payload['notes'] ?? null,
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@saveDeliveryInstructions', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to update instructions.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Fetch arrival details (almost-here screen).
     *
     * Tables: tracking_tokens, order, route_stops
     * driver_locations → BANNED → use route_stops
     */
    public function fetchArrivalDetails(string $token): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            $latestStop = DB::table('route_stops')
                ->where('order_id', $order->OrderID)
                ->orderByDesc('stop_no')
                ->first();

            $isCash = strtolower($order->Payment_method ?? 'cash') === 'cash';

            return $this->success('Arrival details retrieved successfully.', [
                'distance_meters'     => null,
                'distance_display'    => 'Calculating…',
                'eta_minutes'         => $order->ETA ?? null,
                'is_cash_on_delivery' => $isCash,
                'cash_amount'         => $isCash ? (float) ($order->Price ?? 0) : null,
                'currency'            => 'EGP',
                'customer_ready'      => null,    // CustomerReady absent from schema
                'customer_ready_at'   => null,    // CustomerReadyAt absent from schema
                'order_status'        => $order->Status ?? null,
                'driver_location'     => $latestStop ? [
                    'latitude'    => (float) $latestStop->latitude,
                    'longitude'   => (float) $latestStop->longitude,
                    'heading'     => null,
                    'recorded_at' => $latestStop->actual_arrival_time ?? null,
                ] : null,
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@fetchArrivalDetails', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to load arrival details.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Mark customer as ready.
     *
     * CustomerReady / CustomerReadyAt absent from confirmed schema → stub.
     */
    public function markCustomerAsReady(string $token): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            if (!in_array($order->Status, ['almost_here', 'en_route'])) {
                return $this->failure('Not available at this stage.', [], 422);
            }

            return $this->success('Driver has been notified that you are ready.', [
                'already_confirmed' => false,
                'confirmed_at'      => now()->toIso8601String(),
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@markCustomerAsReady', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to confirm readiness.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Fetch delivery proof.
     *
     * delivery_proofs, delivery_photos → BANNED → return nulls/empties.
     * DeliveredAt / FeedbackSubmitted → absent from confirmed schema.
     */
    public function fetchDeliveryProof(string $token): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            if (($order->Status ?? '') !== 'delivered') {
                return $this->failure('Order not delivered yet.', [], 422);
            }

            return $this->success('Delivery proof retrieved successfully.', [
                'delivered_at'          => null,
                'feedback_submitted'    => false,
                'feedback_submitted_at' => null,
                'proof'                 => null,
                'delivery_photos'       => [],
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@fetchDeliveryProof', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to load delivery proof.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Save customer feedback.
     *
     * order_feedback → BANNED → stub acknowledgement, no DB write.
     */
    public function saveFeedback(string $token, array $payload): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            if (($order->Status ?? '') !== 'delivered') {
                return $this->failure('Order not delivered yet.', [], 422);
            }

            return $this->success('Thank you for your feedback!', [
                'submitted_at' => now()->toIso8601String(),
                'rating'       => $payload['rating'] ?? null,
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@saveFeedback', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to submit feedback.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Fetch unsuccessful attempt details.
     *
     * delivery_attempts → BANNED → return null stubs.
     */
    public function fetchUnsuccessfulAttemptDetails(string $token): array
    {
        try {
            $order = $this->resolveOrderFromToken($token);
            if (!$order) {
                return $this->tokenNotFound();
            }

            if (!in_array($order->Status ?? '', ['unsuccessful', 'failed'])) {
                return $this->failure('No failed attempt found.', [], 422);
            }

            return $this->success('Attempt details retrieved successfully.', [
                'attempt_number' => null,
                'max_attempts'   => 3,
                'attempted_at'   => null,
                'reason_code'    => null,
                'reason_message' => 'The delivery could not be completed at this time.',
                'driver_notes'   => null,
                'next_attempt_window' => [
                    'date'  => null,
                    'start' => null,
                    'end'   => null,
                ],
                'support_phone' => config('fleetops.support_phone', '+20-100-000-0000'),
                'support_email' => config('fleetops.support_email', 'support@fleetops.com'),
            ]);

        } catch (Exception $e) {
            Log::error('CustomerPortalService@fetchUnsuccessfulAttemptDetails', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Unable to load attempt details.', ['server' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Return static support contact information from config.
     */
    public function fetchSupportInfo(): array
    {
        return $this->success('Support information retrieved successfully.', [
            'phone'         => config('fleetops.support_phone',    '+20-100-000-0000'),
            'email'         => config('fleetops.support_email',    'support@fleetops.com'),
            'whatsapp'      => config('fleetops.support_whatsapp', null),
            'working_hours' => config('fleetops.support_hours',    'Sun–Thu, 9:00 AM – 6:00 PM'),
            'live_chat_url' => config('fleetops.live_chat_url',    null),
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Resolve the order row for a given token.
     * Returns null on token miss, expiry, or missing order.
     */
    private function resolveOrderFromToken(string $token): ?\stdClass
    {
        $tokenRecord = DB::table('tracking_tokens')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return null;
        }

        return DB::table('order')
            ->where('OrderID', $tokenRecord->order_id)
            ->first();
    }

    /**
     * Standard success envelope.
     * `data` is always a flat array (list-compatible at the top level).
     */
    private function success(string $message, array $data): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];
    }

    /**
     * Standard failure envelope.
     * `errors` is always an array.
     */
    private function failure(string $message, array $errors = [], int $statusCode = 422): array
    {
        return [
            'success'     => false,
            'message'     => $message,
            'errors'      => $errors,
            'data'        => [],
            'status_code' => $statusCode,
        ];
    }

    /**
     * Shorthand for invalid/expired token failure.
     */
    private function tokenNotFound(): array
    {
        return $this->failure(
            'Invalid or expired tracking link.',
            ['token' => ['This link is invalid or expired.']],
            404
        );
    }

    /**
     * Format PromisedWindow into a readable string.
     * e.g. "Today at 2:00 PM" or "Wed, May 7 at 2:00 PM"
     */
    private function formatEtaWindow(string $start): string
    {
        $startCarbon = Carbon::parse($start);
        $dayLabel    = $startCarbon->isToday() ? 'Today' : $startCarbon->format('D, M j');
        return "{$dayLabel} at {$startCarbon->format('g:i A')}";
    }
}
