@extends('layouts.app')

@section('content')
    <div class="card">
        <div style="display:flex; align-items:center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <h2>{{ $monthDate->format('Y년 m월') }} 수업 달력</h2>
            <div style="display:flex; gap: 10px;">
                <a class="btn btn-secondary" href="{{ route('calendar.index', ['month' => $monthDate->copy()->subMonth()->format('Y-m')]) }}">이전 달</a>
                <a class="btn btn-secondary" href="{{ route('calendar.index', ['month' => now()->format('Y-m')]) }}">이번 달</a>
                <a class="btn btn-secondary" href="{{ route('calendar.index', ['month' => $monthDate->copy()->addMonth()->format('Y-m')]) }}">다음 달</a>
            </div>
        </div>
    </div>

    <div class="card calendar-grid">
        <table class="calendar-table">
            <thead>
            <tr>
                @foreach ($days as $dayLabel)
                    <th>{{ $dayLabel }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($weeks as $week)
                <tr>
                    @foreach ($week as $day)
                        @php
                            $dateKey = $day['date']->toDateString();
                        @endphp
                        <td class="{{ $day['isCurrentMonth'] ? '' : 'calendar-muted' }}">
                            <div class="calendar-date">{{ $day['date']->format('j') }}</div>
                            @forelse ($day['sessions'] as $session)
                                <div class="calendar-session">
                                    <div class="calendar-session-title">
                                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i') }}
                                        {{ $session->subject->name }}
                                    </div>
                                    @foreach ($session->students as $student)
                                        @php
                                            $hasAttendance = isset($attendanceMap[$student->id][$session->id][$dateKey]);
                                        @endphp
                                        <div class="calendar-student">
                                            <span>{{ $student->name }}</span>
                                            <form method="POST" action="{{ route('calendar.attendance.toggle') }}">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <input type="hidden" name="class_session_id" value="{{ $session->id }}">
                                                <input type="hidden" name="lesson_date" value="{{ $dateKey }}">
                                                <button type="submit" class="btn btn-mini {{ $hasAttendance ? 'btn-secondary' : '' }}">
                                                    {{ $hasAttendance ? '완료됨' : '완료' }}
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                @if ($day['isCurrentMonth'])
                                    <div class="calendar-empty">수업 없음</div>
                                @endif
                            @endforelse
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>수업료 입금 요청</h3>
        <table class="table">
            <thead>
            <tr>
                <th>수강생</th>
                <th>진행 회차</th>
                <th>요청 조건</th>
                <th>요청</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($billingStats as $studentId => $stat)
                @php
                    $student = $stat['student'];
                @endphp
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $stat['count'] }}회</td>
                    <td>{{ $stat['cycle'] }}회마다 요청</td>
                    <td>
                        @if ($stat['eligible'])
                            <form method="POST" action="{{ route('calendar.tuition.request') }}">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <button type="submit" class="btn">입금 요청</button>
                            </form>
                        @elseif ($stat['pending'])
                            요청 대기 중
                        @else
                            회차 부족
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">등록된 수강생이 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
