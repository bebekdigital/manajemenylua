<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'fathudinmahmud'],
            [
                'name' => 'Fathudin Mahmud',
                'email' => 'fathudinmahmud@admin.com',
                'password' => Hash::make('Terusberkarya100@'),
                'role' => 'superadmin',
            ]
        );
    }
}
