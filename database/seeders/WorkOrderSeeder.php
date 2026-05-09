<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * WorkOrderSeeder — sample work orders covering all statuses and types
 */
class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles  = DB::table('vehicles')->pluck('vehicle_id')->toArray();
        $mechanics = DB::table('users')->where('role', 'mechanic')->pluck('user_id')->toArray();
        $admins    = DB::table('users')->whereIn('role', ['fleet_manager', 'dispatcher'])->pluck('user_id')->toArray();

        if (empty($vehicles)) {
            $this->command->warn('⚠️  WorkOrderSeeder: No vehicles found. Run VehicleSeeder first.');
            return;
        }

        $createdBy  = $admins[0]  ?? null;
        $mechanic1  = $mechanics[0] ?? null;
        $mechanic2  = $mechanics[1] ?? $mechanic1;

        $now = now();

        $orders = [
            // 1. Open — waiting for assignment
            [
                'vehicle_id'          => $vehicles[0],
                'mechanic_id'         => null,
                'created_by'          => $createdBy,
                'type'                => 'breakdown',
                'status'              => 'open',
                'description'         => 'المركبة لا تشتغل — مشكلة في البطارية',
                'repair_cost'         => null,
                'parts_used'          => null,
                'priority'            => 'critical',
                'odometer_at_service' => 45200.00,
                'opened_at'           => $now->copy()->subDays(2),
                'assigned_at'         => null,
                'started_at'          => null,
                'resolved_at'         => null,
                'closed_at'           => null,
                'notes'               => null,
                'created_at'          => $now->copy()->subDays(2),
                'updated_at'          => $now->copy()->subDays(2),
            ],
            // 2. Assigned — mechanic assigned, not started yet
            [
                'vehicle_id'          => $vehicles[1],
                'mechanic_id'         => $mechanic1,
                'created_by'          => $createdBy,
                'type'                => 'emergency',
                'status'              => 'assigned',
                'description'         => 'تسرب زيت من المحرك',
                'repair_cost'         => null,
                'parts_used'          => null,
                'priority'            => 'high',
                'odometer_at_service' => 88750.00,
                'opened_at'           => $now->copy()->subDays(3),
                'assigned_at'         => $now->copy()->subDays(2),
                'started_at'          => null,
                'resolved_at'         => null,
                'closed_at'           => null,
                'notes'               => 'تم تعيين الميكانيكي وينتظر قطع الغيار',
                'created_at'          => $now->copy()->subDays(3),
                'updated_at'          => $now->copy()->subDays(2),
            ],
            // 3. In Progress
            [
                'vehicle_id'          => $vehicles[2],
                'mechanic_id'         => $mechanic2,
                'created_by'          => $createdBy,
                'type'                => 'routine',
                'status'              => 'in_progress',
                'description'         => 'صيانة دورية — تغيير زيت وفلتر هواء',
                'repair_cost'         => null,
                'parts_used'          => json_encode([
                    ['part_id' => null, 'name' => 'زيت محرك 5W-30', 'quantity' => 5],
                    ['part_id' => null, 'name' => 'فلتر هواء', 'quantity' => 1],
                ]),
                'priority'            => 'medium',
                'odometer_at_service' => 121200.00,
                'opened_at'           => $now->copy()->subDays(1),
                'assigned_at'         => $now->copy()->subDays(1),
                'started_at'          => $now->copy()->subHours(3),
                'resolved_at'         => null,
                'closed_at'           => null,
                'notes'               => 'جاري العمل',
                'created_at'          => $now->copy()->subDays(1),
                'updated_at'          => $now->copy()->subHours(3),
            ],
            // 4. Resolved — awaiting fleet manager closure
            [
                'vehicle_id'          => $vehicles[3] ?? $vehicles[0],
                'mechanic_id'         => $mechanic1,
                'created_by'          => $createdBy,
                'type'                => 'breakdown',
                'status'              => 'resolved',
                'description'         => 'استبدال إطار أمامي أيسر بعد ثقب',
                'repair_cost'         => 850.00,
                'parts_used'          => json_encode([
                    ['part_id' => null, 'name' => 'إطار 225/70R15', 'quantity' => 1],
                ]),
                'priority'            => 'high',
                'odometer_at_service' => 67300.00,
                'opened_at'           => $now->copy()->subDays(5),
                'assigned_at'         => $now->copy()->subDays(5),
                'started_at'          => $now->copy()->subDays(4),
                'resolved_at'         => $now->copy()->subDays(1),
                'closed_at'           => null,
                'notes'               => 'تم الإصلاح بنجاح — ينتظر مراجعة مدير الأسطول',
                'created_at'          => $now->copy()->subDays(5),
                'updated_at'          => $now->copy()->subDays(1),
            ],
            // 5. Closed — full lifecycle complete
            [
                'vehicle_id'          => $vehicles[4] ?? $vehicles[1],
                'mechanic_id'         => $mechanic2,
                'created_by'          => $createdBy,
                'type'                => 'routine',
                'status'              => 'closed',
                'description'         => 'فحص دوري شامل قبل رحلة طويلة',
                'repair_cost'         => 1200.00,
                'parts_used'          => json_encode([
                    ['part_id' => null, 'name' => 'فلتر وقود', 'quantity' => 1],
                    ['part_id' => null, 'name' => 'شمعات إشعال', 'quantity' => 4],
                ]),
                'priority'            => 'low',
                'odometer_at_service' => 154900.00,
                'opened_at'           => $now->copy()->subDays(10),
                'assigned_at'         => $now->copy()->subDays(10),
                'started_at'          => $now->copy()->subDays(9),
                'resolved_at'         => $now->copy()->subDays(8),
                'closed_at'           => $now->copy()->subDays(7),
                'notes'               => 'اكتملت الصيانة وأُعيدت المركبة للخدمة',
                'created_at'          => $now->copy()->subDays(10),
                'updated_at'          => $now->copy()->subDays(7),
            ],
            // 6. Open — low priority routine
            [
                'vehicle_id'          => $vehicles[5] ?? $vehicles[2],
                'mechanic_id'         => null,
                'created_by'          => $createdBy,
                'type'                => 'routine',
                'status'              => 'open',
                'description'         => 'تغيير فلتر مكيف الهواء',
                'repair_cost'         => null,
                'parts_used'          => null,
                'priority'            => 'low',
                'odometer_at_service' => 34050.00,
                'opened_at'           => $now->copy()->subHours(5),
                'assigned_at'         => null,
                'started_at'          => null,
                'resolved_at'         => null,
                'closed_at'           => null,
                'notes'               => null,
                'created_at'          => $now->copy()->subHours(5),
                'updated_at'          => $now->copy()->subHours(5),
            ],
        ];

        DB::table('work_orders')->insert($orders);

        $this->command->info('✅ WorkOrderSeeder: ' . count($orders) . ' work orders seeded.');
    }
}
