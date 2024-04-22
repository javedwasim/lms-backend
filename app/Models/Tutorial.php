<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Course;
use App\Models\Category;

class Tutorial extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'tutorial_tbl';

    protected $guarded=[];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];

    protected $appends = ['status_name'];
    
    public function course_detail()
    {       
        return $this->hasOne(Course::class, 'id','course_id');
    } 

    public function category_detail()
    {       
        return $this->hasOne(Category::class, 'id','category_id');
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

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_tutorials', 'tutorial_id', 'course_id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_tutorials', 'tutorial_id', 'package_id');
    }

    public function watched_tutorials()
    {
        return $this->hasMany(WatchedTutorial::class, 'tutorial_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(VideoComment::class, 'tutorial_id', 'id');
    }

    public function assign_tutorials()
    {
        return $this->hasMany(AssignTutorial::class, 'tutorial_id', 'id');
    }

    public function tutorial_notes()
    {
        return $this->hasMany(TutorialNote::class, 'tutorial_id', 'id');
    }

    public function bookmark_tutorials()
    {
        return $this->hasMany(Bookmark::class, 'tutorial_id', 'id');
    }

    public function tutorial_files()
    {
        return $this->hasMany(TutorialFile::class, 'tutorial_id', 'id');
    }

    public function course_tutorials()
    {
        return $this->belongsToMany(Course::class, 'course_tutorials', 'tutorial_id', 'course_id');
    }
}