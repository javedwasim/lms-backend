<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignTutorial extends Model
{
     use HasFactory;
  
    protected  $table = 'assign_tutorial_to_course_tbl';

    protected $fillable = [
        'course_id','tutorial_id','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}