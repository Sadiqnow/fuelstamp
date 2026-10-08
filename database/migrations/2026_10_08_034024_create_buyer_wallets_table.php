<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('user_id');

            $table->string('wallet_code', 40);

            /*
             * ACTIVE | SUSPENDED | CLOSED
             */
            $table->string('status', 24)
                ->default('ACTIVE');

            $table->timestamp('activated_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'user_id'],
                'buyer_wallet_user_unique'
            );

            $table->unique('wallet_code');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_wallets');
    }
};