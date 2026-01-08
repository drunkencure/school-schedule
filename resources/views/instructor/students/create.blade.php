@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 680px; margin: 0 auto;">
        <h2>수강생 등록</h2>
        <form method="POST" action="{{ route('students.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">이름</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="billing_cycle_count">수업료 요청 회차</label>
                <input type="number" id="billing_cycle_count" name="billing_cycle_count" min="1" max="50"
                       value="{{ old('billing_cycle_count', 4) }}" required>
            </div>
            <div class="form-group">
                <label for="subject_id">과목</label>
                <select id="subject_id" name="subject_id" required {{ $subjects->isEmpty() ? 'disabled' : '' }}>
                    <option value="">선택</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
                @if ($subjects->isEmpty())
                    <div class="alert alert-error" style="margin-top: 10px;">
                        선택 가능한 과목이 없습니다. 관리자에게 문의하세요.
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="weekday">요일</label>
                <select id="weekday" name="weekday" required>
                    @foreach ($days as $dayKey => $dayLabel)
                        <option value="{{ $dayKey }}" {{ old('weekday') == $dayKey ? 'selected' : '' }}>
                            {{ $dayLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="start_time">시작 시간 (1시간 단위)</label>
                <select id="start_time" name="start_time" required>
                    @foreach ($timeSlots as $slot)
                        <option value="{{ $slot }}" {{ old('start_time') == $slot ? 'selected' : '' }}>
                            {{ $slot }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="confirm_group" value="1" {{ old('confirm_group') ? 'checked' : '' }}>
                    같은 시간대에 다른 학생이 있으면 그룹 수업으로 등록
                </label>
            </div>
            <button type="submit" class="btn" {{ $subjects->isEmpty() ? 'disabled' : '' }}>등록</button>
        </form>
    </div>
@endsection
