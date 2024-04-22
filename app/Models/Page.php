<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class Page extends Model
{
    use HasFactory;
  
    protected  $table = 'pages';

    protected $fillable = [
        'page_name','page_content','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at', 
    ];  
}