@extends('layouts.app')

@section('content')
    <h2>수강생 메뉴</h2>

    <div class="card">
        <h3>수강생 추가</h3>
        <form method="POST" action="{{ route('admin.students.store') }}">
            @csrf
            <div class="grid two">
                <div class="form-group">
                    <label for="student_name">이름</label>
                    <input type="text" id="student_name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label for="student_registered_at">등록일</label>
                    <input type="date" id="student_registered_at" name="registered_at" value="{{ old('registered_at', now()->toDateString()) }}" required>
                </div>
                <div class="form-group" style="max-width: 140px;">
                    <label for="student_billing_cycle">수업료 요청 회차</label>
                    <input type="number" id="student_billing_cycle" name="billing_cycle_count" value="{{ old('billing_cycle_count', 4) }}" min="1" max="50" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 260px;">
                    <label for="student_instructor">담당 강사</label>
                    <select id="student_instructor" name="instructor_id" required>
                        <option value="">선택</option>
                        @foreach ($instructors as $instructor)
                            @php
                                $subjectNames = $instructor->subjects->pluck('name')->filter()->unique()->values();
                                $subjectLabel = $subjectNames->isEmpty() ? '과목 미지정' : $subjectNames->join(', ');
                            @endphp
                            <option value="{{ $instructor->id }}" {{ old('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->name }} ({{ $subjectLabel }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary">수강생 등록</button>
        </form>
    </div>

    <div class="card">
        <h3>전체 수강생</h3>
        <form method="GET" action="{{ route('admin.students.index') }}" style="margin-bottom: 16px; display: flex; justify-content: flex-end; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; width: 240px;">
                <label for="student-search" style="margin-bottom: 4px; font-size: 12px;">수강생 이름 검색</label>
                <input id="student-search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="이름을 입력하세요">
            </div>
            <button type="submit" class="btn btn-secondary">검색</button>
        </form>
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
                    @php
                        $instructor = $student->instructor;
                        $instructorSubjects = $instructor ? $instructor->subjects->pluck('name')->filter()->unique()->values() : collect();
                        $instructorLabel = $instructor
                            ? $instructor->name.'('.($instructorSubjects->isEmpty() ? '과목 미지정' : $instructorSubjects->join(', ')).')'
                            : '';
                    @endphp
                    <td>
                        @if ($instructor)
                            <a href="{{ route('admin.instructors.show', $instructor) }}" style="text-decoration: underline;">
                                {{ $instructorLabel }}
                            </a>
                        @else
                            {{ $instructorLabel }}
                        @endif
                    </td>
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
