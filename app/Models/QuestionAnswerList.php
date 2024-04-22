<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionAnswerList extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'question_answer_tbl';

    protected $fillable = [
        'course_id','category_id','sub_category_ids','paragraph','tutorial_id','question_name','option_a','option_b','option_c','option_d','option_e','option_f','correct_answer','status','created_at','updated_at','deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];
 
    protected $appends = ['status_name','question_type_name'];
    
    public function course_detail()
    {       
        return $this->hasOne(Course::class, 'id','course_id');
    } 

    public function category_detail()
    {       
        return $this->hasOne(Category::class, 'id','category_id');
    } 
    
    public function subcategories()
    {
        return $this->belongsToMany(SubCategory::class, 'question_sub_categories', 'question_id', 'sub_category_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_questions', 'question_id', 'course_id');
    }

    public function getStatusNameAttribute()
    {      
        $status_id = $this->status;

        if($status_id=='1')
           $status = "Active"; 
        else 
            $status = "InActive";

        return $status;
    } 

    public function getQuestionTypeNameAttribute()
    {      
        $question_type = $this->question_type;

        if($question_type=='1')
           $type_name = "Question on left And Option on right"; 
        else if($question_type=='2')
           $type_name = "Drag and Drop"; 
        else if($question_type=='3')
           $type_name = "No division"; 
        else if($question_type=='4')
           $type_name = "Type 4"; 
        else if($question_type=='5')
           $type_name = "Type 5"; 
        else 
            $type_name = "";

        return $type_name;
    } 
}