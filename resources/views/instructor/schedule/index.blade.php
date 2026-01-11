@extends('layouts.app')

@section('content')
    <h2>시간표 수정</h2>

    <div class="card">
        <h3>수업 추가</h3>
        <form method="POST" action="{{ route('schedule.store') }}">
            @csrf
            <div class="grid two">
                <div class="form-group">
                    <label for="student_id">수강생</label>
                    <select id="student_id" name="student_id" required {{ $students->isEmpty() ? 'disabled' : '' }}>
                        <option value="">선택</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" {{ old('student_id', $selectedStudentId ?? '') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
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
                    <label for="start_time">시작 시간</label>
                    <select id="start_time" name="start_time" required>
                        @foreach ($timeSlots as $slot)
                            <option value="{{ $slot }}" {{ old('start_time') == $slot ? 'selected' : '' }}>
                                {{ $slot }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if ($students->isEmpty())
                <div class="alert alert-error" style="margin-bottom: 12px;">
                    수강생을 먼저 등록해야 수업을 추가할 수 있습니다.
                </div>
            @endif
            <div class="form-group">
                <label>
                    <input type="checkbox" name="confirm_group" value="1" {{ old('confirm_group') ? 'checked' : '' }}>
                    같은 시간대에 다른 학생이 있으면 그룹 수업으로 등록
                </label>
            </div>
            <button type="submit" class="btn" {{ $students->isEmpty() || $subjects->isEmpty() ? 'disabled' : '' }}>추가</button>
        </form>
    </div>

    <div class="card mobile-only">
        <h3>모바일 시간표 수정</h3>
        <form method="POST" action="{{ route('schedule.move.form') }}">
            @csrf
            <div class="form-group">
                <label for="session_id">이동할 수업</label>
                <select id="session_id" name="session_id" required>
                    <option value="">선택</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}">
                            {{ $session->subject->name }} -
                            {{ $session->students->pluck('name')->join(', ') }} /
                            {{ $days[$session->weekday] ?? '' }}
                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="mobile_weekday">요일</label>
                <select id="mobile_weekday" name="weekday" required>
                    @foreach ($days as $dayKey => $dayLabel)
                        <option value="{{ $dayKey }}">{{ $dayLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="mobile_start_time">시작 시간</label>
                <select id="mobile_start_time" name="start_time" required>
                    @foreach ($timeSlots as $slot)
                        <option value="{{ $slot }}">{{ $slot }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn">이동</button>
        </form>

        <h4 style="margin-top: 20px;">수업 삭제</h4>
        @if ($sessions->isEmpty())
            <p>등록된 수업이 없습니다.</p>
        @else
            <table class="table">
                <thead>
                <tr>
                    <th>수업</th>
                    <th>삭제</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($sessions as $session)
                    <tr>
                        <td>
                            {{ $session->subject->name }} -
                            {{ $session->students->pluck('name')->join(', ') }} /
                            {{ $days[$session->weekday] ?? '' }}
                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i') }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('schedule.destroy', $session) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">삭제</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card schedule-grid desktop-only">
        <h3>드래그로 이동</h3>
        <p>다른 수업이 있는 시간대에는 이동할 수 없습니다. 삭제 후 다시 등록하세요.</p>
        <table class="schedule-table" id="scheduleTable">
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
                        <td class="schedule-slot" data-weekday="{{ $dayKey }}" data-time="{{ $time }}">
                            @if ($session)
                                <div class="session" draggable="true" data-session-id="{{ $session->id }}">
                                    {{ $session->subject->name }}
                                    <small>{{ $session->students->pluck('name')->join(', ') }}</small>
                                    @if ($session->is_group)
                                        <span class="tag">그룹</span>
                                    @endif
                                    <div class="session-actions">
                                        <form method="POST" action="{{ route('schedule.destroy', $session) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit" onclick="return confirm('해당 수업을 삭제할까요?')">삭제</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@section('scripts')
    <script>
        const scheduleTable = document.getElementById('scheduleTable');
        if (scheduleTable) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let draggedSessionId = null;

            scheduleTable.addEventListener('dragstart', (event) => {
                const target = event.target.closest('.session');
                if (!target) {
                    return;
                }
                draggedSessionId = target.dataset.sessionId;
                event.dataTransfer.setData('text/plain', draggedSessionId);
            });

            scheduleTable.addEventListener('dragover', (event) => {
                const cell = event.target.closest('.schedule-slot');
                if (cell && !cell.querySelector('.session')) {
                    event.preventDefault();
                }
            });

            scheduleTable.addEventListener('drop', async (event) => {
                const cell = event.target.closest('.schedule-slot');
                if (!cell || cell.querySelector('.session')) {
                    return;
                }
                event.preventDefault();
                const sessionId = draggedSessionId || event.dataTransfer.getData('text/plain');
                const weekday = cell.dataset.weekday;
                const startTime = cell.dataset.time;

                try {
                    const response = await fetch(`/schedule/sessions/${sessionId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ weekday, start_time: startTime }),
                    });

                    if (!response.ok) {
                        const data = await response.json();
                        alert(data.message || '이동할 수 없습니다.');
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    alert('이동 중 오류가 발생했습니다.');
                }
            });
        }
    </script>
@endsection
