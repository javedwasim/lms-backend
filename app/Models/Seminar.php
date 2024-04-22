<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
  
class Seminar extends Model
{
    use HasFactory;
    protected $hidden=['updated_at','deleted_at'];
    public function addOns()
    {
        return $this->hasMany(\App\Models\SeminarAddon::class);
    }
    public function tutor()
    {
        return $this->hasMany(\App\Models\SeminarTutor::class);
    }
    public function testimonial()
    {
        return $this->hasMany(\App\Models\SeminarTestimonial::class);
    }
   /*  public function getStartTimeAttribute()
    {
        return $this->start_time ? $this->start_time : '';
    }
    public function getEndTimeAttribute()
    {
        return $this->end_time ? $this->end_time : '';
    } */
  
}