<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\QuestionAnswer;
use App\Models\User;
use App\Models\VideoComment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(QuestionAnswer $question)
    {
        $questionComments = Comment::with(['user', 'adminComments.user'])
            ->where('question_id', $question)
            ->where('parent_id', 0)
            ->orderBy('likecount', 'DESC')
            ->get();

        $allComments = [];
        foreach ($questionComments as $cmtKey => $cmt) {
            $adminData = [];
            foreach ($cmt->adminComments as $key1 => $admin) {
                $adminData[$key1] = $admin;
                $adminData[$key1]['user'] = $admin->user;
            }
            $allComments[$cmtKey] = $cmt;
            $allComments[$cmtKey]['adminComment'] = $adminData;
        }

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $allComments,
            'message' => 'Comment list'
        ]);
    }

    public function store(Request $request, QuestionAnswer $question)
    {
        $request->validate([
            'user_name' => 'required',
            'comment' => 'required',
        ]);

        $row = new Comment();
        $row->user_id = auth()->user()->id;
        $row->user_name = $request->user_name;
        $row->question_id = $question->id;
        $row->comment = $request->comment;
        $row->is_name_display = (int) $request->is_name_display;;
        $row->save();

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [],
            'message' => "Comment Added Successfully"
        ]);
    }
}
