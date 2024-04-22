<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssingQuestionMocktest extends Model
{
    use HasFactory;
    public function category()
    {
        return $this->belongsTo(Category::class,"category_id","id");
    }
    public function question()
    {
        return $this->belongsTo(QuestionAnswer::class,"question_id","id");
    }
    
}