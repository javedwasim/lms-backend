<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempBeforeFinishTest extends Model
{
    use HasFactory;
  
    protected  $table = 'temp_before_finish_test';

    protected $fillable = [
        'user_id','questions_id','question_for_review','skip_question','created_at','updated_at'
    ];
}