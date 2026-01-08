<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>학원 수강생 스케쥴러</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="site-header">
    <div class="container">
        @auth
            <a class="brand" href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('instructor.dashboard') }}">
                학원 수강생 스케쥴러
            </a>
        @else
            <div class="brand">학원 수강생 스케쥴러</div>
        @endauth
        @auth
            <nav class="nav">
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}">관리자</a>
                @else
                    <a href="{{ route('students.index') }}">수강생 등록/삭제</a>
                    <a href="{{ route('schedule.index') }}">시간표 수정</a>
                    <a href="{{ route('calendar.index') }}">수업료 요청</a>
                @endif
            </nav>
            <div class="nav-actions">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">로그아웃</button>
                </form>
            </div>
        @endauth
    </div>
</header>

<main class="container main">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@yield('scripts')
</body>
</html>
