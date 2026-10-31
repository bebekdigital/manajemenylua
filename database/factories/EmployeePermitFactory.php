<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeePermit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeePermit>
 */
class EmployeePermitFactory extends Factory
{
    protected $model = EmployeePermit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'tanggal' => fake()->date(),
            'tanggal_selesai' => null,
            'jenis' => fake()->randomElement(['Izin', 'Sakit', 'Cuti', 'Tugas Luar']),
            'detail' => fake()->sentence(3),
            'keterangan' => fake()->paragraph(),
            'lampiran_path' => null,
            'lampiran_nama' => null,
            'status' => fake()->randomElement(['diajukan', 'disetujui', 'ditolak']),
            'catatan' => null,
        ];
    }
}
