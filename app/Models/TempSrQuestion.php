<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempSrQuestion extends Model
{
    use HasFactory;
  
    protected  $table = 'temp_sr_no_question_tbl';

    protected $fillable = [
        'sr_no','question_id','user_id'
    ];
}