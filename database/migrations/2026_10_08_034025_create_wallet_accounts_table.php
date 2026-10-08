<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('wallet_id');

            /*
             * NGN | CREDIT | LITRE
             */
            $table->string('asset_type', 20);

            /*
             * Only required for LITRE accounts.
             * Example: PMS / AGO / DPK product ID.
             */
            $table->unsignedSmallInteger(
                'fuel_product_id'
            )->nullable();

            /*
             * Cached balance.
             *
             * NGN/CREDIT:
             * stored as integer minor units.
             */
            $table->bigInteger(
                'balance_minor'
            )->default(0);

            /*
             * Only used for LITRE asset.
             */
            $table->decimal(
                'balance_litres',
                16,
                3
            )->default(0);

            $table->string('status', 24)
                ->default('ACTIVE');

            $table->timestamps();

            $table->unique([
                'wallet_id',
                'asset_type',
                'fuel_product_id',
            ], 'wallet_asset_unique');

            $table->foreign('wallet_id')
                ->references('id')
                ->on('buyer_wallets')
                ->cascadeOnDelete();

            $table->foreign('fuel_product_id')
                ->references('id')
                ->on('fuel_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_accounts');
    }
};