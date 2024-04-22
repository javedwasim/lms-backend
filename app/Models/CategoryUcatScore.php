<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Category; 
use App\Models\Band; 
use App\Models\CourseType; 

class CategoryUcatScore extends Model
{
    use HasFactory,SoftDeletes;
  
    public function category()
    {       
        return $this->belongsTo(Category::class);
    }
    public function band()
    {       
        return $this->belongsTo(Band::class);
    }
    public function courseType()
    {       
        return $this->belongsTo(CourseType::class);
    }
}