<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressSetting extends Model
{
    use HasFactory;
  
    protected  $table = 'progress_bar_setting';

    protected $fillable = [
        'no_of_count','color','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}