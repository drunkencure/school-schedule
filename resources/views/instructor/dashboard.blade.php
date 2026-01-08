@extends('layouts.app')

@section('content')
    <h2>주간 시간표</h2>

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
