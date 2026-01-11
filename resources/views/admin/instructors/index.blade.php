@extends('layouts.app')

@section('content')
    <h2>강사 메뉴</h2>

    <div class="card">
        <h3>등록된 강사</h3>
        <form method="GET" action="{{ route('admin.instructors.index') }}" style="margin-bottom: 16px; display: flex; justify-content: flex-end; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; width: 240px;">
                <label for="instructor-search" style="margin-bottom: 4px; font-size: 12px;">강사 이름 검색</label>
                <input id="instructor-search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="이름을 입력하세요">
            </div>
            <button type="submit" class="btn btn-secondary">검색</button>
        </form>
        <table class="table">
            <thead>
            <tr>
                <th>이름</th>
                <th>과목</th>
                <th>현재 수업 개수</th>
                <th>수업 있는 요일</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($instructors as $instructor)
                @php
                    $subjects = $instructor->subjects->pluck('name')->filter()->unique()->values();
                    $weekdays = $instructor->classSessions->pluck('weekday')->filter()->unique()->sort()->values();
                    $weekdayLabels = $weekdays->map(function ($day) use ($days) {
                        return $days[$day] ?? null;
                    })->filter()->values();
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.instructors.show', $instructor) }}" style="text-decoration: underline;">
                            {{ $instructor->name }}
                        </a>
                    </td>
                    <td>{{ $subjects->isEmpty() ? '과목 미지정' : $subjects->join(', ') }}</td>
                    <td>{{ $instructor->classSessions->count() }}개</td>
                    <td>{{ $weekdayLabels->isEmpty() ? '없음' : $weekdayLabels->join(', ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">등록된 강사가 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
