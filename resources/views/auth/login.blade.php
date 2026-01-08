@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 480px; margin: 40px auto;">
        <h2>강사 로그인</h2>
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="form-group">
                <label for="login_id">아이디</label>
                <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}" required>
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">로그인</button>
        </form>
        <p style="margin-top: 16px;">
            아직 계정이 없나요?
            <a href="{{ route('register') }}" style="text-decoration: underline;">강사 등록 요청</a>
        </p>
    </div>
@endsection
