<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AttemptQuestion;
use App\Models\Order;
use App\Models\Category;
use App\Models\Course;

class TutorialOrder extends Model
{
    use HasFactory;

    protected  $table = 'tutorial_orders';

    protected $fillable = [
        'category_name', 'time', 'created_at', 'updated_at', 'deleted_at', 'sort'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'status_name'
    ];

    public function getStatusNameAttribute()
    {
        $status_id = $this->status;

        if ($status_id == '1')
            $status = "Active";
        else
            $status = "InActive";

        return $status;
    }

    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class, 'tutorial_id', 'id');
    }
}
