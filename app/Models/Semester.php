<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'academic_year_id',
        'start_date', 
        'end_date',   
        'is_active',  
    ];

    protected $casts = [
        'start_date' => 'date',   
        'end_date'   => 'date',   
        'is_active'  => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}