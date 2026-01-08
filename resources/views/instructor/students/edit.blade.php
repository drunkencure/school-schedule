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
            <div class="form-group">
                <label for="billing_cycle_count">수업료 요청 회차</label>
                <input type="number" id="billing_cycle_count" name="billing_cycle_count" min="1" max="50"
                       value="{{ old('billing_cycle_count', $student->billing_cycle_count) }}" required>
            </div>
            <button type="submit" class="btn">저장</button>
        </form>
    </div>
@endsection
