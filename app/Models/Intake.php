<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intake extends Model
{
    protected $fillable = [
        'name',
        'start_year',
        'expected_graduation_year',
    ];
}
