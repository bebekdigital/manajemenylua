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
        );
    }
}
