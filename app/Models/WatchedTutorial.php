<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class WatchedTutorial extends Model
{
    use HasFactory;
  
    protected  $table = 'watched_tutorial';

    protected $fillable = [
        'user_id','course_id','tutorial_id','category_id','total_video_time','watched_time','created_at','updated_at'
    ];

    protected $hidden = [ 
        'updated_at',
        'deleted_at', 
    ];

    public function tutorial()
    {       
        return $this->belongsTo(Tutorial::class,"tutorial_id","id");
    }

    public function course()
    {       
        return $this->belongsTo(Course::class,"course_id","id");
    }

    public function category()
    {       
        return $this->belongsTo(Category::class,"category_id","id");
    }
}