<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_holds', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_holds', 'tenant_id')) {
                $table->uuid('tenant_id');
            }

            if (! Schema::hasColumn('wallet_holds', 'wallet_id')) {
                $table->uuid('wallet_id');
            }

            if (! Schema::hasColumn('wallet_holds', 'wallet_account_id')) {
                $table->uuid('wallet_account_id');
            }

            if (! Schema::hasColumn('wallet_holds', 'asset_type')) {
                $table->string('asset_type', 20);
            }

            if (! Schema::hasColumn('wallet_holds', 'fuel_product_id')) {
                $table->unsignedSmallInteger('fuel_product_id')->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'amount_minor')) {
                $table->unsignedBigInteger('amount_minor')->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'volume_litres')) {
                $table->decimal('volume_litres', 16, 3)->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'status')) {
                $table->string('status', 24)->default('ACTIVE');
            }

            if (! Schema::hasColumn('wallet_holds', 'reference_type')) {
                $table->string('reference_type', 40)->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'reference_id')) {
                $table->uuid('reference_id')->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'consumed_at')) {
                $table->timestamp('consumed_at')->nullable();
            }

            if (! Schema::hasColumn('wallet_holds', 'released_at')) {
                $table->timestamp('released_at')->nullable();
            }
        });

        $foreignKeys = collect(
            Schema::getForeignKeys('wallet_holds')
        )->pluck('name')->all();

        Schema::table('wallet_holds', function (Blueprint $table) use ($foreignKeys) {
            if (! in_array('wallet_holds_tenant_id_foreign', $foreignKeys, true)) {
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants');
            }

            if (! in_array('wallet_holds_wallet_id_foreign', $foreignKeys, true)) {
                $table->foreign('wallet_id')
                    ->references('id')
                    ->on('buyer_wallets');
            }

            if (! in_array('wallet_holds_wallet_account_id_foreign', $foreignKeys, true)) {
                $table->foreign('wallet_account_id')
                    ->references('id')
                    ->on('wallet_accounts');
            }

            if (! in_array('wallet_holds_fuel_product_id_foreign', $foreignKeys, true)) {
                $table->foreign('fuel_product_id')
                    ->references('id')
                    ->on('fuel_products')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $foreignKeys = collect(
            Schema::getForeignKeys('wallet_holds')
        )->pluck('name')->all();

        Schema::table('wallet_holds', function (Blueprint $table) use ($foreignKeys) {
            foreach ([
                'wallet_holds_tenant_id_foreign',
                'wallet_holds_wallet_id_foreign',
                'wallet_holds_wallet_account_id_foreign',
                'wallet_holds_fuel_product_id_foreign',
            ] as $foreignKey) {
                if (in_array($foreignKey, $foreignKeys, true)) {
                    $table->dropForeign($foreignKey);
                }
            }

            foreach ([
                'tenant_id',
                'wallet_id',
                'wallet_account_id',
                'asset_type',
                'fuel_product_id',
                'amount_minor',
                'volume_litres',
                'status',
                'reference_type',
                'reference_id',
                'expires_at',
                'consumed_at',
                'released_at',
            ] as $column) {
                if (Schema::hasColumn('wallet_holds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
