<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempOrder extends Model
{
    use HasFactory;
  
    protected  $table = 'temp_order_tbl';

    protected $fillable = [
        'user_id','package_for','total_amount','billing_address','post_code','card_no','card_expiry','transaction_id','payment_status','created_at','updated_at'
    ];

    public function orderDetails()
    {
        return $this->hasMany(TempOrderDetail::class, 'order_id', 'id');
    }
}