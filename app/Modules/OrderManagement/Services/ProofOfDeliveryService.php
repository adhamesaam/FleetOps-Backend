<?php

/**
 * @file: ProofOfDeliveryService.php
 * @description: خدمة إثبات التسليم (POD) - صورة وتوقيع (fn13/14 / OM-05)
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Services;

use App\Modules\OrderManagement\Repositories\OrderRepository;
use App\Modules\OrderManagement\Repositories\ProofOfDeliveryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ProofOfDeliveryService
{
    protected OrderRepository $orderRepository;
    protected ProofOfDeliveryRepository $podRepository;

    public function __construct(OrderRepository $orderRepository, ProofOfDeliveryRepository $podRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->podRepository = $podRepository;
    }

    /**
     * حفظ إثبات التسليم (توقيع + صورة) (fn13/14)
     * @param int $orderId
     * @param array $data  (driver_id, lat, lng, customer_name, customer_signed, is_safe_drop)
     * @return mixed  ProofOfDelivery record
     * @throws Exception
     */
    public function storePOD(int $orderId, array $data)
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = $this->orderRepository->findById($orderId);

            if (!$order) {
                throw new Exception("Order not found.");
            }

            if ($order->Status === 'Delivered') {
                throw new Exception("Order is already delivered.");
            }

            $signatureUrl = null;

            if (!empty($data['customer_signed']) && !empty($data['signature'])) {
                // Decode base64
                $imageParts = explode(";base64,", $data['signature']);
                $imageTypeAux = explode("image/", $imageParts[0]);
                
                // Set default extension if not present in base64 string
                $imageExtension = isset($imageTypeAux[1]) ? $imageTypeAux[1] : 'png';
                
                $imageBase64 = base64_decode(isset($imageParts[1]) ? $imageParts[1] : $imageParts[0]);
                
                $fileName = 'signatures/' . Str::uuid() . '.' . $imageExtension;
                
                Storage::disk('public')->put($fileName, $imageBase64);
                $signatureUrl = Storage::disk('public')->url($fileName);
            }

            // POD data is stored on the order row itself — update the existing order record
            $this->orderRepository->update($orderId, [
                'digital_signature' => $signatureUrl ?? $data['signature'] ?? null,
                'Latitude'          => $data['lat'],
                'Longitude'         => $data['lng'],
                'DeliveredAt'       => now(),
                'Status'            => 'Delivered',
            ]);

            $pod = $this->orderRepository->findById($orderId);

            return $pod;
        });
    }
}
