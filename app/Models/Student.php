<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'full_name',
        'dob',
        'class_id',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }
}
