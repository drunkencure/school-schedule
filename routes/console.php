<?php

use App\Models\Academy;
use App\Models\ClassSession;
use App\Models\LessonAttendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('command:sample-data {--academies=3} {--instructors-min=5} {--instructors-max=6} {--students-min=50} {--students-max=80} {--reset}', function () {
    $academyCount = max(1, (int) $this->option('academies'));
    if ($academyCount > 3) {
        $this->warn('Academies capped to 3.');
        $academyCount = 3;
    }
    $instructorMin = max(1, (int) $this->option('instructors-min'));
    $instructorMax = max($instructorMin, (int) $this->option('instructors-max'));
    $studentMin = max(1, (int) $this->option('students-min'));
    $studentMax = max($studentMin, (int) $this->option('students-max'));
    $prefix = 'sample_instructor_';
    $academyMemo = 'sample-data';
    $now = now();
    $startDate = $now->copy()->subYear()->startOfDay();

    if ($this->option('reset')) {
        if (! $this->confirm('Delete existing sample data and recreate?')) {
            $this->comment('Canceled.');
            return;
        }

        $sampleAcademyIds = Academy::where('memo', $academyMemo)->pluck('id');
        $sampleInstructorIds = User::where('login_id', 'like', $prefix.'%')->pluck('id');
        $sampleStudentIds = Student::whereIn('instructor_id', $sampleInstructorIds)->pluck('id');
        $sampleSessionIds = ClassSession::whereIn('instructor_id', $sampleInstructorIds)
            ->orWhereIn('academy_id', $sampleAcademyIds)
            ->pluck('id');

        LessonAttendance::whereIn('student_id', $sampleStudentIds)->delete();
        DB::table('class_session_student')
            ->whereIn('student_id', $sampleStudentIds)
            ->orWhereIn('class_session_id', $sampleSessionIds)
            ->delete();
        DB::table('instructor_subject')->whereIn('user_id', $sampleInstructorIds)->delete();
        DB::table('academy_user')
            ->whereIn('user_id', $sampleInstructorIds)
            ->orWhereIn('academy_id', $sampleAcademyIds)
            ->delete();
        DB::table('academy_student')
            ->whereIn('student_id', $sampleStudentIds)
            ->orWhereIn('academy_id', $sampleAcademyIds)
            ->delete();
        ClassSession::whereIn('id', $sampleSessionIds)->delete();
        Student::whereIn('id', $sampleStudentIds)->delete();
        Subject::whereIn('academy_id', $sampleAcademyIds)->delete();
        User::whereIn('id', $sampleInstructorIds)->delete();
        Academy::whereIn('id', $sampleAcademyIds)->delete();

        $this->comment('Removed existing sample data.');
    } else {
        $existingSample = User::where('login_id', 'like', $prefix.'%')->exists()
            || Academy::where('memo', $academyMemo)->exists();
        if ($existingSample) {
            $this->warn('Sample instructors already exist. Use --reset to recreate.');
            return;
        }
    }

    $subjectNames = [
        '일렉기타',
        '어쿠스틱기타',
        '베이스기타',
        '드럼',
        '보컬',
        '피아노',
        '미디',
    ];

    $academyConfigs = [];
    for ($i = 1; $i <= $academyCount; $i++) {
        $academyConfigs[] = [
            'index' => $i,
            'instructors' => random_int($instructorMin, $instructorMax),
            'students' => random_int($studentMin, $studentMax),
        ];
    }

    $randomDate = function (Carbon $start, Carbon $end): Carbon {
        $startTimestamp = $start->timestamp;
        $endTimestamp = $end->timestamp;
        $randomTimestamp = random_int($startTimestamp, $endTimestamp);

        return Carbon::createFromTimestamp($randomTimestamp);
    };

    $familyNames = ['김', '이', '박', '최', '정', '강', '조', '윤', '장', '임', '유', '소', '지', '양', '부'];
    $givenNames = [
        '민수',
        '서연',
        '지훈',
        '하준',
        '서준',
        '예은',
        '지은',
        '현우',
        '지민',
        '수빈',
        '도윤',
        '유진',
        '지아',
        '윤서',
        '지원',
        '은우',
        '소연',
        '민준',
        '시우',
        '다현',
        '준호',
        '세영',
        '선우',
        '혜진',
        '태윤',
        '영준',
        '선택',
        '세진',
        '동수',
    ];

    $namePool = [];
    foreach ($familyNames as $familyName) {
        foreach ($givenNames as $givenName) {
            $namePool[] = $familyName.$givenName;
        }
    }
    shuffle($namePool);
    $totalInstructors = array_sum(array_column($academyConfigs, 'instructors'));
    $totalStudents = array_sum(array_column($academyConfigs, 'students'));
    $requiredNames = $totalInstructors + $totalStudents;
    if ($requiredNames > count($namePool)) {
        $this->warn('Name pool is smaller than requested. Some names may repeat.');
    }

    $randomName = function () use ($familyNames, $givenNames): string {
        return $familyNames[array_rand($familyNames)].$givenNames[array_rand($givenNames)];
    };

    $instructorNames = array_slice($namePool, 0, $totalInstructors);
    $studentNames = array_slice($namePool, $totalInstructors, $totalStudents);

    $academies = collect();
    $instructors = collect();
    $sessions = collect();
    $days = array_keys(config('schedule.days'));
    $startHour = (int) config('schedule.start_hour');
    $endHour = (int) config('schedule.end_hour');
    $hourSlots = range($startHour, $endHour - 1);
    $allSlots = [];

    foreach ($days as $weekday) {
        foreach ($hourSlots as $hour) {
            $allSlots[] = [
                'weekday' => $weekday,
                'start_time' => sprintf('%02d:00:00', $hour),
            ];
        }
    }

    $subjectIdsByInstructor = [];
    $availableSlotsByInstructor = [];
    $sessionsByInstructor = [];
    $academyByInstructor = [];
    $instructorsByAcademy = [];
    $academyStudentsTarget = [];

    $instructorIndex = 0;
    foreach ($academyConfigs as $config) {
        $academy = Academy::create([
            'name' => sprintf('샘플 학원 %d', $config['index']),
            'address' => sprintf('샘플 주소 %d', $config['index']),
            'memo' => $academyMemo,
        ]);
        $academies->push($academy);
        $academyStudentsTarget[$academy->id] = $config['students'];

        $subjects = collect($subjectNames)->map(function ($name) use ($academy) {
            return Subject::firstOrCreate([
                'academy_id' => $academy->id,
                'name' => $name,
            ]);
        });

        $instructorsByAcademy[$academy->id] = collect();

        for ($i = 1; $i <= $config['instructors']; $i++) {
            $instructorIndex++;
            $loginId = sprintf('%s%02d', $prefix, $instructorIndex);
            $email = sprintf('%s%02d@sample.test', $prefix, $instructorIndex);
            $createdAt = $randomDate($startDate, $now->copy()->subDays(7));

            $instructorName = $instructorNames[$instructorIndex - 1] ?? $randomName();
            $instructor = User::create([
                'login_id' => $loginId,
                'name' => $instructorName,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'instructor',
                'status' => 'approved',
            ]);

            $instructor->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $instructor->academies()->syncWithoutDetaching([
                $academy->id => ['status' => 'approved'],
            ]);

            $subjectPickCount = random_int(1, min(3, $subjects->count()));
            $pickedSubjects = $subjects->random($subjectPickCount);
            $pickedSubjects = $pickedSubjects instanceof Subject ? collect([$pickedSubjects]) : $pickedSubjects;
            $subjectIds = $pickedSubjects->pluck('id')->all();
            $instructor->subjects()->syncWithoutDetaching($subjectIds);
            $instructor->load('subjects');

            $subjectIdsByInstructor[$instructor->id] = $subjectIds;
            $academyByInstructor[$instructor->id] = $academy->id;
            $slots = $allSlots;
            shuffle($slots);
            $availableSlotsByInstructor[$instructor->id] = $slots;
            $sessionsByInstructor[$instructor->id] = [];

            $instructors->push($instructor);
            $instructorsByAcademy[$academy->id]->push($instructor);
        }
    }

    $sessionStudentCounts = [];
    $studentSessionPairs = [];
    $studentIndex = 0;

    $createSessionForInstructor = function (User $instructor) use (
        &$availableSlotsByInstructor,
        &$sessionsByInstructor,
        &$sessions,
        $subjectIdsByInstructor,
        $academyByInstructor,
        $randomDate,
        $now
    ): ?ClassSession {
        $slots = &$availableSlotsByInstructor[$instructor->id];
        if (empty($slots)) {
            return null;
        }

        $slot = array_pop($slots);
        $subjectId = Arr::random($subjectIdsByInstructor[$instructor->id]);
        $sessionCreatedAt = $randomDate($instructor->created_at, $now->copy()->subDays(7));

        $session = ClassSession::create([
            'academy_id' => $academyByInstructor[$instructor->id],
            'instructor_id' => $instructor->id,
            'subject_id' => $subjectId,
            'weekday' => $slot['weekday'],
            'start_time' => $slot['start_time'],
            'end_time' => Carbon::createFromFormat('H:i:s', $slot['start_time'])->addHour()->format('H:i:s'),
            'is_group' => false,
        ]);

        $session->forceFill([
            'created_at' => $sessionCreatedAt,
            'updated_at' => $sessionCreatedAt,
        ])->saveQuietly();

        $sessionsByInstructor[$instructor->id][] = $session;
        $sessions->push($session);

        return $session;
    };

    $assignStudentToSession = function (
        Student $student,
        User $instructor,
        Carbon $registeredAt,
        array $excludedSessionIds = []
    ) use (
        &$sessionsByInstructor,
        &$sessionStudentCounts,
        &$studentSessionPairs,
        $createSessionForInstructor
    ): ?int {
        $session = $createSessionForInstructor($instructor);

        if (! $session) {
            $candidates = collect($sessionsByInstructor[$instructor->id] ?? [])
                ->reject(function ($existing) use ($excludedSessionIds) {
                    return in_array($existing->id, $excludedSessionIds, true);
                });

            if ($candidates->isEmpty()) {
                return null;
            }

            $session = $candidates->sortBy(function ($existing) use ($sessionStudentCounts) {
                return $sessionStudentCounts[$existing->id] ?? 0;
            })->first();
        }

        $pivotDate = $registeredAt->copy()->addDays(random_int(0, 14));
        DB::table('class_session_student')->insert([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'created_at' => $pivotDate,
            'updated_at' => $pivotDate,
        ]);
        $sessionStudentCounts[$session->id] = ($sessionStudentCounts[$session->id] ?? 0) + 1;
        $studentSessionPairs[] = [$student->id, $session->id, $registeredAt];

        return $session->id;
    };

    foreach ($academies as $academy) {
        $academyInstructors = $instructorsByAcademy[$academy->id] ?? collect();
        if ($academyInstructors->isEmpty()) {
            continue;
        }

        $studentTarget = $academyStudentsTarget[$academy->id] ?? 0;
        $studentsPerInstructor = intdiv($studentTarget, $academyInstructors->count());
        $remainderStudents = $studentTarget % $academyInstructors->count();

        foreach ($academyInstructors->values() as $index => $instructor) {
            $count = $studentsPerInstructor + ($index < $remainderStudents ? 1 : 0);

            for ($i = 0; $i < $count; $i++) {
                $registeredAt = $randomDate($instructor->created_at, $now->copy()->subDays(7))->startOfDay();
                $studentName = $studentNames[$studentIndex] ?? $randomName();
                $studentIndex++;

                $student = Student::create([
                    'instructor_id' => $instructor->id,
                    'name' => $studentName,
                    'registered_at' => $registeredAt->toDateString(),
                    'billing_cycle_count' => random_int(4, 12),
                    'last_billed_lesson_date' => null,
                ]);

                $student->forceFill([
                    'created_at' => $registeredAt,
                    'updated_at' => $registeredAt,
                ])->saveQuietly();

                $student->academies()->syncWithoutDetaching([$academy->id]);

                $assignedSessionIds = [];
                $primarySessionId = $assignStudentToSession($student, $instructor, $registeredAt);
                if ($primarySessionId) {
                    $assignedSessionIds[] = $primarySessionId;
                }

                if (random_int(1, 100) <= 15) {
                    $extraInstructor = $instructor;
                    if ($academyInstructors->count() > 1 && random_int(1, 100) <= 60) {
                        $extraInstructor = $academyInstructors->where('id', '!=', $instructor->id)->random();
                    }

                    $assignStudentToSession($student, $extraInstructor, $registeredAt, $assignedSessionIds);
                }
            }
        }
    }

    $sessionLookup = $sessions->keyBy('id');

    foreach ($sessionStudentCounts as $sessionId => $count) {
        if ($count > 1) {
            ClassSession::where('id', $sessionId)->update(['is_group' => true]);
        }
    }

    $attendanceRows = [];
    $today = $now->copy()->startOfDay();
    $nextSessionDate = function (Carbon $baseDate, int $weekday): Carbon {
        $base = $baseDate->copy()->startOfDay();
        $currentWeekday = (int) $base->dayOfWeekIso;
        $offset = ($weekday - $currentWeekday + 7) % 7;

        return $base->copy()->addDays($offset);
    };

    foreach ($studentSessionPairs as [$studentId, $sessionId, $registeredAt]) {
        $session = $sessionLookup->get($sessionId);
        if (! $session) {
            continue;
        }

        $firstDate = $nextSessionDate($registeredAt, (int) $session->weekday);
        $cursor = $firstDate->copy();

        while ($cursor->lte($today)) {
            if (random_int(1, 100) <= 75) {
                $attendanceRows[] = [
                    'student_id' => $studentId,
                    'class_session_id' => $sessionId,
                    'lesson_date' => $cursor->toDateString(),
                    'created_at' => $cursor->copy()->addHours(18),
                    'updated_at' => $cursor->copy()->addHours(18),
                ];
            }
            $cursor->addWeek();
        }
    }

    foreach (array_chunk($attendanceRows, 500) as $chunk) {
        DB::table('lesson_attendances')->insert($chunk);
    }

    $this->info('Sample data seeded.');
})->purpose('Seed sample academies, instructors, students, sessions, and attendance data.');
