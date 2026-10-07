<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->uuid('approved_by_user_id')
                ->nullable()
                ->after('status');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('approved_by_user_id');

            $table->text('approval_notes')
                ->nullable()
                ->after('approved_at');

            $table->foreign('approved_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropForeign([
                'approved_by_user_id'
            ]);

            $table->dropColumn([
                'approved_by_user_id',
                'approved_at',
                'approval_notes',
            ]);
        });
    }
};