<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_discrepancies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('station_id');
            $table->uuid('shift_id');
            $table->uuid('reconciliation_report_id');

            $table->uuid('nozzle_id')
                ->nullable();

            /*
             * VOLUME_VARIANCE
             * CASH_VARIANCE
             * PAYMENT_VARIANCE
             * METER_EXCEPTION
             * OTHER
             */
            $table->string(
                'discrepancy_type',
                32
            );

            /*
             * OPEN
             * EXPLAINED
             * RESOLVED
             * ESCALATED
             */
            $table->string(
                'status',
                24
            )->default('OPEN');

            $table->decimal(
                'variance_litres',
                16,
                3
            )->nullable();

            /*
             * Signed because shortage may be negative.
             */
            $table->bigInteger(
                'variance_minor'
            )->nullable();

            $table->text(
                'description'
            );

            $table->text(
                'resolution_notes'
            )->nullable();

            $table->uuid(
                'created_by_user_id'
            );

            $table->uuid(
                'resolved_by_user_id'
            )->nullable();

            $table->timestamp(
                'resolved_at'
            )->nullable();

            $table->timestamps();

            $table->index([
                'shift_id',
                'status',
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
                'reconciliation_report_id'
            )
                ->references('id')
                ->on('reconciliation_reports');

            $table->foreign('nozzle_id')
                ->references('id')
                ->on('nozzles')
                ->nullOnDelete();

            $table->foreign(
                'created_by_user_id'
            )
                ->references('id')
                ->on('users');

            $table->foreign(
                'resolved_by_user_id'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shift_discrepancies'
        );
    }
};