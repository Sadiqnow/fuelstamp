<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'manual_transaction_details',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid(
                    'transaction_id'
                );

                /*
                 * LITRES or AMOUNT
                 *
                 * Identifies what the attendant
                 * originally entered.
                 */
                $table->string(
                    'entry_mode',
                    20
                );

                $table->decimal(
                    'entered_litres',
                    14,
                    3
                )->nullable();

                $table->unsignedBigInteger(
                    'entered_amount_minor'
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    'transaction_id'
                );

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
            'manual_transaction_details'
        );
    }
};