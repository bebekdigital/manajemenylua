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
        Schema::table('department_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('department_positions', 'level')) {
                $table->unsignedTinyInteger('level')->default(3)->after('name');
            }

            if (! Schema::hasColumn('department_positions', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('level')
                    ->constrained('department_positions')
                    ->nullOnDelete();
            }

            $table->foreignId('department_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_positions', function (Blueprint $table) {
            if (Schema::hasColumn('department_positions', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }

            if (Schema::hasColumn('department_positions', 'level')) {
                $table->dropColumn('level');
            }
        });
    }
};
