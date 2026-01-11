@extends('layouts.app')

@section('content')
    <h2>주간 시간표</h2>

    @if ($pendingStudents->isNotEmpty())
        <div class="card notice-card">
            <div class="notice-header">
                <h3>수업 일정을 정해야 하는 새로운 수강생이 있습니다!</h3>
                <span class="notice-subtitle">수강생을 선택해 수업 시간을 배정하세요.</span>
            </div>
            <ul class="notice-list">
                @foreach ($pendingStudents as $student)
                    <li class="notice-item">
                        <strong>{{ $student->name }}</strong>
                        <a class="btn btn-mini btn-secondary" href="{{ route('schedule.index', ['student_id' => $student->id]) }}">수업 배정</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card schedule-grid">
        <table class="schedule-table">
            <thead>
            <tr>
                <th>시간</th>
                @foreach ($days as $dayLabel)
                    <th>{{ $dayLabel }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($timeSlots as $time)
                <tr>
                    <th>{{ $time }}</th>
                    @foreach ($days as $dayKey => $dayLabel)
                        @php
                            $session = $grid[$dayKey][$time] ?? null;
                        @endphp
                        <td class="schedule-slot">
                            @if ($session)
                                <div class="session">
                                    {{ $session->subject->name }}
                                    <small>{{ $session->students->pluck('name')->join(', ') }}</small>
                                    @if ($session->is_group)
                                        <span class="tag">그룹</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        @if ($sessions->isEmpty())
            <p style="margin-top: 12px;">등록된 수업이 없습니다.</p>
        @endif
    </div>
@endsection
