<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reportissue extends Model
{
    use HasFactory;
    protected  $table = 'report_issue';

    protected $fillable = [
        'user_id','question_id','options','email','description','created_at','updated_at'
    ];

    public function userData()
    {       
        return $this->hasOne(User::class,'id', 'user_id');

    } 
    
    public function questionData()
    {       
        return $this->belongsTo(QuestionAnswer::class,"question_id");
    } 
}