<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_student_can_toggle_past_attendance(): void
    {
        $academy = Academy::create(['name' => '학원']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'approved',
        ]);
        $instructor->academies()->attach($academy->id, ['status' => 'approved']);

        $subject = Subject::create([
            'academy_id' => $academy->id,
            'name' => '영어',
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

        $student->delete();

        $lessonDate = now()->toDateString();

        $response = $this->actingAs($instructor)
            ->withSession(['academy_id' => $academy->id])
            ->post(route('calendar.attendance.toggle'), [
                'student_id' => $student->id,
                'class_session_id' => $session->id,
                'lesson_date' => $lessonDate,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lesson_attendances', [
            'student_id' => $student->id,
            'class_session_id' => $session->id,
            'lesson_date' => $lessonDate,
        ]);
    }
}
