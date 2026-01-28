<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Academy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'memo',
    ];

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'academy_user')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'academy_student')->withTimestamps();
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }
}
