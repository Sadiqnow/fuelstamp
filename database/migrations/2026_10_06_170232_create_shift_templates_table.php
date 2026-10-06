<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shift_templates',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('tenant_id');

                $table->uuid('station_id');

                $table->string('name', 100);

                $table->unsignedInteger(
                    'duration_minutes'
                );

                $table->unsignedInteger(
                    'break_minutes'
                )->default(0);

                $table->unsignedInteger(
                    'reconciliation_window_minutes'
                )->default(30);

                $table->boolean('active')
                    ->default(true);

                $table->timestamps();

                $table->softDeletes();

                $table->index([
                    'station_id',
                    'active',
                ]);

                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('station_id')
                    ->references('id')
                    ->on('stations')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shift_templates'
        );
    }
};