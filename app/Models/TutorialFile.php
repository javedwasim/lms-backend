<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class TutorialFile extends Model
{
    use HasFactory;
  
   

    protected $hidden = [ 
        'updated_at',
        'deleted_at', 
    ];
    public function subfiles()
    {
        return $this->hasMany(TutorialSubfile::class, 'tutorial_file_id','id')->orderBy('position','asc');
    }
}