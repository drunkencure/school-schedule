@extends('layouts.app')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content: space-between; align-items: center;">
            <div>
                <h2>{{ $instructor->name }} 강사</h2>
                <div>아이디: {{ $instructor->login_id }}</div>
                <div>이메일: {{ $instructor->email }}</div>
                <div>과목: {{ $instructor->subjects->pluck('name')->join(', ') ?: '과목 미지정' }}</div>
            </div>
            <a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">관리자 대시보드</a>
        </div>
    </div>

    <div class="card">
        <h3>수강생 목록</h3>
        <table class="table">
            <thead>
            <tr>
                <th>이름</th>
                <th>수업 시간</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($instructor->students as $student)
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>
                        @if ($student->classSessions->isEmpty())
                            등록된 수업 없음
                        @else
                            @php
                                $sortedSessions = $student->classSessions->sortBy(function ($session) {
                                    return sprintf('%02d-%s', $session->weekday, $session->start_time);
                                });
                            @endphp
                            @foreach ($sortedSessions as $session)
                                <div>
                                    {{ $days[$session->weekday] ?? '' }}
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i') }}
                                    ({{ $session->subject->name }})
                                </div>
                            @endforeach
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">등록된 수강생이 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card schedule-grid">
        <h3>주간 시간표</h3>
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
