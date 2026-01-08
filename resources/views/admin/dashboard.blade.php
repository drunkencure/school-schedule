@extends('layouts.app')

@section('content')
    <h2>전체 관리자</h2>

    <div class="grid two">
        <div class="card">
            <h3>과목 관리</h3>
            <form method="POST" action="{{ route('admin.subjects.store') }}">
                @csrf
                <div class="form-group">
                    <label for="subject_name">과목 이름</label>
                    <input type="text" id="subject_name" name="name" required>
                </div>
                <button type="submit" class="btn btn-secondary">과목 등록</button>
            </form>

            <table class="table" style="margin-top: 16px;">
                <thead>
                <tr>
                    <th>과목</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($subjects as $subject)
                    <tr>
                        <td>{{ $subject->name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>등록된 과목이 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>승인 대기 강사</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>아이디</th>
                    <th>이름</th>
                    <th>이메일</th>
                    <th>처리</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($pendingInstructors as $instructor)
                    <tr>
                        <td>{{ $instructor->login_id }}</td>
                        <td>{{ $instructor->name }}</td>
                        <td>{{ $instructor->email }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.instructors.approve', $instructor) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn">승인</button>
                            </form>
                            <form method="POST" action="{{ route('admin.instructors.reject', $instructor) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-danger">거절</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">승인 대기 강사가 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>활성 강사</h3>
        <table class="table">
            <thead>
            <tr>
                <th>아이디</th>
                <th>이름</th>
                <th>이메일</th>
                <th>과목</th>
                <th>처리</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($approvedInstructors as $instructor)
                <tr>
                    <td>{{ $instructor->login_id }}</td>
                    <td>
                        <a href="{{ route('admin.instructors.show', $instructor) }}" style="text-decoration: underline;">
                            {{ $instructor->name }}
                        </a>
                    </td>
                    <td>{{ $instructor->email }}</td>
                    <td>
                        {{ $instructor->subjects->pluck('name')->join(', ') ?: '과목 미지정' }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.instructors.deactivate', $instructor) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">비활성화</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">활성 강사가 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid two">
        <div class="card">
            <h3>비활성 강사</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>아이디</th>
                    <th>이름</th>
                    <th>이메일</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($inactiveInstructors as $instructor)
                    <tr>
                        <td>{{ $instructor->login_id }}</td>
                        <td>{{ $instructor->name }}</td>
                        <td>{{ $instructor->email }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">비활성 강사가 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>거절된 강사</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>아이디</th>
                    <th>이름</th>
                    <th>이메일</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rejectedInstructors as $instructor)
                    <tr>
                        <td>{{ $instructor->login_id }}</td>
                        <td>{{ $instructor->name }}</td>
                        <td>{{ $instructor->email }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">거절된 강사가 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
