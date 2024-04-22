<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected  $table = 'course_tbl';

    protected $fillable = [
        'course_name', 'course_image', 'banner_content', 'banner_link', 'popup_content', 'popup_course_image', 'popup_link', 'created_at', 'updated_at', 'deleted_at', 'categories', 'coursetypeid'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $appends = [
        'status_name'
    ];

    public function getStatusNameAttribute()
    {
        $status_id = $this->status;

        if ($status_id == '1')
            $status = "Active";
        else
            $status = "InActive";

        return $status;
    }
    public function course_type()
    {
        return $this->belongsTo(CourseType::class, "course_type_id", "id");
    }

    public function tutorials()
    {
        return $this->belongsToMany(Tutorial::class, 'course_tutorials', 'course_id', 'tutorial_id');
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class, 'particular_record_id', 'id');
    }

    public function watched_tutorials()
    {
        return $this->hasMany(WatchedTutorial::class, 'course_id', 'id');
    }

    public function packages()
    {
        return $this->hasMany(Package::class, 'perticular_record_id', 'id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'course_categories')->where('status', 1);
    }

    public function categories_detail()
    {
        return $this->belongsToMany(Category::class, 'course_categories')->where('status', 1);
    }

    public function attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'course_id', 'id');
    }

    public function correct_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'course_id', 'id')->where('is_correct', 1);
    }

    public function incorrect_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'course_id', 'id')->where('is_correct', 0);
    }

    public function questions()
    {
        return $this->belongsToMany(QuestionAnswer::class, 'course_questions', 'course_id', 'question_id');
    }

    public function assignQuestions()
    {
        return $this->belongsToMany(AssignQuestion::class, 'assign_question_to_course_tbl');
    }

    public function block_popups()
    {
        return $this->hasMany(BlockPopup::class, 'course_id', 'id');
    }

    public function questions_by_category()
    {
        return $this->belongsToMany(Category::class, 'course_categories')->where('status', 1)
        ->with('sub_categories' , function($query){
            $query->where('status', 1)->withCount([
                'attempted_questions',
                'correct_attempted_questions',
                'user_attempted_questions',
                'user_correct_attempted_questions',
            ]);
        })
        ->withCount([
            'attempted_questions',
            'correct_attempted_questions',
            'user_attempted_questions',
            'user_correct_attempted_questions',
        ]);
    }

    public function user_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'course_id', 'id')->where('user_id', auth()->user()->id);
    }

    public function user_correct_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'course_id', 'id')->where(['user_id' => auth()->user()->id, 'is_correct' => 1]);
    }
    
    public function tutorials_by_category()
    {
        return $this->belongsToMany(Category::class, 'course_categories')->where('status', 1);
    }

    public function tips()
    {
        return $this->hasMany(Tips::class, 'course_id', 'id');
    }

    public function supports()
    {
        return $this->hasMany(PersonalSupport::class, 'course_id', 'id');
    }

    public function exam_date()
    {
        return $this->hasMany(CourseExamDate::class, 'course_id', 'id');
    }
}
