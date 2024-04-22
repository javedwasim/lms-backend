<?php

namespace App\Http\Controllers\Api\Tutorial;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\VideoComment;
use App\Models\VideoCommentLike;
use Illuminate\Http\Request;

class TutorialCommentController extends Controller
{
    public function index(Tutorial $tutorial)
    {
        $videoComments = VideoComment::with(['user', 'adminComments.user'])
            ->where('tutorial_id', $tutorial->id)
            ->where('parent_id', 0)
            ->orderByDesc('likecount')
            ->get();

        $allComments = [];
        foreach ($videoComments as $cmtKey => $cmt) {
            $adminData = [];
            foreach ($cmt->adminComments as $key1 => $admin) {
                $adminData[$key1] = $admin;
                $adminData[$key1]['user'] = $admin->user;
            }
            $allComments[$cmtKey] = $cmt;
            $allComments[$cmtKey]['adminComment'] = $adminData;
        }

        return response()->json(['statusCode' => 200, 'message' => 'Comment list Successfully', 'data' => $allComments], 200);
    }

    public function store(Request $request, Tutorial $tutorial)
    {
        $request->validate([
            'comment' => 'required',
        ]);

        $comment = $request->comment;
        $userId = auth()->user()->id;
        $VideoComment = new VideoComment();
        $VideoComment->user_id = $userId;
        $VideoComment->tutorial_id = $tutorial->id;
        $VideoComment->comment = $comment;
        $VideoComment->status = 1;
        $VideoComment->save();
        return response()->json(['statusCode' => 200, 'message' => 'Comment Added Successfully', 'data' => $VideoComment], 200);
    }

    public function videoCommentLike(Request $request)
    {
        $comment = $request->id;
        $type = $request->type;
        $userId = auth()->user()->id;
        $checkData = VideoCommentLike::where("video_comment_id", $comment)->where("user_id", $userId)->first();
        $VideoComment = [];
        if (!empty($checkData)) {
            if ($checkData->type != $type) {
                if ($type == "like") {
                    VideoComment::where("id", $checkData->video_comment_id)->increment('likecount');
                    VideoComment::where("id", $checkData->video_comment_id)->decrement('unlikecount');
                } else {
                    VideoComment::where("id", $checkData->video_comment_id)->decrement('likecount');
                    VideoComment::where("id", $checkData->video_comment_id)->increment('unlikecount');
                }
                $VideoComment =  VideoCommentLike::find($checkData->id);
                $VideoComment->user_id = $userId;
                $VideoComment->video_comment_id = $comment;
                $VideoComment->type = $type;

                $VideoComment->save();
            }
        } else {
            if ($type == "like") {
                VideoComment::where("id", $comment)->increment('likecount');
            } else {

                VideoComment::where("id", $comment)->increment('unlikecount');
            }
            $VideoComment = new VideoCommentLike();
            $VideoComment->user_id = $userId;
            $VideoComment->video_comment_id = $comment;
            $VideoComment->type = $type;

            $VideoComment->save();
        }

        return response()->json(['statusCode' => 200, 'message' => 'Comment ' . $type . ' Successfully', 'data' => $VideoComment], 200);
    }


}
