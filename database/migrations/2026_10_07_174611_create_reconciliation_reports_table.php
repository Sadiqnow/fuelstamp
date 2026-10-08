<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('station_id');
            $table->uuid('shift_id');

            $table->uuid('generated_by_user_id');

            $table->string('status', 32)
                ->default('PENDING_REVIEW');

            $table->decimal(
                'ledger_litres',
                16,
                3
            )->default(0);

            $table->decimal(
                'meter_movement_litres',
                16,
                3
            )->default(0);

            $table->decimal(
                'volume_variance_litres',
                16,
                3
            )->default(0);

            $table->unsignedBigInteger(
                'total_revenue_minor'
            )->default(0);

            $table->unsignedBigInteger(
                'expected_cash_minor'
            )->default(0);

            $table->unsignedBigInteger(
                'declared_cash_minor'
            )->default(0);

            $table->bigInteger(
                'cash_variance_minor'
            )->default(0);

            $table->timestamp('generated_at');

            $table->timestamp('reviewed_at')
                ->nullable();

            $table->uuid('reviewed_by_user_id')
                ->nullable();

            $table->text('review_notes')
                ->nullable();

            $table->timestamps();

            $table->unique('shift_id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('station_id')
                ->references('id')
                ->on('stations');

            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts');

            $table->foreign('generated_by_user_id')
                ->references('id')
                ->on('users');

            $table->foreign('reviewed_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_reports');
    }
};