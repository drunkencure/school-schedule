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
        $students = Student::with(['classSessions.subject'])
            ->where('instructor_id', $instructor->id)
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

        return view('instructor.students.create', [
            'subjects' => $instructor->subjects()->orderBy('name')->get(),
            'timeSlots' => $this->timeSlots(),
            'days' => config('schedule.days'),
        ]);
    }

    public function store(Request $request, ScheduleService $scheduleService)
    {
        $instructor = Auth::user();
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

        DB::transaction(function () use ($instructor, $validated, $scheduleService) {
            $student = Student::create([
                'instructor_id' => $instructor->id,
                'name' => $validated['name'],
                'registered_at' => $validated['registered_at'],
                'billing_cycle_count' => $validated['billing_cycle_count'],
            ]);

            $scheduleService->assignStudentToSession(
                $instructor,
                $student,
                (int) $validated['subject_id'],
                (int) $validated['weekday'],
                $validated['start_time'],
                (bool) ($validated['confirm_group'] ?? false)
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
        $student->load('classSessions.students');

        foreach ($student->classSessions as $session) {
            $session->students()->detach($student->id);
            $remaining = $session->students()->count();

            if ($remaining === 0) {
                $session->delete();
            } elseif ($remaining === 1 && $session->is_group) {
                $session->is_group = false;
                $session->save();
            }
        }

        $student->delete();

        return redirect()->route('students.index')->with('status', '수강생을 삭제했습니다.');
    }

    private function authorizeStudent(Student $student): void
    {
        if ($student->instructor_id !== Auth::id()) {
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
