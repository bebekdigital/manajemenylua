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
        if (Schema::hasTable('departments') && ! Schema::hasColumn('departments', 'unit_group')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('unit_group', 50)->default('Yayasan')->after('name');
            });

            // Update unit pendidikan yang sudah ada agar otomatis terisi 'Satuan Pendidikan'
            if (Schema::hasTable('school_units')) {
                $schoolUnitNames = DB::table('school_units')->pluck('name')->toArray();
                if (! empty($schoolUnitNames)) {
                    DB::table('departments')->whereIn('name', $schoolUnitNames)->update(['unit_group' => 'Satuan Pendidikan']);
                }
            }

            DB::table('departments')
                ->where(function ($q) {
                    $q->where('name', 'like', '%SD%')
                        ->orWhere('name', 'like', '%SMP%')
                        ->orWhere('name', 'like', '%SMA%')
                        ->orWhere('name', 'like', '%SMK%')
                        ->orWhere('name', 'like', '%TK%')
                        ->orWhere('name', 'like', '%KB%')
                        ->orWhere('name', 'like', '%PAUD%');
                })
                ->update(['unit_group' => 'Satuan Pendidikan']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'unit_group')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('unit_group');
            });
        }
    }
};
