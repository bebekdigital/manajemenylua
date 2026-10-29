<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('role_permissions')) {
            // Hapus permission untuk modul-modul lain sementara waktu sesuai permintaan penyederhanaan
            DB::table('role_permissions')
                ->where('feature_group', '!=', 'employee_detail_history')
                ->delete();

            // Pastikan data role utama memiliki konfigurasi awal untuk Detail Data Pegawai
            $roles = [
                'superadmin' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'can_export' => true,
                    'can_import' => true,
                ],
                'Kepala Bidang SDM' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'can_export' => true,
                    'can_import' => true,
                ],
                'Guru' => [
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => true,
                    'can_delete' => false,
                    'can_export' => false,
                    'can_import' => false,
                ],
            ];

            foreach ($roles as $role => $perms) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role' => $role,
                        'feature_group' => 'employee_detail_history',
                    ],
                    array_merge($perms, [
                        'feature_name' => 'Detail Data Pegawai',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ])
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
