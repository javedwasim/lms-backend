<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'order_tbl';

    protected $fillable = [
        'user_id','package_for','total_amount','billing_address','post_code','card_no','card_expiry','transaction_id','payment_status','created_at','updated_at'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
   
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

}