<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->string('station_code', 32);

            $table->string('name', 180);

            $table->text('address');

            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('status', 24)->default('PENDING');

            $table->date('license_expiry')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'station_code',
            ]);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};