<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
  
class FlashCard extends Model
{
    use HasFactory;
    protected $hidden=['updated_at','deleted_at'];
    public function addOns()
    {
        return $this->hasMany(\App\Models\FlashCardAddon::class);
    }
    public function tutor()
    {
        return $this->hasMany(\App\Models\FlashCardTutor::class);
    }
    public function testimonial()
    {
        return $this->hasMany(\App\Models\FlashCardTestimonial::class);
    }
  
}