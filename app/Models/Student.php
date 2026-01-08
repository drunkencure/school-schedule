<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'name',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function classSessions()
    {
        return $this->belongsToMany(ClassSession::class, 'class_session_student')->withTimestamps();
    }
}
