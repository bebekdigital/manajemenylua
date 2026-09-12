<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $academicYears = AcademicYear::all();

        $classroomTemplates = [
            // SDIT — SD (Tingkat 1-6)
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 1, 'name' => '1A', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 2, 'name' => '2A', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 3, 'name' => '3A', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 4, 'name' => '4A', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 5, 'name' => '5A', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 5, 'name' => '5B', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 6, 'name' => '6A', 'capacity' => 30],
            ['unit' => 'SDIT Ulil Albab Gondangrejo', 'jenjang' => 'SD', 'grade' => 6, 'name' => '6B', 'capacity' => 30],

            // SMPIT — SMP (Tingkat 7-9)
            ['unit' => 'SMPIT Ulil Albab Gondangrejo', 'jenjang' => 'SMP', 'grade' => 7, 'name' => '7A', 'capacity' => 30],
            ['unit' => 'SMPIT Ulil Albab Gondangrejo', 'jenjang' => 'SMP', 'grade' => 8, 'name' => '8A', 'capacity' => 30],
            ['unit' => 'SMPIT Ulil Albab Gondangrejo', 'jenjang' => 'SMP', 'grade' => 9, 'name' => '9A', 'capacity' => 30],

            // PPTQ — SMP (Tingkat 7-9, Tahfidz)
            ['unit' => 'PPTQ Ulil Albab Gondangrejo', 'jenjang' => 'SMP', 'grade' => 7, 'name' => '7-Tahfidz', 'capacity' => 25],
            ['unit' => 'PPTQ Ulil Albab Gondangrejo', 'jenjang' => 'SMP', 'grade' => 8, 'name' => '8-Tahfidz', 'capacity' => 25],
            ['unit' => 'PPTQ Ulil Albab Gondangrejo', 'jenjang' => 'SMP', 'grade' => 9, 'name' => '9-Tahfidz', 'capacity' => 25],
        ];

        foreach ($academicYears as $year) {
            foreach ($classroomTemplates as $classroom) {
                $year->classrooms()->create($classroom);
            }
        }
    }
}
