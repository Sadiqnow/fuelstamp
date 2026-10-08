<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'reconciliation_nozzle_lines',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('reconciliation_report_id');
                $table->uuid('nozzle_id');

                $table->decimal(
                    'opening_meter_litres',
                    16,
                    3
                );

                $table->decimal(
                    'closing_meter_litres',
                    16,
                    3
                );

                $table->decimal(
                    'meter_movement_litres',
                    16,
                    3
                );

                $table->decimal(
                    'ledger_litres',
                    16,
                    3
                );

                $table->decimal(
                    'variance_litres',
                    16,
                    3
                );

                $table->timestamps();

                $table->unique([
                    'reconciliation_report_id',
                    'nozzle_id',
                ], 'recon_nozzle_unique');

                $table->foreign(
                    'reconciliation_report_id'
                )
                    ->references('id')
                    ->on('reconciliation_reports')
                    ->cascadeOnDelete();

                $table->foreign('nozzle_id')
                    ->references('id')
                    ->on('nozzles');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'reconciliation_nozzle_lines'
        );
    }
};