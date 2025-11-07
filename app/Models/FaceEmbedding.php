<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceEmbedding extends Model
{
    use HasFactory;

    protected $table = 'face_embeddings';

    protected $fillable = [
        'student_id', 
        'embedding_vector',
    ];

    protected $casts = [
        'embedding_vector' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}