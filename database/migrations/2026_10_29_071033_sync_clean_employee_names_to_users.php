<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('employees')) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $emp = $user->employeeProfile();
            if ($emp && ! empty($emp->nama) && $user->name !== $emp->nama) {
                $user->update(['name' => $emp->nama]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as we retain original nama
    }
};
