<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TuitionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'student_id',
        'lesson_count',
        'lesson_dates',
        'status',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'lesson_dates' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
