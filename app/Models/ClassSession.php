<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'subject_id',
        'weekday',
        'start_time',
        'end_time',
        'is_group',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_group' => 'boolean',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_session_student')->withTimestamps();
    }
}
