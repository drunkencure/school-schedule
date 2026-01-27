<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/schedule', [AdminController::class, 'scheduleIndex'])->name('admin.schedule.index');
    Route::get('/instructors', [AdminController::class, 'instructorsIndex'])->name('admin.instructors.index');
    Route::post('/instructors', [AdminController::class, 'storeInstructor'])->name('admin.instructors.store');
    Route::post('/subjects', [AdminController::class, 'storeSubject'])->name('admin.subjects.store');
    Route::get('/students', [AdminController::class, 'studentsIndex'])->name('admin.students.index');
    Route::post('/students', [AdminController::class, 'storeStudent'])->name('admin.students.store');
    Route::get('/instructors/{user}', [AdminController::class, 'showInstructor'])->name('admin.instructors.show');
    Route::post('/instructors/{user}/approve', [AdminController::class, 'approve'])->name('admin.instructors.approve');
    Route::post('/instructors/{user}/reject', [AdminController::class, 'reject'])->name('admin.instructors.reject');
    Route::post('/instructors/{user}/deactivate', [AdminController::class, 'deactivate'])->name('admin.instructors.deactivate');
    Route::post('/tuition-requests/{tuitionRequest}/complete', [AdminController::class, 'completeTuitionRequest'])
        ->name('admin.tuition-requests.complete');
});

Route::middleware(['auth', 'role:instructor'])->group(function () {
    Route::get('/dashboard', [ScheduleController::class, 'dashboard'])->name('instructor.dashboard');

    Route::resource('students', StudentController::class)->except(['show']);

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/attendance', [CalendarController::class, 'toggleAttendance'])->name('calendar.attendance.toggle');
    Route::post('/calendar/tuition-requests', [CalendarController::class, 'requestTuition'])->name('calendar.tuition.request');

    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule/sessions', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::post('/schedule/sessions/move', [ScheduleController::class, 'moveByForm'])->name('schedule.move.form');
    Route::put('/schedule/sessions/{classSession}', [ScheduleController::class, 'move'])->name('schedule.move');
    Route::delete('/schedule/sessions/{classSession}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
});
