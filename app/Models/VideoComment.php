<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Course;
use App\Models\Category;

class VideoComment extends Model
{
    use HasFactory, SoftDeletes;



    protected $guarded = [];

    protected $hidden = [

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

    public function adminComments()
    {
        return $this->hasMany(VideoComment::class, 'parent_id')->where('admin_reply', 1)->orderBy('likecount', 'DESC');
    }

    public function getUserVideoComments($user_id, $tutorial_id)
    {
        $userTutorialNotes = self::where('user_id', $user_id)
            ->where('tutorial_id', $tutorial_id)
            ->get();

        return response()->json(['userTutorialNotes' => $userTutorialNotes]);
    }
}
