<?php

/**
 * Migration: create_orders_table (Updated to match new DDL)
 * DDL Source: FleetOpsDB.dbo.[Order]
 *
 * @author Team Leader (Khalid)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order', function (Blueprint $table) {
            $table->bigIncrements('OrderID');
            
            $table->bigInteger('DriverID(FK)')->nullable();
            $table->bigInteger('CustomerID(FK)')->nullable();
            $table->bigInteger('vehicle_id(FK)')->nullable();
            $table->bigInteger('TransactionID(FK)')->nullable();
            
            $table->string('Status', 50)->nullable();
            $table->char('ETA', 10)->nullable();
            $table->dateTime('PromisedWindow', 7)->nullable();
            $table->integer('Priority')->nullable(); // 0 to 100
            $table->string('Type', 50)->default('Normal'); // Normal, Express, Low
            $table->integer('Price');
            $table->char('digital_signature', 10)->nullable();
            $table->string('Delivery_preference', 255)->nullable();
            $table->string('Payment_method', 50)->nullable();
            $table->dateTime('Created_at', 7)->nullable();
            $table->dateTime('UpdatedAt', 7)->nullable();
            $table->boolean('Perishable');
            $table->dateTime('DeliveredAt', 7)->nullable();
            $table->integer('Weight')->nullable();
            $table->integer('Volume')->nullable();
            $table->string('LiveTrackingLink', 255)->nullable();
            $table->decimal('DeliveryTimeWindow', 12, 2)->nullable();
            $table->decimal('Longitude', 11, 8)->nullable();
            $table->decimal('Latitude', 10, 8)->nullable();
            $table->string('Area', 255)->nullable();

            // Foreign Keys (TransactionID FK will be added in a later migration)
            $table->foreign('vehicle_id(FK)', 'FK_Order_Vehicle')
                  ->references('vehicle_id')->on('vehicles')->noActionOnDelete();
                  
            $table->foreign('CustomerID(FK)', 'FK_Orders_Customers')
                  ->references('customer_id')->on('customers')->noActionOnDelete();
                  
            $table->foreign('DriverID(FK)', 'FK_Orders_Drivers')
                  ->references('driver_id')->on('drivers')->noActionOnDelete();
        });

        // Add check constraints
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE [order] ADD CONSTRAINT [CK_Orders_Status] CHECK ([Status] IN ('Pending', 'Assigned', 'InTransit', 'Out for Delivery', 'Delivered', 'Failed', 'Returned'))");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE [order] ADD CONSTRAINT [CK_Orders_Type] CHECK ([Type] IN ('Normal', 'Express', 'Low'))");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE [order] ADD CONSTRAINT [CK_Orders_Priority] CHECK ([Priority] >= 0 AND [Priority] <= 100)");
    }

    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};