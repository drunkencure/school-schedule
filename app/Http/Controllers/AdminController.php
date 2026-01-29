<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\ClassSession;
use App\Models\LessonAttendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TuitionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $academyId = session('academy_id');
        $pendingInstructors = User::where('role', 'instructor')
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'pending');
            })
            ->orderBy('created_at')
            ->get();

        $approvedInstructors = User::where('role', 'instructor')
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'approved');
            })
            ->with(['subjects' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            }])
            ->orderBy('name')
            ->get();

        $inactiveInstructors = User::where('role', 'instructor')
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'inactive');
            })
            ->orderBy('name')
            ->get();

        $rejectedInstructors = User::where('role', 'instructor')
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'rejected');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $subjects = Subject::where('academy_id', $academyId)->orderBy('name')->get();
        $tuitionRequests = TuitionRequest::with(['instructor', 'student'])
            ->whereHas('student.academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId);
            })
            ->orderByDesc('requested_at')
            ->get();

        return view('admin.dashboard', [
            'academies' => Academy::orderBy('name')->get(),
            'pendingInstructors' => $pendingInstructors,
            'approvedInstructors' => $approvedInstructors,
            'inactiveInstructors' => $inactiveInstructors,
            'rejectedInstructors' => $rejectedInstructors,
            'subjects' => $subjects,
            'tuitionRequests' => $tuitionRequests,
        ]);
    }

    public function scheduleIndex()
    {
        return view('admin.schedule.index', $this->buildScheduleOverview());
    }

    public function instructorsIndex(Request $request)
    {
        $academyId = session('academy_id');
        $search = trim((string) $request->query('search', ''));

        $instructors = User::where('role', 'instructor')
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId);
            })
            ->with(['subjects' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            }, 'classSessions' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            }])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.instructors.index', [
            'instructors' => $instructors,
            'days' => config('schedule.days'),
            'subjects' => Subject::where('academy_id', $academyId)->orderBy('name')->get(),
            'availableInstructors' => User::where('role', 'instructor')
                ->whereDoesntHave('academies', function ($query) use ($academyId) {
                    $query->where('academies.id', $academyId);
                })
                ->orderBy('name')
                ->get(),
            'search' => $search,
        ]);
    }

    public function storeInstructor(Request $request)
    {
        $academyId = session('academy_id');
        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:50', 'unique:users,login_id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')->where('academy_id', $academyId)],
        ]);

        $instructor = User::create([
            'login_id' => $validated['login_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'instructor',
            'status' => 'approved',
        ]);

        $instructor->subjects()->sync($validated['subjects']);
        $instructor->academies()->syncWithoutDetaching([
            $academyId => ['status' => 'approved'],
        ]);

        return back()->with('status', '강사를 등록했습니다.');
    }

    public function attachInstructor(Request $request)
    {
        $academyId = session('academy_id');
        $validated = $request->validate([
            'instructor_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'instructor');
                }),
            ],
        ]);

        $instructor = User::where('role', 'instructor')
            ->whereKey($validated['instructor_id'])
            ->firstOrFail();

        $instructor->academies()->syncWithoutDetaching([
            $academyId => ['status' => 'approved'],
        ]);
        if ($instructor->status !== 'approved') {
            $instructor->status = 'approved';
            $instructor->save();
        }

        return back()->with('status', '강사를 학원에 연결했습니다.');
    }

    public function studentsIndex(Request $request)
    {
        $academyId = session('academy_id');
        $search = trim((string) $request->query('search', ''));

        $students = Student::with([
            'instructor.subjects' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            },
            'classSessions' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId)->with('subject');
            },
        ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId);
            })
            ->orderBy('name')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'days' => config('schedule.days'),
            'instructors' => User::where('role', 'instructor')
                ->whereHas('academies', function ($query) use ($academyId) {
                    $query->where('academies.id', $academyId)
                        ->where('academy_user.status', 'approved');
                })
                ->with(['subjects' => function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                }])
                ->orderBy('name')
                ->get(),
            'search' => $search,
        ]);
    }

    public function storeStudent(Request $request)
    {
        $academyId = session('academy_id');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'registered_at' => ['required', 'date'],
            'billing_cycle_count' => ['required', 'integer', 'min:1', 'max:50'],
            'instructor_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'instructor');
                }),
            ],
        ]);

        User::where('role', 'instructor')
            ->whereKey($validated['instructor_id'])
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'approved');
            })
            ->firstOrFail();

        $student = Student::create([
            'instructor_id' => $validated['instructor_id'],
            'name' => $validated['name'],
            'registered_at' => $validated['registered_at'],
            'billing_cycle_count' => $validated['billing_cycle_count'],
        ]);
        $student->academies()->syncWithoutDetaching([$academyId]);

        return back()->with('status', '수강생을 등록했습니다.');
    }

    public function approve(User $user)
    {
        $this->ensureInstructor($user);
        $academyId = session('academy_id');
        $user->academies()->syncWithoutDetaching([$academyId]);
        $user->academies()->updateExistingPivot($academyId, ['status' => 'approved']);
        if ($user->status !== 'approved') {
            $user->status = 'approved';
            $user->save();
        }

        return back()->with('status', '강사 승인 완료');
    }

    public function reject(User $user)
    {
        $this->ensureInstructor($user);
        $academyId = session('academy_id');
        $user->academies()->syncWithoutDetaching([$academyId]);
        $user->academies()->updateExistingPivot($academyId, ['status' => 'rejected']);

        return back()->with('status', '강사 등록을 거절했습니다.');
    }

    public function deactivate(User $user)
    {
        $this->ensureInstructor($user);
        $academyId = session('academy_id');
        $user->academies()->syncWithoutDetaching([$academyId]);
        $user->academies()->updateExistingPivot($academyId, ['status' => 'inactive']);

        return back()->with('status', '강사를 비활성화했습니다.');
    }

    public function storeSubject(Request $request)
    {
        $academyId = session('academy_id');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('subjects', 'name')->where('academy_id', $academyId)],
        ]);

        Subject::create([
            'academy_id' => $academyId,
            'name' => $validated['name'],
        ]);

        return back()->with('status', '과목을 등록했습니다.');
    }

    public function completeTuitionRequest(TuitionRequest $tuitionRequest)
    {
        $academyId = session('academy_id');
        if (! $tuitionRequest->student || ! $tuitionRequest->student->academies()->whereKey($academyId)->exists()) {
            abort(404);
        }
        if ($tuitionRequest->status !== 'pending') {
            return back()->with('status', '이미 처리된 요청입니다.');
        }

        $tuitionRequest->status = 'completed';
        $tuitionRequest->processed_at = now();
        $tuitionRequest->save();

        return back()->with('status', '수업료 입금 처리를 완료했습니다.');
    }

    public function storeAcademy(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:500'],
        ]);

        Academy::create($validated);

        return back()->with('status', '학원을 등록했습니다.');
    }

    public function destroyAcademy(Request $request, Academy $academy): RedirectResponse
    {
        $academyId = $academy->id;

        DB::transaction(function () use ($academy, $academyId): void {
            $studentIds = DB::table('academy_student')
                ->where('academy_id', $academyId)
                ->pluck('student_id');
            $instructorIds = DB::table('academy_user')
                ->join('users', 'academy_user.user_id', '=', 'users.id')
                ->where('academy_user.academy_id', $academyId)
                ->where('users.role', 'instructor')
                ->pluck('users.id');

            if ($studentIds->isNotEmpty()) {
                Student::withTrashed()
                    ->whereIn('id', $studentIds)
                    ->forceDelete();
            }

            if ($instructorIds->isNotEmpty()) {
                User::whereIn('id', $instructorIds)->delete();
            }

            $academy->delete();
        });

        if ((int) $request->session()->get('academy_id') === $academyId) {
            $request->session()->forget('academy_id');
        }

        return back()->with('status', '학원을 삭제했습니다.');
    }

    public function showInstructor(User $user)
    {
        $this->ensureInstructor($user);
        $academyId = session('academy_id');

        if (! $user->academies()->whereKey($academyId)->exists()) {
            abort(404);
        }

        $user->load([
            'subjects' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            },
            'students' => function ($query) use ($academyId) {
                $query->whereHas('academies', function ($subQuery) use ($academyId) {
                    $subQuery->where('academies.id', $academyId);
                })->orderBy('name');
            },
            'students.classSessions.subject',
            'classSessions' => function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            },
            'classSessions.students.classSessions.subject',
            'classSessions.subject',
        ]);

        $gridData = $this->buildGrid($user);
        $sessionStudents = $user->classSessions
            ->flatMap(function ($session) {
                return $session->students;
            })
            ->unique('id')
            ->sortBy('name')
            ->values();
        $academyStatus = $user->academies()->whereKey($academyId)->value('academy_user.status');

        return view('admin.instructors.show', [
            'instructor' => $user,
            'sessionStudents' => $sessionStudents,
            'academyStatus' => $academyStatus,
            ...$gridData,
        ]);
    }

    private function ensureInstructor(User $user): void
    {
        if ($user->role !== 'instructor') {
            abort(404);
        }
    }

    private function buildGrid(User $instructor): array
    {
        $academyId = session('academy_id');
        $days = config('schedule.days');
        $timeSlots = $this->timeSlots();
        $sessions = $instructor->classSessions()->where('academy_id', $academyId)->get();
        $grid = [];

        foreach ($sessions as $session) {
            $timeKey = Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i');
            $grid[$session->weekday][$timeKey] = $session;
        }

        return [
            'days' => $days,
            'timeSlots' => $timeSlots,
            'grid' => $grid,
            'sessions' => $sessions,
        ];
    }

    private function timeSlots(): array
    {
        $start = (int) config('schedule.start_hour');
        $end = (int) config('schedule.end_hour');
        $slots = [];

        for ($hour = $start; $hour < $end; $hour++) {
            $slots[] = Carbon::createFromTime($hour)->format('H:i');
        }

        return $slots;
    }

    private function buildScheduleOverview(): array
    {
        $academyId = session('academy_id');
        $days = config('schedule.days');
        $timeSlots = $this->timeSlots();
        $todayKey = Carbon::now()->isoWeekday();
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = (clone $weekStart)->addDays(6);
        $weekRange = sprintf('%s ~ %s', $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
        $showYear = $weekStart->year !== $weekEnd->year;
        $weekDates = [];

        foreach ($days as $dayKey => $dayLabel) {
            $weekDates[$dayKey] = $weekStart->copy()->addDays($dayKey - 1);
        }

        $approvedInstructors = User::where('role', 'instructor')
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'approved');
            })
            ->orderBy('name')
            ->get();

        $scheduleSessions = ClassSession::with(['instructor', 'subject', 'students'])
            ->where('academy_id', $academyId)
            ->whereHas('instructor', function ($query) {
                $query->where('role', 'instructor')
                    ->where('status', 'approved');
            })
            ->whereHas('instructor.academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId)
                    ->where('academy_user.status', 'approved');
            })
            ->get();

        $scheduleGrid = [];
        $attendanceMap = [];
        $palette = [
            'instructor-color-1',
            'instructor-color-2',
            'instructor-color-3',
            'instructor-color-4',
            'instructor-color-5',
            'instructor-color-6',
            'instructor-color-7',
            'instructor-color-8',
        ];
        $instructorColors = [];

        foreach ($scheduleSessions as $session) {
            $timeKey = Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i');
            $scheduleGrid[$session->weekday][$timeKey][] = $session;
        }

        foreach ($approvedInstructors->values() as $index => $instructor) {
            $instructorColors[$instructor->id] = $palette[$index % count($palette)];
        }

        if ($scheduleSessions->isNotEmpty()) {
            $attendanceRecords = LessonAttendance::query()
                ->whereBetween('lesson_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->whereIn('class_session_id', $scheduleSessions->pluck('id'))
                ->get(['class_session_id', 'lesson_date']);

            foreach ($attendanceRecords as $attendance) {
                $dateKey = $attendance->lesson_date->toDateString();
                $attendanceMap[$attendance->class_session_id][$dateKey] = true;
            }
        }

        return [
            'days' => $days,
            'timeSlots' => $timeSlots,
            'todayKey' => $todayKey,
            'weekRange' => $weekRange,
            'weekDates' => $weekDates,
            'showYear' => $showYear,
            'scheduleGrid' => $scheduleGrid,
            'scheduleSessions' => $scheduleSessions,
            'attendanceMap' => $attendanceMap,
            'approvedInstructors' => $approvedInstructors,
            'instructorColors' => $instructorColors,
            'approvedInstructorsCount' => $approvedInstructors->count(),
            'scheduleSessionCount' => $scheduleSessions->count(),
            'todaySessionCount' => $scheduleSessions->where('weekday', $todayKey)->count(),
        ];
    }
}
