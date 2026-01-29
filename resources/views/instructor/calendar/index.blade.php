@extends('layouts.app')

@section('content')
    @php
        $today = \Carbon\Carbon::today();
    @endphp
    <div class="calendar-layout">
        <div class="calendar-main">
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

            <div class="card notice-card">
                <div class="notice-header">
                    <h3>수업료 입금 알림</h3>
                    <span class="notice-subtitle">요청 및 처리 현황을 빠르게 확인하세요.</span>
                </div>
                <div class="notice-grid">
                    <div class="notice-panel notice-pending">
                        <div class="notice-title">입금 요청 대기</div>
                        <div class="notice-count">{{ $pendingTuitionRequests->count() }}건</div>
                        <ul class="notice-list">
                            @forelse ($pendingTuitionRequests as $request)
                                <li>
                                    <strong>{{ $request->student->name ?? '' }}</strong>
                                    <span class="text-muted">{{ $request->requested_at->format('m/d') }} 요청</span>
                                </li>
                            @empty
                                <li class="text-muted">대기 중인 요청이 없습니다.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="notice-panel notice-completed">
                        <div class="notice-title">입금 처리 완료</div>
                        <div class="notice-count">{{ $recentCompletedTuitionRequests->count() }}건</div>
                        <ul class="notice-list">
                            @forelse ($recentCompletedTuitionRequests as $request)
                                <li>
                                    <strong>{{ $request->student->name ?? '' }}</strong>
                                    <span class="text-muted">
                                        {{ optional($request->processed_at)->format('m/d') }} 처리
                                    </span>
                                </li>
                            @empty
                                <li class="text-muted">최근 처리 완료 내역이 없습니다.</li>
                            @endforelse
                        </ul>
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
                                    @php
                                        $hasSession = false;
                                    @endphp
                                    @foreach ($day['sessions'] as $session)
                                        @php
                                            $sessionStartDate = $session->start_date ?? null;
                                        @endphp
                                        @if ($sessionStartDate && $day['date']->lt($sessionStartDate))
                                            @continue
                                        @endif
                                        @php
                                            $eligibleStudents = $session->students->filter(function ($student) use ($day) {
                                                $startDate = $student->pivot->start_date ?? null;
                                                if ($startDate && $day['date']->lt($startDate)) {
                                                    return false;
                                                }

                                                if ($student->trashed()) {
                                                    $endedAt = $student->deleted_at ? $student->deleted_at->copy()->startOfDay() : null;
                                                    if ($endedAt && $day['date']->gt($endedAt)) {
                                                        return false;
                                                    }
                                                }

                                                return true;
                                            });
                                        @endphp
                                        @if ($eligibleStudents->isEmpty())
                                            @continue
                                        @endif
                                        @php
                                            $hasSession = true;
                                        @endphp
                                        <div class="calendar-session">
                                            <div class="calendar-session-title">
                                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $session->start_time)->format('H:i') }}
                                                {{ $session->subject->name }}
                                            </div>
                                            @foreach ($eligibleStudents as $student)
                                                @php
                                                    $hasAttendance = isset($attendanceMap[$student->id][$session->id][$dateKey]);
                                                    $registeredAt = $student->registered_at
                                                        ? $student->registered_at->copy()->startOfDay()
                                                        : $student->created_at->copy()->startOfDay();
                                                    $deletedAt = $student->deleted_at
                                                        ? $student->deleted_at->copy()->startOfDay()
                                                        : null;
                                                    $lessonDay = $day['date']->copy()->startOfDay();
                                                    $canToggle = $lessonDay->gte($registeredAt) && $lessonDay->lte($today);
                                                    if ($deletedAt && $lessonDay->gt($deletedAt)) {
                                                        $canToggle = false;
                                                    }
                                                @endphp
                                                <div class="calendar-student">
                                                    <span>{{ $student->name }}</span>
                                                    <form method="POST" action="{{ route('calendar.attendance.toggle') }}">
                                                        @csrf
                                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                        <input type="hidden" name="class_session_id" value="{{ $session->id }}">
                                                        <input type="hidden" name="lesson_date" value="{{ $dateKey }}">
                                                        <button type="submit"
                                                                class="btn btn-mini {{ $hasAttendance ? 'btn-secondary' : '' }}"
                                                                {{ $canToggle ? '' : 'disabled' }}>
                                                            {{ $hasAttendance ? '진행 완료' : '진행전' }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                    @if (! $hasSession && $day['isCurrentMonth'])
                                        <div class="calendar-empty">수업 없음</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="calendar-sidebar">
            <div class="card sidebar-card">
                <h3>수업료 요청</h3>
                <div class="sidebar-section">
                    <div class="sidebar-section-title">요청 가능</div>
                    <div class="sidebar-section-subtitle">바로 입금 요청을 보낼 수 있습니다.</div>
                    <div class="sidebar-list">
                        @forelse ($tuitionReady as $stat)
                            @php
                                $student = $stat['student'];
                            @endphp
                            <div class="sidebar-item">
                                <div class="sidebar-row">
                                    <strong>{{ $student->name }}</strong>
                                    <span class="status-badge status-pending">요청 가능</span>
                                </div>
                                <div class="sidebar-meta">{{ $stat['count'] }} / {{ $stat['cycle'] }}회</div>
                                <form method="POST" action="{{ route('calendar.tuition.request') }}">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                    <button type="submit" class="btn btn-mini">입금 요청</button>
                                </form>
                            </div>
                        @empty
                            <div class="sidebar-empty">요청 가능한 수강생이 없습니다.</div>
                        @endforelse
                    </div>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">요청 임박</div>
                    <div class="sidebar-section-subtitle">1~2회만 더 진행하면 요청 가능합니다.</div>
                    <div class="sidebar-list">
                        @forelse ($tuitionSoon as $stat)
                            @php
                                $student = $stat['student'];
                            @endphp
                            <div class="sidebar-item">
                                <div class="sidebar-row">
                                    <strong>{{ $student->name }}</strong>
                                    <span class="status-badge status-soon">{{ $stat['remaining'] }}회 남음</span>
                                </div>
                                <div class="sidebar-meta">{{ $stat['count'] }} / {{ $stat['cycle'] }}회</div>
                            </div>
                        @empty
                            <div class="sidebar-empty">요청 임박 수강생이 없습니다.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="card">
        <h3>수업료 입금 요청</h3>
        <table class="table">
            <thead>
            <tr>
                <th>수강생</th>
                <th>진행 회차</th>
                <th>요청 조건</th>
                <th>처리 상태</th>
                <th>요청</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($billingStats as $studentId => $stat)
                @php
                    $student = $stat['student'];
                    $latestRequest = $stat['latestRequest'];
                @endphp
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $stat['count'] }}회</td>
                    <td>{{ $stat['cycle'] }}회마다 요청</td>
                    <td>
                        @if ($latestRequest)
                            @if ($latestRequest->status === 'pending')
                                <span class="status-badge status-pending">요청 대기 중</span>
                                <div class="text-muted">요청: {{ $latestRequest->requested_at->format('Y-m-d') }}</div>
                            @else
                                <span class="status-badge status-completed">입금 처리 완료</span>
                                @if ($latestRequest->processed_at)
                                    <div class="text-muted">처리: {{ $latestRequest->processed_at->format('Y-m-d') }}</div>
                                @endif
                            @endif
                        @else
                            <span class="text-muted">요청 없음</span>
                        @endif
                    </td>
                    <td>
                        @if ($stat['pending'])
                            요청 대기 중
                        @elseif ($stat['eligible'])
                            <form method="POST" action="{{ route('calendar.tuition.request') }}">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <button type="submit" class="btn">입금 요청</button>
                            </form>
                        @else
                            회차 부족
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">등록된 수강생이 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
