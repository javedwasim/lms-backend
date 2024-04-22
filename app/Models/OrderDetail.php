<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetail extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'order_detail';

    protected $fillable = [
        'order_id','particular_record_id','package_id','package_for','price','expiry_date','is_plan_expired','created_at'
    ];
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }
    public function order(){
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}