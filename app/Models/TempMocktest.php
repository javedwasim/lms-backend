<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempMocktest extends Model
{
    use HasFactory;
  
   

    protected $fillable = [
        'user_id','course_id','category_id','sub_category_ids','question_type','correct_option_json','question_id','answer','is_correct','created_at','updated_at','mocktest_id'
    ];
}