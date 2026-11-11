<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_student_statistics_page_loads_with_students_and_classroom_gender_stats(): void
    {
        $response = $this->actingAs($this->user)->get('/statistics');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Statistics/StudentStatistics')
            ->has('students')
            ->has('studentsPerClass')
            ->has('selectedAcademicYear')
            ->where('selectedAcademicYear.semester', 'Semua')
            ->has('currentAcademicYear')
            ->has('availableAcademicYears')
            ->has('allAcademicYears')
        );
    }

    public function test_student_statistics_page_filters_by_academic_year_and_semester(): void
    {
        $response = $this->actingAs($this->user)->get('/statistics?academic_year=2024/2025&semester=Semua');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Statistics/StudentStatistics')
            ->where('selectedAcademicYear.name', '2024/2025')
            ->where('selectedAcademicYear.semester', 'Semua')
            ->has('availableAcademicYears')
            ->has('allAcademicYears')
        );
    }
}
