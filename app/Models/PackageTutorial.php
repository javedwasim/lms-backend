<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageTutorial extends Model
{
    use HasFactory;
    protected $fillable = [
        'package_id',
        'tutorial_id'
    ];
    public $timestamps = false; // Disable timestamps

}
