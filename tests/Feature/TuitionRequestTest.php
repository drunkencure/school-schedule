<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\ClassSession;
use App\Models\LessonAttendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TuitionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TuitionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_cannot_request_tuition_when_pending_exists(): void
    {
        $academy = Academy::create(['name' => '학원']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'approved',
        ]);
        $instructor->academies()->attach($academy->id, ['status' => 'approved']);

        $subject = Subject::create([
            'academy_id' => $academy->id,
            'name' => '수학',
        ]);

        $student = Student::create([
            'instructor_id' => $instructor->id,
            'name' => '수강생',
            'registered_at' => now()->toDateString(),
            'billing_cycle_count' => 1,
        ]);
        $student->academies()->attach($academy->id);

        $weekday = (int) now()->dayOfWeekIso;
        $session = ClassSession::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'subject_id' => $subject->id,
            'weekday' => $weekday,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_group' => false,
        ]);
        $session->students()->attach($student->id);

        $lessonDate = now()->toDateString();
        LessonAttendance::create([
            'student_id' => $student->id,
            'class_session_id' => $session->id,
            'lesson_date' => $lessonDate,
        ]);

        TuitionRequest::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'lesson_count' => 1,
            'lesson_dates' => [$lessonDate],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->withSession(['academy_id' => $academy->id])
            ->post(route('calendar.tuition.request'), [
                'student_id' => $student->id,
            ]);

        $response->assertSessionHasErrors('student_id');
        $this->assertSame(1, TuitionRequest::count());
    }
}
