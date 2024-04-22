<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\QuestionAnswer;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, QuestionAnswer $question)
    {
        $request->validate([
            'rating' => 'required',
        ]);

        $rating = Rating::firstOrCreate(
            ['user_id' => auth()->user()->id, 'question_id' => $question->id],
            ['rating' => $request->rating]
        );
        
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [],
            'message' => $rating->wasRecentlyCreated ? "Rating Added Successful" : "Rating Update Successful"
        ]);
    }
}
