<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'check_in_time',
        'status',
        'source',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }
}