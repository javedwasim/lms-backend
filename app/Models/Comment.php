<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class Comment extends Model
{
    use HasFactory;
  
    protected  $table = 'comment_tbl';

    protected $fillable = [
        'user_id','question_id','user_name','comment','created_at','updated_at'
    ];

    protected $hidden = [
      
        'updated_at',
    ];

    public function user()
    {       
        return $this->belongsTo(\App\Models\User::class,"user_id","id");
    } 
    public function question()
    {       
        return $this->belongsTo(\App\Models\QuestionAnswer::class);
    } 
    public function likes()
    {       
        return $this->hasMany(\App\Models\CommentLike::class)->where("type","like");
    }
    public function disLikes()
    {       
        return $this->hasMany(\App\Models\CommentLike::class)->where("type","dislike");
    }  
 
    public function adminComments()
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('admin_reply', 1)->orderBy('likecount', 'DESC');
    }
}