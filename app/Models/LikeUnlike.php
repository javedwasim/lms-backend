<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class LikeUnlike extends Model
{
    use HasFactory;
  
    protected  $table = 'like_unlike_tbl';

    protected $fillable = [
        'user_id','question_id','like_unlike_status','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
 
}