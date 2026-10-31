<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeePermit;
use Illuminate\Database\Seeder;

class EmployeePermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employee = Employee::first();
        if ($employee) {
            EmployeePermit::create([
                'employee_id' => $employee->id,
                'tanggal' => now()->subDays(2)->format('Y-m-d'),
                'jenis' => 'Izin',
                'detail' => 'Acara Keluarga',
                'keterangan' => 'Menghadiri pernikahan kerabat',
                'status' => 'disetujui',
                'catatan' => 'Disetujui pimpinan',
            ]);
        }
    }
}
