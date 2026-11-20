<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('role_permissions') && ! Schema::hasColumn('role_permissions', 'data_scope')) {
            Schema::table('role_permissions', function (Blueprint $table): void {
                $table->string('data_scope', 20)->default('all')->after('feature_name');
            });

            DB::table('role_permissions')
                ->where('feature_group', 'employee_detail_history')
                ->where(function ($query): void {
                    $query->whereRaw("LOWER(role) LIKE '%guru%'")
                        ->orWhereRaw("LOWER(role) LIKE '%wali kelas%'");
                })
                ->update(['data_scope' => 'self']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_permissions') && Schema::hasColumn('role_permissions', 'data_scope')) {
            Schema::table('role_permissions', function (Blueprint $table): void {
                $table->dropColumn('data_scope');
            });
        }
    }
};
