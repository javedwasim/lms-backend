<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Course;
use App\Models\Category;

class VideoCommentLike extends Model
{
    use HasFactory,SoftDeletes;
  
  

    protected $guarded=[];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];

   /**
    * Get the user that owns the VideoComment
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
   public function user()
   {
       return $this->belongsTo(User::class, 'user_id', 'id');
   }
}