<?php

/**
 * Migration: create_spare_parts_table
 * @module: Maintenance
 * @author: Team Leader (Khalid)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->bigIncrements('part_id');

            $table->string('name', 200);
            $table->string('sku', 100)->unique()->nullable();
            $table->string('category', 50)->default('other'); // oil|filter|tire|brake|battery|bulb|other
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);   // reorder alert threshold
            $table->unsignedInteger('reorder_level')->nullable();
            $table->string('supplier_name', 200)->nullable();
            $table->unsignedSmallInteger('supplier_lead_days')->nullable();
            $table->string('unit', 20)->default('pcs');             // pcs|liter|kg
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
