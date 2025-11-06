<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash; 

class Student extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;


    protected $table = 'students';

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

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    
    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }
}