<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\LessonAttendance;
use App\Models\Student;
use App\Models\TuitionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $instructor = Auth::user();
        $monthInput = $request->query('month', now()->format('Y-m'));
        $monthDate = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        $start = $monthDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $monthDate->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $sessions = ClassSession::with(['students', 'subject'])
            ->where('instructor_id', $instructor->id)
            ->get();

        foreach ($sessions as $session) {
            $session->start_date = $this->nextSessionStartDate($session->created_at, (int) $session->weekday);
            foreach ($session->students as $student) {
                if (! $student->pivot) {
                    continue;
                }

                $baseDate = $student->registered_at ?? $student->pivot->created_at ?? $student->created_at;

                if ($baseDate) {
                    $startBase = $baseDate instanceof Carbon ? $baseDate : Carbon::parse($baseDate);
                    $student->pivot->start_date = $this->nextSessionStartDate($startBase, (int) $session->weekday);
                }
            }
        }

        $sessions = $sessions->groupBy('weekday');

        $studentIds = $instructor->students()->pluck('id');
        $attendances = LessonAttendance::whereIn('student_id', $studentIds)
            ->whereBetween('lesson_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $attendanceMap = [];

        foreach ($attendances as $attendance) {
            $attendanceMap[$attendance->student_id][$attendance->class_session_id][$attendance->lesson_date->toDateString()] = $attendance->id;
        }

        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $day = $cursor->copy();
                $weekday = $day->dayOfWeekIso;
                $daySessions = $sessions->get($weekday, collect());
                $week[] = [
                    'date' => $day,
                    'isCurrentMonth' => $day->month === $monthDate->month,
                    'sessions' => $daySessions,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $billingStats = $this->buildBillingStats($instructor);
        $pendingTuitionRequests = TuitionRequest::with('student')
            ->where('instructor_id', $instructor->id)
            ->where('status', 'pending')
            ->orderByDesc('requested_at')
            ->get();
        $recentCompletedTuitionRequests = TuitionRequest::with('student')
            ->where('instructor_id', $instructor->id)
            ->where('status', 'completed')
            ->orderByDesc('processed_at')
            ->limit(5)
            ->get();

        return view('instructor.calendar.index', [
            'weeks' => $weeks,
            'monthDate' => $monthDate,
            'days' => config('schedule.days'),
            'attendanceMap' => $attendanceMap,
            'billingStats' => $billingStats,
            'pendingTuitionRequests' => $pendingTuitionRequests,
            'recentCompletedTuitionRequests' => $recentCompletedTuitionRequests,
        ]);
    }

    public function toggleAttendance(Request $request)
    {
        $instructor = Auth::user();
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('instructor_id', $instructor->id),
            ],
            'class_session_id' => [
                'required',
                Rule::exists('class_sessions', 'id')->where('instructor_id', $instructor->id),
            ],
            'lesson_date' => ['required', 'date'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $classSession = ClassSession::with('students')->findOrFail($validated['class_session_id']);
        $lessonDate = Carbon::parse($validated['lesson_date'])->startOfDay();
        $lessonDateString = $lessonDate->toDateString();

        if (! $classSession->students->contains($student->id)) {
            return back()->withErrors(['lesson_date' => '해당 수업에 등록된 학생이 아닙니다.']);
        }

        if ($lessonDate->dayOfWeekIso !== (int) $classSession->weekday) {
            return back()->withErrors(['lesson_date' => '수업 요일과 맞지 않는 날짜입니다.']);
        }

        $today = now()->startOfDay();
        $registeredAt = ($student->registered_at ?? $student->created_at)->copy()->startOfDay();

        if ($lessonDate->gt($today)) {
            return back()->withErrors(['lesson_date' => '오늘 이후의 수업은 완료 처리할 수 없습니다.']);
        }

        if ($lessonDate->lt($registeredAt)) {
            return back()->withErrors(['lesson_date' => '등록일 이전 수업은 완료 처리할 수 없습니다.']);
        }

        $attendance = LessonAttendance::where('student_id', $student->id)
            ->where('class_session_id', $classSession->id)
            ->where('lesson_date', $lessonDateString)
            ->first();

        if ($attendance) {
            $attendance->delete();
            $message = '수업 완료 표시를 취소했습니다.';
        } else {
            LessonAttendance::create([
                'student_id' => $student->id,
                'class_session_id' => $classSession->id,
                'lesson_date' => $lessonDateString,
            ]);
            $message = '수업 완료 표시를 저장했습니다.';
        }

        return back()->with('status', $message);
    }

    public function requestTuition(Request $request)
    {
        $instructor = Auth::user();
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('instructor_id', $instructor->id),
            ],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $attendanceQuery = LessonAttendance::where('student_id', $student->id)
            ->orderBy('lesson_date');

        if ($student->last_billed_lesson_date) {
            $attendanceQuery->where('lesson_date', '>', $student->last_billed_lesson_date->toDateString());
        }

        $attendances = $attendanceQuery->get();
        $count = $attendances->count();

        if ($count < $student->billing_cycle_count) {
            return back()->withErrors(['student_id' => '수업 회차가 아직 부족합니다.']);
        }

        $lessonDates = $attendances->pluck('lesson_date')->map(function ($date) {
            return $date->toDateString();
        })->values()->all();

        TuitionRequest::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'lesson_count' => $count,
            'lesson_dates' => $lessonDates,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $student->last_billed_lesson_date = $attendances->max('lesson_date');
        $student->save();

        return back()->with('status', '수업료 입금 요청을 보냈습니다.');
    }

    private function buildBillingStats($instructor): array
    {
        $students = $instructor->students()->orderBy('name')->get();
        $stats = [];

        foreach ($students as $student) {
            $query = LessonAttendance::where('student_id', $student->id);
            if ($student->last_billed_lesson_date) {
                $query->where('lesson_date', '>', $student->last_billed_lesson_date->toDateString());
            }
            $count = $query->count();

            $latestRequest = TuitionRequest::where('student_id', $student->id)
                ->latest('requested_at')
                ->first();
            $hasPendingRequest = TuitionRequest::where('student_id', $student->id)
                ->where('status', 'pending')
                ->exists();

            $stats[$student->id] = [
                'student' => $student,
                'count' => $count,
                'cycle' => $student->billing_cycle_count,
                'eligible' => $count >= $student->billing_cycle_count,
                'pending' => $hasPendingRequest,
                'latestRequest' => $latestRequest,
            ];
        }

        return $stats;
    }

    private function nextSessionStartDate(Carbon $baseDate, int $weekday): Carbon
    {
        $base = $baseDate->copy()->startOfDay();
        $currentWeekday = (int) $base->dayOfWeekIso;
        $offset = ($weekday - $currentWeekday + 7) % 7;

        return $base->copy()->addDays($offset);
    }
}
