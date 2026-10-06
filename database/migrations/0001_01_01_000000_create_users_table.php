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
        Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    $table->string('user_code', 32)->unique();
    $table->string('name', 160);
    $table->string('phone', 32);
    $table->string('email', 190)->nullable();
    $table->string('password');
    $table->string('status', 24)->default('ACTIVE');
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
    $table->unique(['tenant_id', 'phone']);
    $table->index(['tenant_id', 'status']);
});

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
