<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('station_id');
            $table->uuid('shift_template_id')->nullable();
            $table->uuid('manager_user_id');

            $table->string('shift_code', 40);

            $table->string('status', 32)
                ->default('DRAFT');

            $table->timestamp('scheduled_start_at')
                ->nullable();

            $table->timestamp('opened_at')
                ->nullable();

            $table->timestamp('activated_at')
                ->nullable();

            $table->timestamp('closing_started_at')
                ->nullable();

            $table->timestamp('closed_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'station_id',
                'shift_code',
            ]);

            $table->index([
                'station_id',
                'status',
            ]);

            $table->index([
                'manager_user_id',
                'status',
            ]);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('station_id')
                ->references('id')
                ->on('stations');

            $table->foreign('shift_template_id')
                ->references('id')
                ->on('shift_templates')
                ->nullOnDelete();

            $table->foreign('manager_user_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};