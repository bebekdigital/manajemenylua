<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentAcademicYearTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_students_page_shows_active_academic_year_by_default(): void
    {
        $response = $this->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students')
            ->has('students', 20)
            ->where('selectedAcademicYear.name', '2025/2026')
            ->where('selectedAcademicYear.semester', 'Ganjil')
            ->has('academicYears', 3)
        );
    }

    public function test_can_switch_academic_year_and_filter_students(): void
    {
        $pastYear = AcademicYear::where('name', '2024/2025')->where('semester', 'Genap')->firstOrFail();

        // 1. Post to switch academic year
        $switchResponse = $this->post('/academic-years/switch', [
            'academic_year_id' => $pastYear->id,
        ]);

        $switchResponse->assertRedirect();
        $switchResponse->assertSessionHas('selected_academic_year_id', $pastYear->id);

        // 2. Visit students page with session
        $studentsResponse = $this->get('/students');

        $studentsResponse->assertStatus(200);
        $studentsResponse->assertInertia(fn (Assert $page) => $page
            ->component('Students')
            ->has('students', 10)
            ->where('selectedAcademicYear.name', '2024/2025')
            ->where('selectedAcademicYear.semester', 'Genap')
        );
    }

    public function test_empty_academic_year_shows_zero_students(): void
    {
        $emptyYear = AcademicYear::where('name', '2024/2025')->where('semester', 'Ganjil')->firstOrFail();

        $response = $this->withSession(['selected_academic_year_id' => $emptyYear->id])
            ->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students')
            ->has('students', 0)
            ->where('selectedAcademicYear.name', '2024/2025')
            ->where('selectedAcademicYear.semester', 'Ganjil')
        );
    }
}
