<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gps_pings', function (Blueprint $table) {
            $table->id('ping_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->decimal('speed_kmh', 5, 2)->default(0);
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            
            $table->boolean('is_spoofed')->default(false);
            
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index('driver_id');
            $table->index('vehicle_id');
            $table->index('route_id');
            $table->index('recorded_at');

            $table->foreign('driver_id')->references('driver_id')->on('drivers')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->nullOnDelete();
            $table->foreign('route_id')->references('route_id')->on('routes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_pings');
    }
};
