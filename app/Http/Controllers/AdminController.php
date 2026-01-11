<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Student;
use App\Models\TuitionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $pendingInstructors = User::where('role', 'instructor')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $approvedInstructors = User::where('role', 'instructor')
            ->where('status', 'approved')
            ->with('subjects')
            ->orderBy('name')
            ->get();

        $inactiveInstructors = User::where('role', 'instructor')
            ->where('status', 'inactive')
            ->orderBy('name')
            ->get();

        $rejectedInstructors = User::where('role', 'instructor')
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->get();

        $subjects = Subject::orderBy('name')->get();
        $tuitionRequests = TuitionRequest::with(['instructor', 'student'])
            ->orderByDesc('requested_at')
            ->get();

        return view('admin.dashboard', [
            'pendingInstructors' => $pendingInstructors,
            'approvedInstructors' => $approvedInstructors,
            'inactiveInstructors' => $inactiveInstructors,
            'rejectedInstructors' => $rejectedInstructors,
            'subjects' => $subjects,
            'tuitionRequests' => $tuitionRequests,
        ]);
    }

    public function instructorsIndex(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $instructors = User::where('role', 'instructor')
            ->with(['subjects', 'classSessions'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.instructors.index', [
            'instructors' => $instructors,
            'days' => config('schedule.days'),
            'search' => $search,
        ]);
    }

    public function studentsIndex(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $students = Student::with(['instructor.subjects', 'classSessions.subject'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'days' => config('schedule.days'),
            'search' => $search,
        ]);
    }

    public function approve(User $user)
    {
        $this->ensureInstructor($user);
        $user->status = 'approved';
        $user->save();

        return back()->with('status', '강사 승인 완료');
    }

    public function reject(User $user)
    {
        $this->ensureInstructor($user);
        $user->status = 'rejected';
        $user->save();

        return back()->with('status', '강사 등록을 거절했습니다.');
    }

    public function deactivate(User $user)
    {
        $this->ensureInstructor($user);
        $user->status = 'inactive';
        $user->save();

        return back()->with('status', '강사를 비활성화했습니다.');
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:subjects,name'],
        ]);

        Subject::create($validated);

        return back()->with('status', '과목을 등록했습니다.');
    }

    public function completeTuitionRequest(TuitionRequest $tuitionRequest)
    {
        if ($tuitionRequest->status !== 'pending') {
            return back()->with('status', '이미 처리된 요청입니다.');
        }

        $tuitionRequest->status = 'completed';
        $tuitionRequest->processed_at = now();
        $tuitionRequest->save();

        return back()->with('status', '수업료 입금 처리를 완료했습니다.');
    }

    public function showInstructor(User $user)
    {
        $this->ensureInstructor($user);

        $user->load([
            'subjects',
            'students' => function ($query) {
                $query->orderBy('name');
            },
            'students.classSessions.subject',
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

        return view('admin.instructors.show', [
            'instructor' => $user,
            'sessionStudents' => $sessionStudents,
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
        $days = config('schedule.days');
        $timeSlots = $this->timeSlots();
        $sessions = $instructor->classSessions;
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
}
