<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->uuid('station_id');

            $table->string('tank_code', 32);

            $table->unsignedSmallInteger('fuel_product_id');

            $table->decimal(
                'capacity_litres',
                14,
                3
            );

            $table->decimal(
                'low_level_threshold_litres',
                14,
                3
            )->default(0);

            $table->string('status', 24)
                ->default('ACTIVE');

            $table->timestamps();

            $table->softDeletes();

            $table->unique([
                'station_id',
                'tank_code',
            ]);

            $table->index([
                'station_id',
                'fuel_product_id',
            ]);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('station_id')
                ->references('id')
                ->on('stations')
                ->cascadeOnDelete();

            $table->foreign('fuel_product_id')
                ->references('id')
                ->on('fuel_products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};