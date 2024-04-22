<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\AttemptQuestion;
use App\Models\Order;
use App\Models\Course;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected  $table = 'category_tbl';

    protected $fillable = [
        'category_name', 'time', 'created_at', 'updated_at', 'deleted_at', 'sort'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'status_name'
    ];

    function subCategory()
    {
        return $this->hasMany(SubCategory::class, 'category_id')->where('status', 1)->orderBy('id', 'ASC');
    }
    
    function sub_categories()
    {
        return $this->hasMany(SubCategory::class, 'category_id')->where('status', 1);
    }
    
    public function questions()
    {
        return $this->hasMany(QuestionAnswer::class, 'category_id');
    }

    public function attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id');
    }

    public function correct_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id')->where('is_correct', 1);
    }

    public function attempted_questions_by_user()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id');
    }

    public function correct_attempted_questions_by_user()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id')->where('is_correct', 1);
    }

    public function user_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id')->where('user_id', auth()->user()->id);
    }

    public function user_correct_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id')->where(['user_id' => auth()->user()->id, 'is_correct' => 1]);
    }

    public function courses()
    {
        return $this->belongsToMany(CourseCategory::class, 'course_categories', 'category_id', 'course_id');
    }
    
    public function incorrect_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'category_id', 'id')->where('is_correct', 0);
    }

    public function tutorials()
    {
        return $this->hasMany(Tutorial::class, 'category_id', 'id');
    }

    public function watched_tutorials()
    {
        return $this->hasMany(WatchedTutorial::class, 'category_id', 'id');
    }

    //get QuestionTempFilter 
    function QuestionTempFilter()
    {
        return $this->belongsTo('App\Models\QuestionTempFilter', 'category_id')->where('user_id', Auth::user()->id);
    }

    public function getStatusNameAttribute()
    {
        $status_id = $this->status;

        if ($status_id == '1')
            $status = "Active";
        else
            $status = "InActive";

        return $status;
    }

    // zain - function for getting score question list
    public static function score_question_list($user_id, $course_id)
    {
        $scoredata = [];

        // get all the category and subcategory of the attempted questions of user
        $allScoreCategoryUser = AttemptQuestion::selectRaw('category_id AS cat_id, sub_category_ids AS subid')->where(['user_id' => $user_id, 'course_id' => $course_id])->groupBy('category_id', 'sub_category_ids')->get()->toArray();

        if (!empty($allScoreCategoryUser)) {
            foreach ($allScoreCategoryUser as $key => $val) {
                $cat_id = $val['cat_id'];
                $subid = $val['subid'];

                // for each category and subcategory get the total questions and total correct questions
                $sub_totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $cat_id, 'sub_category_ids' => $subid])->get()->count();

                $sub_totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $cat_id, 'sub_category_ids' => $subid, 'is_correct' => '1'])->get()->count();

                if (!empty($sub_totalQuestions)) {
                    // calculate the score for each category and subcategory in percentage
                    if ($sub_totalMyAttemptCorrect > 0 && $sub_totalQuestions > 0)
                        $sub_your_score =  ($sub_totalMyAttemptCorrect * 100) / $sub_totalQuestions;
                    else
                        $sub_your_score = 0;

                    // get the ids of attempted questions for each category and subcategory
                    $questionsList = AttemptQuestion::select('question_id')->where(
                        [
                            'user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $cat_id, 'sub_category_ids' => $subid
                        ]
                    )->get()->toArray();;

                    $row = [];
                    $row['questionids'] = $questionsList;
                    $row['score'] = $sub_your_score;
                    $scoredata[] = $row;
                }
            }
        }

        $questionListArray = [];
        $questionsList = [];

        if (!empty($scoredata)) {
            $col = array_column($scoredata, "score");
            array_multisort($col, SORT_ASC, $scoredata);  /// sort assending by score 
            foreach ($scoredata as $key => $val) {
                $questionListArray[] = $val['questionids'];
            }
        }

        // pick the 5 categories and subcategories with lowest score
        $questionListArray = !empty($questionListArray) ? array_slice($questionListArray, 0, 5) : [];

        // merge questions to single array
        if (!empty($questionListArray)) {
            foreach ($questionListArray as $key => $val) {

                if (!empty($val)) {
                    foreach ($val as $o => $b) {
                        $questionsList[] = $b['question_id'];
                    }
                }
            }
        }

        return !empty($questionsList) ? implode(',', $questionsList) : "";
    }
}
