<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paragraph extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'question_paragraph_tbl';

    protected $fillable = [
        'paragraph','created_at','updated_at','deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ]; 
}