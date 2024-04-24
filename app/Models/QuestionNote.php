<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'quize_note',
    ];

}
