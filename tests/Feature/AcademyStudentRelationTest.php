<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyStudentRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_belong_to_multiple_academies(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'approved',
        ]);
        $student = Student::create([
            'instructor_id' => $instructor->id,
            'name' => '수강생',
        ]);
        $academyA = Academy::create(['name' => '학원 A']);
        $academyB = Academy::create(['name' => '학원 B']);

        $student->academies()->attach($academyA->id);
        $student->academies()->attach($academyB->id);

        $student->refresh();

        $this->assertCount(2, $student->academies);
    }
}
