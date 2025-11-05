<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = [
        'name',
        'facility_id',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
