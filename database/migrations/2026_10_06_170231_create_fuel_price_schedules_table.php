<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'fuel_price_schedules',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('tenant_id');

                $table->uuid('station_id');

                $table->unsignedSmallInteger(
                    'fuel_product_id'
                );

                /*
                 * Store money in kobo.
                 *
                 * Example:
                 * ₦945.00 = 94500
                 */
                $table->unsignedBigInteger(
                    'price_minor_per_litre'
                );

                $table->timestamp(
                    'effective_from'
                );

                $table->timestamp(
                    'effective_to'
                )->nullable();

                $table->uuid(
                    'created_by_user_id'
                );

                $table->timestamps();

                $table->index([
                    'station_id',
                    'fuel_product_id',
                    'effective_from',
                ], 'fuel_price_effective_idx');

                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('station_id')
                    ->references('id')
                    ->on('stations')
                    ->cascadeOnDelete();

                $table->foreign(
                    'fuel_product_id'
                )
                    ->references('id')
                    ->on('fuel_products');

                $table->foreign(
                    'created_by_user_id'
                )
                    ->references('id')
                    ->on('users');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'fuel_price_schedules'
        );
    }
};