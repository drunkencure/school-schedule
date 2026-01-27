@extends('layouts.app')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2>전체 시간표</h2>
                <div class="text-muted">기간: {{ $weekRange }}</div>
            </div>
            <a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">관리자 대시보드</a>
        </div>
    </div>

    <div class="card dashboard-schedule">
        <div class="dashboard-schedule-header">
            <div>
                <h3>이번 주 전체 시간표</h3>
                <div class="text-muted">이번 주 모든 강사의 시간표를 시간대별로 확인합니다.</div>
            </div>
            <div class="dashboard-stats">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-label">활성 강사</span>
                    <span class="dashboard-stat-value">{{ $approvedInstructorsCount }}</span>
                </div>
                <div class="dashboard-stat">
                    <span class="dashboard-stat-label">등록 수업</span>
                    <span class="dashboard-stat-value">{{ $scheduleSessionCount }}</span>
                </div>
                <div class="dashboard-stat">
                    <span class="dashboard-stat-label">오늘 수업</span>
                    <span class="dashboard-stat-value">{{ $todaySessionCount }}</span>
                </div>
                <div class="dashboard-stat">
                    <span class="dashboard-stat-label">선택 강사 수업</span>
                    <span class="dashboard-stat-value" id="selectedSessionCount">{{ $scheduleSessionCount }}</span>
                </div>
            </div>
        </div>

        <div class="dashboard-schedule-filters">
            <button type="button" class="filter-btn is-active" data-instructor-filter="all" aria-pressed="true">
                전체 보기
            </button>
            @foreach ($approvedInstructors as $instructor)
                @php
                    $colorClass = $instructorColors[$instructor->id] ?? 'instructor-color-1';
                @endphp
                <button type="button" class="filter-btn" data-instructor-filter="{{ $instructor->id }}" aria-pressed="false">
                    <span class="legend-dot {{ $colorClass }}"></span>
                    {{ $instructor->name }}
                </button>
            @endforeach
        </div>

        <div class="schedule-grid">
            <table class="schedule-table schedule-table-admin">
                <thead>
                <tr>
                    <th>시간</th>
                    @foreach ($days as $dayKey => $dayLabel)
                        <th class="{{ $todayKey === $dayKey ? 'today-column' : '' }}">
                            <div class="day-header">
                                <span>{{ $dayLabel }}</span>
                                @if ($todayKey === $dayKey)
                                    <span class="today-pill">오늘</span>
                                @endif
                            </div>
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($timeSlots as $time)
                    <tr>
                        <th>{{ $time }}</th>
                        @foreach ($days as $dayKey => $dayLabel)
                            @php
                                $sessions = $scheduleGrid[$dayKey][$time] ?? [];
                            @endphp
                            <td class="schedule-slot {{ $todayKey === $dayKey ? 'today-column' : '' }}">
                                @foreach ($sessions as $session)
                                    @php
                                        $colorClass = $instructorColors[$session->instructor_id] ?? 'instructor-color-1';
                                    @endphp
                                    <div class="session session-admin {{ $colorClass }}" data-instructor-id="{{ $session->instructor_id }}">
                                        <div class="session-title">
                                            <strong>{{ $session->instructor->name ?? '강사 미지정' }}</strong>
                                            <span class="session-subject">{{ $session->subject->name ?? '과목 미지정' }}</span>
                                        </div>
                                        <small>{{ $session->students->pluck('name')->join(', ') ?: '수강생 없음' }}</small>
                                        @if ($session->is_group)
                                            <span class="tag">그룹</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($scheduleSessions->isEmpty())
                <p style="margin-top: 12px;">등록된 수업이 없습니다.</p>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scheduleCard = document.querySelector('.dashboard-schedule');
            if (!scheduleCard) {
                return;
            }

            const filterButtons = scheduleCard.querySelectorAll('[data-instructor-filter]');
            const sessions = scheduleCard.querySelectorAll('.session[data-instructor-id]');
            const selectedCountEl = scheduleCard.querySelector('#selectedSessionCount');
            const totalCount = sessions.length;

            const setActiveInstructor = (instructorId) => {
                filterButtons.forEach((button) => {
                    const isActive = button.dataset.instructorFilter === instructorId;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                let visibleCount = 0;
                sessions.forEach((session) => {
                    const isMatch = instructorId === 'all' || session.dataset.instructorId === instructorId;
                    if (instructorId === 'all') {
                        session.classList.remove('is-hidden', 'is-highlighted');
                    } else {
                        session.classList.toggle('is-hidden', !isMatch);
                        session.classList.toggle('is-highlighted', isMatch);
                    }
                    if (isMatch) {
                        visibleCount += 1;
                    }
                });

                if (selectedCountEl) {
                    selectedCountEl.textContent = instructorId === 'all' ? totalCount : visibleCount;
                }
            };

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    setActiveInstructor(button.dataset.instructorFilter);
                });
            });

            setActiveInstructor('all');
        });
    </script>
@endsection
