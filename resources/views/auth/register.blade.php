@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 600px; margin: 30px auto;">
        <h2>강사 등록 요청</h2>
        <form method="POST" action="{{ route('register.submit') }}">
            @csrf
            <div class="form-group">
                <label for="login_id">아이디</label>
                <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}" required>
            </div>
            <div class="form-group">
                <label for="email">이메일</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label for="name">이름</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            @if (!empty($defaultAcademy))
                <div class="form-group">
                    <label>등록 학원</label>
                    <div class="text-muted">{{ $defaultAcademy->name }}</div>
                </div>
            @endif
            <div class="form-group">
                <label>과목 선택 (복수 선택 가능)</label>
                @if ($subjects->isEmpty())
                    <div class="alert alert-error">등록된 과목이 없습니다. 관리자에게 문의하세요.</div>
                @else
                    <div class="checkbox-group">
                        @foreach ($subjects as $subject)
                            <label>
                                <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                                    {{ in_array($subject->id, old('subjects', [])) ? 'checked' : '' }}>
                                {{ $subject->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation">비밀번호 확인</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn" {{ $subjects->isEmpty() ? 'disabled' : '' }}>등록 요청</button>
        </form>
    </div>
@endsection
