<?php

/**
 * @file: SparePartRepository.php
 * @description: مستودع بيانات قطع الغيار - Maintenance Service (fn31)
 * @module: Maintenance
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\Maintenance\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\Maintenance\Models\SparePart;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class SparePartRepository extends BaseRepository
{
    public function __construct(SparePart $model)
    {
        parent::__construct($model);
    }

    public function getLowStockParts(): Collection
    {
        // TODO: return $this->model->lowStock()->get();
    }

    public function deductStock(int $partId, int $quantity): bool
    {
        $part = $this->model->where('part_id', $partId)->lockForUpdate()->first();

        if (!$part) {
            throw new Exception("قطعة الغيار غير موجودة (ID: {$partId})");
        }

        if ($part->stock_quantity < $quantity) {
            throw new Exception("المخزون غير كافي للقطعة: {$part->name} (متاح: {$part->stock_quantity}, مطلوب: {$quantity})");
        }

        $this->model->where('part_id', $partId)->decrement('stock_quantity', $quantity);

        // Reload to get updated stock and check reorder threshold
        $part->refresh();
        if ($part->stock_quantity <= $part->minimum_stock) {
            logger()->warning("[SparePartRepository] قطعة غيار تحتاج إعادة طلب: {$part->name} (متبقي: {$part->stock_quantity})");
        }

        return true;
    }

    public function addStock(int $partId, int $quantity): bool
    {
        // TODO: $this->model->where('part_id', $partId)->increment('stock_quantity', $quantity);
        // return true;
    }
}
