<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wallet_holds', 'wallet_account_id')) {
            DB::statement(
                "ALTER TABLE wallet_holds
                 ADD COLUMN wallet_account_id CHAR(36) NULL AFTER wallet_id"
            );

            DB::statement(
                "ALTER TABLE wallet_holds
                 ADD CONSTRAINT wallet_holds_wallet_account_id_foreign
                 FOREIGN KEY (wallet_account_id)
                 REFERENCES wallet_accounts(id)"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wallet_holds', 'wallet_account_id')) {
            DB::statement(
                "ALTER TABLE wallet_holds
                 DROP FOREIGN KEY wallet_holds_wallet_account_id_foreign"
            );

            DB::statement(
                "ALTER TABLE wallet_holds
                 DROP COLUMN wallet_account_id"
            );
        }
    }
};