<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function isPlanExist($course_id)
    {
        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => auth()->user()->id])
            ->count();

            return $check_plan > 0;
    }
}
