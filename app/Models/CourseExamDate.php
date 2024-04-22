<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseExamDate extends Model
{
    use HasFactory;
  
    protected  $table = 'user_exam_date_tbl';

    protected $fillable = [
        'user_id','course_id','exam_date','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}