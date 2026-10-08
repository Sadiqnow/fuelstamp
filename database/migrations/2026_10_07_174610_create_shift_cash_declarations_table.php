<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_cash_declarations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('station_id');
            $table->uuid('shift_id');
            $table->uuid('declared_by_user_id');

            $table->unsignedBigInteger('declared_cash_minor');

            $table->timestamp('declared_at');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique([
                'shift_id',
                'declared_by_user_id',
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

            $table->foreign('declared_by_user_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_cash_declarations');
    }
};