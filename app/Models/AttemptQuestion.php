<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttemptQuestion extends Model
{
    use HasFactory;
  
    protected  $table = 'attempt_questions';

    protected $fillable = [
        'user_id','course_id','tutorial_id','category_id','sub_category_ids','question_type','correct_option_json','question_id','answer','is_correct','created_at','updated_at'
    ];
}