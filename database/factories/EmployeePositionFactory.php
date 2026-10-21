<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeePosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeePosition>
 */
class EmployeePositionFactory extends Factory
{
    protected $model = EmployeePosition::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'unit_kerja' => fake()->randomElement(['SDIT Ulil Albab', 'SMP IT Ulil Albab', 'Yayasan']),
            'jabatan' => fake()->randomElement(['Guru Kelas', 'Wali Kelas', 'Kepala Sekolah', 'Staff TU']),
            'jenis_kepegawaian' => fake()->randomElement(['Tenaga Pendidik', 'Tenaga Kependidikan']),
            'tmt_jabatan' => fake()->date(),
            'tst_jabatan' => null,
        ];
    }
}
