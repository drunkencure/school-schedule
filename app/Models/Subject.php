<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Academy;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
    ];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'instructor_subject')->withTimestamps();
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }
}
