<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempMocktestSrQuestion extends Model
{
    use HasFactory;
  
   

    protected $fillable = [
        'sr_no','question_id','user_id'
    ];
}