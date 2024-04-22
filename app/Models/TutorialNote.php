<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorialNote extends Model
{
    use HasFactory;

    protected  $table = 'user_tutorial_notes';

    protected $fillable = [
        'user_id','tutorial_id','notes','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getUserTutorials($user_id, $tutorial_id)
    {
        $userTutorialNotes = self::where('user_id', $user_id)
            ->where('tutorial_id', $tutorial_id)
            ->get();

        return response()->json(['userTutorialNotes' => $userTutorialNotes]);
    }
}
