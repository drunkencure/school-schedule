<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index()
    {
        $instructor = Auth::user();
        $academyId = session('academy_id');
        $students = Student::with(['classSessions' => function ($query) use ($academyId) {
            $query->where('academy_id', $academyId)->with('subject');
        }])
            ->where('instructor_id', $instructor->id)
            ->whereHas('academies', function ($query) use ($academyId) {
                $query->where('academies.id', $academyId);
            })
            ->orderBy('name')
            ->get();

        return view('instructor.students.index', [
            'students' => $students,
            'days' => config('schedule.days'),
        ]);
    }

    public function create()
    {
        $instructor = Auth::user();
        $academyId = session('academy_id');

        return view('instructor.students.create', [
            'subjects' => $instructor->subjects()
                ->where('academy_id', $academyId)
                ->orderBy('name')
                ->get(),
            'timeSlots' => $this->timeSlots(),
            'days' => config('schedule.days'),
        ]);
    }

    public function store(Request $request, ScheduleService $scheduleService)
    {
        $instructor = Auth::user();
        $academyId = session('academy_id');
        $timeSlots = $this->timeSlots();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'registered_at' => ['required', 'date'],
            'billing_cycle_count' => ['required', 'integer', 'min:1', 'max:50'],
            'subject_id' => [
                'required',
                Rule::exists('instructor_subject', 'subject_id')->where('user_id', $instructor->id),
            ],
            'weekday' => ['required', Rule::in(array_keys(config('schedule.days')))],
            'start_time' => ['required', Rule::in($timeSlots)],
            'confirm_group' => ['nullable', 'boolean'],
        ]);

        $subjectAllowed = $instructor->subjects()
            ->where('subjects.id', $validated['subject_id'])
            ->where('academy_id', $academyId)
            ->exists();
        if (! $subjectAllowed) {
            return back()->withErrors(['subject_id' => '선택한 과목은 현재 학원에 속하지 않습니다.']);
        }

        DB::transaction(function () use ($instructor, $validated, $scheduleService, $academyId) {
            $student = Student::create([
                'instructor_id' => $instructor->id,
                'name' => $validated['name'],
                'registered_at' => $validated['registered_at'],
                'billing_cycle_count' => $validated['billing_cycle_count'],
            ]);
            $student->academies()->syncWithoutDetaching([$academyId]);

            $scheduleService->assignStudentToSession(
                $instructor,
                $student,
                (int) $validated['subject_id'],
                (int) $validated['weekday'],
                $validated['start_time'],
                (bool) ($validated['confirm_group'] ?? false),
                $academyId
            );
        });

        return redirect()->route('students.index')->with('status', '수강생을 등록했습니다.');
    }

    public function edit(Student $student)
    {
        $this->authorizeStudent($student);

        return view('instructor.students.edit', [
            'student' => $student,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudent($student);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'billing_cycle_count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $student->update($validated);

        return redirect()->route('students.index')->with('status', '수강생 정보를 수정했습니다.');
    }

    public function destroy(Student $student)
    {
        $this->authorizeStudent($student);
        $student->load('classSessions');
        $student->delete();

        foreach ($student->classSessions as $session) {
            $remaining = $session->students()->count();

            if ($remaining <= 1 && $session->is_group) {
                $session->is_group = false;
                $session->save();
            }
        }

        return redirect()->route('students.index')->with('status', '수강생을 삭제했습니다.');
    }

    private function authorizeStudent(Student $student): void
    {
        $academyId = session('academy_id');
        if ($student->instructor_id !== Auth::id()
            || ! $student->academies()->whereKey($academyId)->exists()) {
            abort(403);
        }
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
