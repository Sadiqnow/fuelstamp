<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->nullable();

            $table->uuid('user_id');

            $table->foreignId('role_id')->constrained('roles');

            // We create station_id now.
            // FK will be added when stations table exists.
            $table->uuid('station_id')->nullable();
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index([
                'user_id',
                'effective_to'
            ]);

            $table->index([
                'station_id',
                'role_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
