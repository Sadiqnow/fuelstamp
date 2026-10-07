<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shift_nozzle_assignments',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('tenant_id');
                $table->uuid('station_id');
                $table->uuid('shift_id');

                $table->uuid('nozzle_id');
                $table->uuid('attendant_user_id');

                $table->string(
                    'custody_status',
                    24
                )->default('PENDING');

                $table->timestamp(
                    'assigned_at'
                );

                $table->timestamp(
                    'accepted_at'
                )->nullable();

                $table->timestamp(
                    'released_at'
                )->nullable();

                $table->timestamps();

                $table->unique([
                    'shift_id',
                    'nozzle_id',
                ], 'shift_nozzle_unique');

                $table->index([
                    'shift_id',
                    'attendant_user_id',
                ]);

                $table->index([
                    'nozzle_id',
                    'custody_status',
                ]);

                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants');

                $table->foreign('station_id')
                    ->references('id')
                    ->on('stations');

                $table->foreign('shift_id')
                    ->references('id')
                    ->on('shifts')
                    ->cascadeOnDelete();

                $table->foreign('nozzle_id')
                    ->references('id')
                    ->on('nozzles');

                $table->foreign(
                    'attendant_user_id'
                )
                    ->references('id')
                    ->on('users');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shift_nozzle_assignments'
        );
    }
};