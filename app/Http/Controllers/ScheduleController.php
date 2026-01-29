<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Student;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function dashboard()
    {
        $instructor = Auth::user();
        $academyId = session('academy_id');
        $gridData = $this->buildGrid($instructor);
        $pendingStudents = $instructor->students()
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId);
            })
            ->whereDoesntHave('classSessions', function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            })
            ->orderBy('name')
            ->get();

        return view('instructor.dashboard', [
            ...$gridData,
            'pendingStudents' => $pendingStudents,
        ]);
    }

    public function index(Request $request)
    {
        $instructor = Auth::user();
        $academyId = session('academy_id');
        $gridData = $this->buildGrid($instructor);
        $selectedStudentId = $request->query('student_id');
        if ($selectedStudentId) {
            $selectedStudentId = $instructor->students()
                ->whereHas('academies', function ($query) use ($academyId) {
                    $query->where('academies.id', $academyId);
                })
                ->where('id', $selectedStudentId)
                ->value('id');
        }

        return view('instructor.schedule.index', [
            ...$gridData,
            'students' => $instructor->students()
                ->whereHas('academies', function ($query) use ($academyId) {
                    $query->where('academies.id', $academyId);
                })
                ->orderBy('name')
                ->get(),
            'subjects' => $instructor->subjects()
                ->where('academy_id', $academyId)
                ->orderBy('name')
                ->get(),
            'selectedStudentId' => $selectedStudentId,
        ]);
    }

    public function store(Request $request, ScheduleService $scheduleService)
    {
        $instructor = Auth::user();
        $academyId = session('academy_id');
        $timeSlots = $this->timeSlots();

        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('instructor_id', $instructor->id),
            ],
            'subject_id' => [
                'required',
                Rule::exists('instructor_subject', 'subject_id')->where('user_id', $instructor->id),
            ],
            'weekday' => ['required', Rule::in(array_keys(config('schedule.days')))],
            'start_time' => ['required', Rule::in($timeSlots)],
            'confirm_group' => ['nullable', 'boolean'],
        ]);

        $student = Student::where('instructor_id', $instructor->id)
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId);
            })
            ->findOrFail($validated['student_id']);

        $subjectAllowed = $instructor->subjects()
            ->where('subjects.id', $validated['subject_id'])
            ->where('academy_id', $academyId)
            ->exists();
        if (! $subjectAllowed) {
            return back()->withErrors(['subject_id' => '선택한 과목은 현재 학원에 속하지 않습니다.']);
        }

        $scheduleService->assignStudentToSession(
            $instructor,
            $student,
            (int) $validated['subject_id'],
            (int) $validated['weekday'],
            $validated['start_time'],
            (bool) ($validated['confirm_group'] ?? false),
            $academyId
        );

        return redirect()->route('schedule.index')->with('status', '시간표를 등록했습니다.');
    }

    public function move(Request $request, ClassSession $classSession)
    {
        $instructor = Auth::user();
        $this->authorizeSession($classSession, $instructor->id);

        return $this->updateSessionTime($request, $classSession, $instructor->id);
    }

    public function moveByForm(Request $request)
    {
        $instructor = Auth::user();

        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        $classSession = ClassSession::findOrFail($validated['session_id']);
        $this->authorizeSession($classSession, $instructor->id);

        return $this->updateSessionTime($request, $classSession, $instructor->id);
    }

    public function destroy(Request $request, ClassSession $classSession)
    {
        $this->authorizeSession($classSession, Auth::id());
        $classSession->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('schedule.index')->with('status', '수업을 삭제했습니다.');
    }

    private function authorizeSession(ClassSession $classSession, int $instructorId): void
    {
        $academyId = session('academy_id');
        if ($classSession->instructor_id !== $instructorId
            || (int) $classSession->academy_id !== (int) $academyId) {
            abort(403);
        }
    }

    private function buildGrid($instructor): array
    {
        $academyId = session('academy_id');
        $days = config('schedule.days');
        $timeSlots = $this->timeSlots();

        $sessions = ClassSession::with(['students', 'subject'])
            ->where('instructor_id', $instructor->id)
            ->where('academy_id', $academyId)
            ->whereHas('students')
            ->get();

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

    private function updateSessionTime(Request $request, ClassSession $classSession, int $instructorId)
    {
        $academyId = session('academy_id');
        $timeSlots = $this->timeSlots();

        $validated = $request->validate([
            'weekday' => ['required', Rule::in(array_keys(config('schedule.days')))],
            'start_time' => ['required', Rule::in($timeSlots)],
        ]);

        $startTimeValue = Carbon::createFromFormat('H:i', $validated['start_time'])->format('H:i:s');

        $conflict = ClassSession::where('academy_id', $academyId)
            ->where('instructor_id', $instructorId)
            ->where('weekday', $validated['weekday'])
            ->where('start_time', $startTimeValue)
            ->where('id', '!=', $classSession->id)
            ->exists();

        if ($conflict) {
            return $this->moveErrorResponse($request, '다른 수업이 있는 시간대에는 이동할 수 없습니다.');
        }

        $classSession->update([
            'weekday' => (int) $validated['weekday'],
            'start_time' => $startTimeValue,
            'end_time' => Carbon::createFromFormat('H:i', $validated['start_time'])
                ->addHour()
                ->format('H:i:s'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('schedule.index')->with('status', '시간표를 수정했습니다.');
    }

    private function moveErrorResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['start_time' => $message]);
    }
}
