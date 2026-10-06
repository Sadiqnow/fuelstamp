<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nozzles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->uuid('pump_id');

            $table->string('nozzle_code', 32);

            $table->unsignedSmallInteger('fuel_product_id');

            $table->string('status', 24)
                ->default('ACTIVE');

            $table->timestamps();

            $table->softDeletes();

            $table->unique([
                'pump_id',
                'nozzle_code',
            ]);

            $table->index([
                'pump_id',
                'status',
            ]);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('pump_id')
                ->references('id')
                ->on('pumps')
                ->cascadeOnDelete();

            $table->foreign('fuel_product_id')
                ->references('id')
                ->on('fuel_products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nozzles');
    }
};