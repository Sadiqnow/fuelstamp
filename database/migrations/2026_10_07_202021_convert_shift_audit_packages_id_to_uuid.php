<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE shift_audit_packages
             MODIFY id CHAR(36) NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE shift_audit_packages
             MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT"
        );
    }
};