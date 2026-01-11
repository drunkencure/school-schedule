<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LessonAttendance;
use App\Models\TuitionRequest;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'name',
        'registered_at',
        'billing_cycle_count',
        'last_billed_lesson_date',
    ];

    protected $casts = [
        'registered_at' => 'date',
        'last_billed_lesson_date' => 'date',
        'billing_cycle_count' => 'integer',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function classSessions()
    {
        return $this->belongsToMany(ClassSession::class, 'class_session_student')->withTimestamps();
    }

    public function lessonAttendances()
    {
        return $this->hasMany(LessonAttendance::class);
    }

    public function tuitionRequests()
    {
        return $this->hasMany(TuitionRequest::class);
    }
}
