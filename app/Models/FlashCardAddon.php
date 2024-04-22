<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
  
class FlashCardAddon extends Model
{
    use HasFactory;
    protected $hidden=['updated_at','deleted_at'];
  
}