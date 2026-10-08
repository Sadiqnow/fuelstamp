<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_audit_packages', function (Blueprint $table) {
            $table->uuid('tenant_id')
                ->after('id');

            $table->uuid('station_id')
                ->after('tenant_id');

            $table->uuid('shift_id')
                ->after('station_id');

            $table->uuid('reconciliation_report_id')
                ->after('shift_id');

            $table->string('package_hash', 64)
                ->after('reconciliation_report_id');

            $table->json('package_payload')
                ->after('package_hash');

            $table->uuid('sealed_by_user_id')
                ->after('package_payload');

            $table->timestamp('sealed_at')
                ->after('sealed_by_user_id');

            $table->unique(
                'shift_id',
                'shift_audit_packages_shift_unique'
            );

            $table->unique(
                'package_hash',
                'shift_audit_packages_hash_unique'
            );

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('station_id')
                ->references('id')
                ->on('stations');

            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts');

            $table->foreign('reconciliation_report_id')
                ->references('id')
                ->on('reconciliation_reports');

            $table->foreign('sealed_by_user_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('shift_audit_packages', function (Blueprint $table) {
            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropForeign([
                'station_id'
            ]);

            $table->dropForeign([
                'shift_id'
            ]);

            $table->dropForeign([
                'reconciliation_report_id'
            ]);

            $table->dropForeign([
                'sealed_by_user_id'
            ]);

            $table->dropUnique(
                'shift_audit_packages_shift_unique'
            );

            $table->dropUnique(
                'shift_audit_packages_hash_unique'
            );

            $table->dropColumn([
                'tenant_id',
                'station_id',
                'shift_id',
                'reconciliation_report_id',
                'package_hash',
                'package_payload',
                'sealed_by_user_id',
                'sealed_at',
            ]);
        });
    }
};