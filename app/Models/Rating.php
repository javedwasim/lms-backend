<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class Rating extends Model
{
    use HasFactory;
  
    protected  $table = 'rating_tbl';

    protected $fillable = [
        'user_id','question_id','rating','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function user()
    {       
        return $this->belongsTo(\App\Models\User::class);
    } 
    public function question()
    {       
        return $this->belongsTo(\App\Models\QuestionAnswer::class);
    } 
 
}