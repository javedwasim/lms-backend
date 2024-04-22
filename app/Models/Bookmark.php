<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;
  
    protected  $table = 'bookmark_tutorial';

    protected $fillable = [
        'user_id','tutorial_id','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}