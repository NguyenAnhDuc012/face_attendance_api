<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use HasFactory;


    protected $fillable = [
        'schedule_id',
        'session_date',
        'attendance_mode',
        'status',
        'started_at',
        'ended_at',
        'qr_token',
        'qr_token_expires_at',
    ];


    protected $casts = [
        'session_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'qr_token_expires_at' => 'datetime',
        'attendance_mode' => 'string',
        'status' => 'string',
    ];


    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }
}
