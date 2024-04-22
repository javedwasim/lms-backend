<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tips extends Model
{
    use HasFactory;
  
    protected  $table = 'tips_tbl';

    protected $fillable = [
        'course_id','tip_title','description','type','tip_date','web_link','status','created_at','updated_at','course_type_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['status_name','tip_type','course_name'];
    
    public function getStatusNameAttribute()
    {      
        $status_id = $this->status;

        if($status_id=='1')
           $status = "Active"; 
        else 
            $status = "InActive";

        return $status;
    } 

    public function getTipTypeAttribute()
    {      
        $type = $this->type;

        if($type=='2')
           $status = "Weekly Webinar"; 
        else if($type=='3')
           $status = "One Day Workshop"; 
        else
            $status = "Tip Of The Day";

        return $status;
    }

    public function course_detail()
    {       
        return $this->hasOne(Course::class, 'id','course_id');
    }

    public function getCourseNameAttribute()
    {    
        $course_id = $this->course_id;
        $getCourse = Course::where('id',$course_id)->first();   
        return $getCourse->course_name ?? "";
    }
}