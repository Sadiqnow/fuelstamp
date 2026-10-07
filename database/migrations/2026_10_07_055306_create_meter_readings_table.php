<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'meter_readings',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('tenant_id');
                $table->uuid('station_id');
                $table->uuid('shift_id');
                $table->uuid('nozzle_id');

                $table->uuid(
                    'captured_by_user_id'
                );

                $table->string(
                    'reading_type',
                    24
                );

                $table->decimal(
                    'meter_litres',
                    16,
                    3
                );

                $table->timestamp(
                    'captured_at'
                );

                $table->string(
                    'evidence_path'
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();

                $table->unique([
                    'shift_id',
                    'nozzle_id',
                    'reading_type',
                ], 'shift_nozzle_reading_unique');

                $table->index([
                    'station_id',
                    'captured_at',
                ]);

                $table->index([
                    'shift_id',
                    'reading_type',
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

                $table->foreign('nozzle_id')
                    ->references('id')
                    ->on('nozzles');

                $table->foreign(
                    'captured_by_user_id'
                )
                    ->references('id')
                    ->on('users');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'meter_readings'
        );
    }
};