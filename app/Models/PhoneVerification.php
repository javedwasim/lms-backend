<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class PhoneVerification extends Model
{
    use HasFactory;
  
    protected  $table = 'phone_verifications'; 
    
    protected $guarded = [];

    protected $casts = [
        'created_at'  => 'datetime:d M, Y, H:i A',
        'updated_at' => 'datetime:d M, Y, H:i A',
    ];
}