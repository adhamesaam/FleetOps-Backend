<?php

/**
 * Migration: create_work_orders_table
 * @module: Maintenance
 * @author: Team Leader (Khalid)
 *
 * Work Order States: open → assigned → in_progress → resolved → closed
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->bigIncrements('work_order_id');

            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('mechanic_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->string('type', 30);       // routine | emergency | breakdown
            $table->string('status', 30)->default('open'); // open|assigned|in_progress|resolved|closed
            $table->text('description');
            $table->decimal('repair_cost', 12, 2)->nullable();
            $table->json('parts_used')->nullable();
            $table->string('priority', 20)->default('medium'); // low|medium|high|critical
            $table->decimal('odometer_at_service', 12, 2)->nullable();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_id')
                  ->references('vehicle_id')
                  ->on('vehicles');

            // SQL Server does not allow multiple SET NULL FKs to the same table
            // (cycle/multiple cascade paths) — use NO ACTION instead
            $table->foreign('mechanic_id')
                  ->references('user_id')
                  ->on('users');

            $table->foreign('created_by')
                  ->references('user_id')
                  ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
