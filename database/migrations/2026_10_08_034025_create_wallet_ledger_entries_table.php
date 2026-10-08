<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'wallet_ledger_entries',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('tenant_id');
                $table->uuid('wallet_id');
                $table->uuid('wallet_account_id');

                /*
                 * CREDIT or DEBIT
                 */
                $table->string(
                    'direction',
                    12
                );

                /*
                 * NGN | CREDIT | LITRE
                 */
                $table->string(
                    'asset_type',
                    20
                );

                $table->unsignedSmallInteger(
                    'fuel_product_id'
                )->nullable();

                /*
                 * For NGN/CREDIT movements
                 */
                $table->unsignedBigInteger(
                    'amount_minor'
                )->nullable();

                /*
                 * For litre movements
                 */
                $table->decimal(
                    'volume_litres',
                    16,
                    3
                )->nullable();

                /*
                 * Balance after this entry.
                 */
                $table->bigInteger(
                    'balance_after_minor'
                )->nullable();

                $table->decimal(
                    'balance_after_litres',
                    16,
                    3
                )->nullable();

                /*
                 * FUNDING
                 * PURCHASE
                 * REDEMPTION
                 * LITER_FLASH_SEND
                 * LITER_FLASH_RECEIVE
                 * REFUND
                 * ADJUSTMENT
                 */
                $table->string(
                    'entry_type',
                    32
                );

                /*
                 * Future links:
                 * transaction / fuel code /
                 * flash transfer / settlement.
                 */
                $table->string(
                    'reference_type',
                    40
                )->nullable();

                $table->uuid(
                    'reference_id'
                )->nullable();

                $table->string(
                    'reference_code',
                    100
                )->nullable();

                /*
                 * Protect retry operations.
                 */
                $table->string(
                    'idempotency_key',
                    100
                );

                $table->uuid(
                    'created_by_user_id'
                )->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamp(
                    'posted_at'
                );

                $table->timestamps();

                $table->unique([
                    'tenant_id',
                    'idempotency_key',
                ], 'wallet_ledger_idempotency_unique');

                $table->index([
                    'wallet_id',
                    'posted_at',
                ]);

                $table->index([
                    'reference_type',
                    'reference_id',
                ]);

                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants');

                $table->foreign('wallet_id')
                    ->references('id')
                    ->on('buyer_wallets');

                $table->foreign(
                    'wallet_account_id'
                )
                    ->references('id')
                    ->on('wallet_accounts');

                $table->foreign(
                    'fuel_product_id'
                )
                    ->references('id')
                    ->on('fuel_products')
                    ->nullOnDelete();

                $table->foreign(
                    'created_by_user_id'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'wallet_ledger_entries'
        );
    }
};