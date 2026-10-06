<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'station_staff',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('tenant_id');

                $table->uuid('station_id');

                $table->uuid('user_id');

                $table->string(
                    'staff_type',
                    32
                );

                $table->string(
                    'status',
                    24
                )->default('ACTIVE');

                $table->timestamps();

                $table->softDeletes();

                $table->unique([
                    'station_id',
                    'user_id',
                    'staff_type',
                ]);

                $table->index([
                    'station_id',
                    'status',
                ]);

                $table->index([
                    'user_id',
                    'staff_type',
                ]);

                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('station_id')
                    ->references('id')
                    ->on('stations')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'station_staff'
        );
    }
};