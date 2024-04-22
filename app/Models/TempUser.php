<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempUser extends Model
{
    use HasFactory;
  
    protected  $table = 'temp_user';

    protected $fillable = [
        'user_name','email','country_code','country_flag_code','phone','concated_phone','is_verified','device_token','latitude','longitude','created_at','updated_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at', 
    ]; 
}