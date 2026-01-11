<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function assignStudentToSession(
        User $instructor,
        Student $student,
        int $subjectId,
        int $weekday,
        string $startTime,
        bool $confirmGroup
    ): ClassSession {
        $startTimeValue = Carbon::createFromFormat('H:i', $startTime)->format('H:i:s');

        $existing = ClassSession::where('instructor_id', $instructor->id)
            ->where('weekday', $weekday)
            ->where('start_time', $startTimeValue)
            ->first();

        if ($existing) {
            $activeStudents = $existing->students()->count();

            if ($activeStudents === 0) {
                if ($existing->subject_id !== $subjectId) {
                    throw ValidationException::withMessages([
                        'subject_id' => '해당 시간대에는 다른 과목 수업이 있습니다.',
                    ]);
                }

                if ($existing->is_group) {
                    $existing->is_group = false;
                    $existing->save();
                }

                $existing->students()->syncWithoutDetaching([$student->id]);

                return $existing;
            }

            if (! $confirmGroup) {
                throw ValidationException::withMessages([
                    'start_time' => '이미 그 시간대 등록된 학생이 있습니다.',
                ]);
            }

            if ($existing->subject_id !== $subjectId) {
                throw ValidationException::withMessages([
                    'subject_id' => '해당 시간대에는 다른 과목 수업이 있습니다.',
                ]);
            }

            if (! $existing->is_group) {
                $existing->is_group = true;
                $existing->save();
            }

            $existing->students()->syncWithoutDetaching([$student->id]);

            return $existing;
        }

        $endTime = Carbon::createFromFormat('H:i', $startTime)
            ->addHour()
            ->format('H:i:s');

        $session = ClassSession::create([
            'instructor_id' => $instructor->id,
            'subject_id' => $subjectId,
            'weekday' => $weekday,
            'start_time' => $startTimeValue,
            'end_time' => $endTime,
            'is_group' => false,
        ]);

        $session->students()->syncWithoutDetaching([$student->id]);

        return $session;
    }
}
