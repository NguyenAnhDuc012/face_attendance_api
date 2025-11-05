<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyPeriod extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'semester_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
