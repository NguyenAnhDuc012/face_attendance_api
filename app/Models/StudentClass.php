<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentClass extends Model
{
    protected $fillable = [
        'name',
    ];

    use HasFactory;
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
