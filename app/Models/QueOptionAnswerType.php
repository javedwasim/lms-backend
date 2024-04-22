<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueOptionAnswerType extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'ques_option_answer_type_tbl';

    protected $fillable = [
        'question_id','answer_type_name','created_at','updated_at','deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];
 
}