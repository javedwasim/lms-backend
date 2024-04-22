<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueOption extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'ques_option_tbl';

    protected $fillable = [
        'question_id','option_name','option_value_id','correct_option_answer','created_at','updated_at','deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];
 
}