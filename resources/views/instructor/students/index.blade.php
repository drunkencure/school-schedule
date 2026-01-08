@extends('layouts.app')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content: space-between; align-items:center;">
            <h2>수강생 관리</h2>
            <a class="btn" href="{{ route('students.create') }}">수강생 등록</a>
        </div>
        <table class="table" style="margin-top: 16px;">
            <thead>
            <tr>
                <th>이름</th>
                <th>수업 시간</th>
                <th>관리</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($students as $student)
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
                    <td>
                        <a class="btn btn-secondary" href="{{ route('students.edit', $student) }}">이름 수정</a>
                        <form method="POST" action="{{ route('students.destroy', $student) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit" onclick="return confirm('정말 삭제할까요?')">삭제</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">등록된 수강생이 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
