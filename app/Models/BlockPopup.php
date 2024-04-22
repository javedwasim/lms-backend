<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockPopup extends Model
{
    use HasFactory;
  
    protected  $table = 'block_popup';

    protected $fillable = [
        'user_id','course_id','type','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at', 
    ];

}