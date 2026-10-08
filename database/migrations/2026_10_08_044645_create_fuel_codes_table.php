<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->uuid('buyer_user_id');

            $table->uuid('wallet_id');

            $table->uuid('wallet_account_id');

            $table->uuid('wallet_hold_id') -> nullable();

            /*
             * Station-bound authorization.
             */
            $table->uuid('station_id');

            $table->unsignedSmallInteger(
                'fuel_product_id'
            );

            /*
             * NGN | LITRE
             */
            $table->string(
                'asset_type',
                20
            );

            /*
             * AMOUNT | LITRES
             */
            $table->string(
                'authorization_mode',
                20
            );

            $table->unsignedBigInteger(
                'authorized_amount_minor'
            )->nullable();

            $table->decimal(
                'authorized_volume_litres',
                16,
                3
            )->nullable();

            /*
             * Human-readable reference.
             * Example FS-7KQ92D
             */
            $table->string(
                'code',
                32
            )->unique();

            /*
             * Do not store raw QR secret.
             */
            $table->string(
                'qr_token_hash',
                64
            );

            /*
             * Laravel password hash of
             * six-digit PIN.
             */
            $table->string(
                'pin_hash'
            );

            /*
             * ACTIVE
             * REDEEMED
             * CANCELLED
             * EXPIRED
             */
            $table->string(
                'status',
                24
            )->default('ACTIVE');

            $table->unsignedTinyInteger(
                'failed_attempts'
            )->default(0);

            $table->unsignedTinyInteger(
                'max_attempts'
            )->default(5);

            $table->timestamp(
                'expires_at'
            );

            $table->timestamp(
                'redeemed_at'
            )->nullable();

            $table->uuid(
                'redeemed_transaction_id'
            )->nullable();

            $table->timestamp(
                'cancelled_at'
            )->nullable();

            $table->uuid(
                'cancelled_by_user_id'
            )->nullable();

            $table->text(
                'cancellation_reason'
            )->nullable();

            $table->string(
                'idempotency_key',
                100
            );

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'idempotency_key',
            ], 'fuel_code_idempotency_unique');

            $table->index([
                'buyer_user_id',
                'status',
            ]);

            $table->index([
                'station_id',
                'status',
            ]);

            $table->index([
                'expires_at',
                'status',
            ]);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('buyer_user_id')
                ->references('id')
                ->on('users');

            $table->foreign('wallet_id')
                ->references('id')
                ->on('buyer_wallets');

            $table->foreign('wallet_account_id')
                ->references('id')
                ->on('wallet_accounts');

            $table->foreign('wallet_hold_id')
                ->references('id')
                ->on('wallet_holds');

            $table->foreign('station_id')
                ->references('id')
                ->on('stations');

            $table->foreign('fuel_product_id')
                ->references('id')
                ->on('fuel_products');

            /*
             * This transaction does not yet exist
             * when code is generated.
             */
            $table->foreign(
                'redeemed_transaction_id'
            )
                ->references('id')
                ->on('transactions')
                ->nullOnDelete();

            $table->foreign(
                'cancelled_by_user_id'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_codes');
    }
};