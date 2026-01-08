@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 520px; margin: 0 auto;">
        <h2>수강생 이름 수정</h2>
        <form method="POST" action="{{ route('students.update', $student) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">이름</label>
                <input type="text" id="name" name="name" value="{{ old('name', $student->name) }}" required>
            </div>
            <button type="submit" class="btn">저장</button>
        </form>
    </div>
@endsection
