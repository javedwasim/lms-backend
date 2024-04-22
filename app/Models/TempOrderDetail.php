<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempOrderDetail extends Model
{
    use HasFactory;
  
    protected  $table = 'temp_order_detail';

    protected $fillable = [
        'order_id','particular_record_id','package_id','package_for','price','expiry_date','is_plan_expired','created_at'
    ];

    public function tempOrder()
    {
        return $this->belongsTo(TempOrder::class, 'order_id', 'id');
    }
}