<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'transaction_payments',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid(
                    'transaction_id'
                );

                /*
                 * CASH
                 * POS
                 * TRANSFER
                 * OTHER
                 */
                $table->string(
                    'payment_method',
                    24
                );

                $table->unsignedBigInteger(
                    'amount_minor'
                );

                $table->string(
                    'payment_reference',
                    100
                )->nullable();

                $table->string(
                    'status',
                    24
                )->default('CONFIRMED');

                $table->timestamp(
                    'confirmed_at'
                );

                $table->timestamps();

                $table->index([
                    'payment_method',
                    'confirmed_at',
                ]);

                $table->foreign(
                    'transaction_id'
                )
                    ->references('id')
                    ->on('transactions');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'transaction_payments'
        );
    }
};