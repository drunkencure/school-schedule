<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_students_scoped_to_current_academy(): void
    {
        $academyA = Academy::create(['name' => '학원 A']);
        $academyB = Academy::create(['name' => '학원 B']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'approved',
        ]);
        $instructor->academies()->attach($academyA->id, ['status' => 'approved']);
        $instructor->academies()->attach($academyB->id, ['status' => 'approved']);

        $subjectB = Subject::create([
            'academy_id' => $academyB->id,
            'name' => '국어',
        ]);

        $student = Student::create([
            'instructor_id' => $instructor->id,
            'name' => '수강생',
            'registered_at' => now()->toDateString(),
            'billing_cycle_count' => 1,
        ]);
        $student->academies()->attach($academyA->id);
        $student->academies()->attach($academyB->id);

        $weekday = (int) now()->dayOfWeekIso;
        $sessionB = ClassSession::create([
            'academy_id' => $academyB->id,
            'instructor_id' => $instructor->id,
            'subject_id' => $subjectB->id,
            'weekday' => $weekday,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_group' => false,
        ]);
        $sessionB->students()->attach($student->id);

        $response = $this->actingAs($instructor)
            ->withSession(['academy_id' => $academyA->id])
            ->get(route('instructor.dashboard'));

        $response->assertOk();
        $response->assertViewHas('pendingStudents', function ($pending) use ($student) {
            return $pending->contains('id', $student->id);
        });
    }
}
