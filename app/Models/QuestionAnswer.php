<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use URL;

class QuestionAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected  $table = 'question_answer_tbl';

    protected $fillable = [
        'course_id', 'category_id', 'sub_category_ids', 'question_type', 'paragraph', 'tutorial_id', 'question_name', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'option_f', 'correct_answer', 'status', 'test_mode',
        'question_img', 'option_a_img', 'option_b_img', 'option_c_img', 'option_d_img', 'option_e_img', 'option_f_img', 'paragraph_img',
        'question_tags', 'created_at', 'updated_at', 'deleted_at', 'explanation_video', 'explanation', 'course_type_id', 'option_g', 'option_g_img', 'option_h', 'option_h_img', 'option_i', 'option_i_img', 'option_j', 'option_j_img'
    ];


    protected $hidden = [

        'updated_at',
        'deleted_at',
    ];

    // question_img
    //option_a_img
    //  option_b_img
    // option_c_img
    // option_d_img
    // option_e_img
    // option_f_img

    // protected $appends = ['status_name','question_type_name','question_option','option_answer_type'];
    protected $appends = ['status_name', 'question_type_name', 'question_option', 'option_answer_type', 'question_selected_mock', 'question_selected', 'comments', 'video_links'];

    public function course_detail()
    {
        return $this->hasOne(Course::class, 'id', 'course_id');
    }
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_questions', 'question_id', 'course_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function category_detail()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function sub_category()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_ids', 'id');
    }

    public function attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'question_id', 'id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_questions', 'question_id', 'package_id');
    }

    public function getQuestionOptionAttribute()
    {
        return QueOption::where('question_id', $this->id)->get()->toArray();
    }

    public function getOptionAnswerTypeAttribute()
    {
        return QueOptionAnswerType::where('question_id', $this->id)->get()->toArray();
    }

    public function getQuestionSelectedAttribute()
    {
        $user_id = Auth::id();

        $question_id = $this->id;

        $data = TempTest::where(['user_id' => $user_id, 'question_id' => $question_id])->first(['answer', 'correct_option_json']);

        return $data;
    }



    public function getCommentsAttribute()
    {
        $question_id = $this->id;
        $result = array();
        $allData = Comment::where('question_id', $question_id)->where('admin_reply', null)->orderBy("likeCount", "DESC")->orderBy("disLikeCount", "asc")->get(['comment', 'admin_reply', 'is_name_display', 'question_id', 'user_name', 'id', 'user_id', 'created_at']);
        foreach ($allData as $key => $val) {
            $adminReply = Comment::where('parent_id', $val->id)->where('admin_reply', 1)->orderBy("likeCount", "DESC")->orderBy("disLikeCount", "asc")->get(['comment', 'admin_reply', 'is_name_display', 'question_id', 'user_name', 'id', 'likeCount', 'disLikeCount', 'user_id', 'created_at']);
            $adminData = array();
            foreach ($adminReply as $key1 => $admin) {
                $userDetail = User::find($admin->user_id);
                $adminData[$key1] = $admin;
                $adminData[$key1]['user'] = $userDetail;
            }
            $userDetailcomment = User::find($val->user_id);

            $result[$key]['comment_id'] = $val->id;
            $result[$key]['comment'] = $val->comment;
            $result[$key]['created_at'] = $val->created_at;
            $result[$key]['admin_reply'] = $val->admin_reply;
            $result[$key]['is_name_display'] = $val->is_name_display;
            $result[$key]['question_id'] = $val->question_id;
            $result[$key]['user_name'] = $val->user_name;
            $result[$key]['profile_photo_path'] = @$userDetailcomment->profile_photo_path;
            $result[$key]['adminReply'] = $adminData;
            $result[$key]['likeCount'] = $val->likes->count();
            $result[$key]['disLikeCount'] = $val->disLikes->count();
        }

        return $result;
    }

    public function getVideoLinksAttribute()
    {
        if (!empty($this->explanation_video)) {
            $video = URL::to('uploads/' . $this->explanation_video);
            return $video;
        } else {
            return '';
        }
    }
    public function sub_category_detail()
    {
        return $this->hasOne(SubCategory::class, 'id', 'sub_category_ids');
    }

    public function tutorial_detail()
    {
        return $this->hasOne(Tutorial::class, 'id', 'tutorial_id');
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

    public function getQuestionTypeNameAttribute()
    {
        $question_type = $this->question_type;

        if ($question_type == '1')
            $type_name = "Question on left And Option on right";
        else if ($question_type == '2')
            $type_name = "Drag and Drop";
        else if ($question_type == '3')
            $type_name = "No division";
        else if ($question_type == '4')
            $type_name = "Type 4";
        else if ($question_type == '5')
            $type_name = "Type 5";
        else
            $type_name = "";

        return $type_name;
    }
    public function getQuestionSelectedMockAttribute()
    {

        $user_id = \Auth::id();

        $question_id = $this->id;
        $data =    TempMocktest::where(['user_id' => $user_id, 'question_id' => $question_id])->get(['answer', 'correct_option_json'])->toArray();
        $res = [];
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $answer = '';
                if (!empty($val['answer'])) {
                    if ($val['answer'] != 'undefined') {
                        $answer = $val['answer'] ?? '';
                    }
                }

                $row = [];
                $row['answer'] = $answer ?? '';
                $row['correct_option_json'] = $val['correct_option_json'] ?? '';
                $res[] = $row;
            }
        }
        return $res;
    }
}
