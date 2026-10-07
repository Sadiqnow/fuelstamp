<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('station_id');
            $table->uuid('shift_id');

            $table->uuid('attendant_user_id');

            $table->uuid('nozzle_id');

            $table->unsignedSmallInteger(
                'fuel_product_id'
            );

            /*
             * FUELSTAMP | TRADITIONAL
             */
            $table->string(
                'lane_type',
                24
            );

            $table->string(
                'transaction_code',
                50
            );

            /*
             * Litres always stored at 3 decimal places.
             */
            $table->decimal(
                'volume_litres',
                14,
                3
            );

            /*
             * Kobo per litre.
             * Example:
             * ₦945/L = 94500
             */
            $table->unsignedBigInteger(
                'unit_price_minor'
            );

            /*
             * Total transaction value in kobo.
             */
            $table->unsignedBigInteger(
                'amount_minor'
            );

            $table->string(
                'status',
                24
            )->default('COMPLETED');

            /*
             * Useful for retries/mobile network problems.
             */
            $table->string(
                'idempotency_key',
                100
            );

            $table->timestamp(
                'completed_at'
            );

            $table->timestamps();

            $table->unique(
                'transaction_code'
            );

            $table->unique([
                'tenant_id',
                'idempotency_key',
            ], 'transaction_idempotency_unique');

            $table->index([
                'shift_id',
                'status',
            ]);

            $table->index([
                'station_id',
                'completed_at',
            ]);

            $table->index([
                'attendant_user_id',
                'completed_at',
            ]);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('station_id')
                ->references('id')
                ->on('stations');

            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts');

            $table->foreign(
                'attendant_user_id'
            )
                ->references('id')
                ->on('users');

            $table->foreign('nozzle_id')
                ->references('id')
                ->on('nozzles');

            $table->foreign(
                'fuel_product_id'
            )
                ->references('id')
                ->on('fuel_products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'transactions'
        );
    }
};