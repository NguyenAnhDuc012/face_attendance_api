<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'class_id',
        'study_period_id',
        'lecturer_id',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function studyPeriod()
    {
        return $this->belongsTo(StudyPeriod::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
