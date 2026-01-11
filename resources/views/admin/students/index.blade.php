@extends('layouts.app')

@section('content')
    <h2>수강생 메뉴</h2>

    <div class="card">
        <h3>전체 수강생</h3>
        <table class="table">
            <thead>
            <tr>
                <th>이름</th>
                <th>과목</th>
                <th>강사</th>
                <th>수업 요일/시간</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($students as $student)
                @php
                    $subjects = $student->classSessions->pluck('subject.name')->filter()->unique()->values();
                    $sortedSessions = $student->classSessions->sortBy(function ($session) {
                        return sprintf('%02d-%s', $session->weekday, $session->start_time);
                    });
                @endphp
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $subjects->isEmpty() ? '과목 미지정' : $subjects->join(', ') }}</td>
                    <td>{{ $student->instructor->name ?? '' }}</td>
                    <td>
                        @if ($sortedSessions->isEmpty())
                            등록된 수업 없음
                        @else
                            @foreach ($sortedSessions as $session)
                                <div>
                                    {{ $days[$session->weekday] ?? '' }}
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i') }}
                                </div>
                            @endforeach
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
