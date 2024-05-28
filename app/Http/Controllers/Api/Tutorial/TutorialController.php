<?php

namespace App\Http\Controllers\Api\Tutorial;

use App\Http\Controllers\Controller;
use App\Models\AssignTutorial;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Course;
use App\Models\Order;
use App\Models\Package;
use App\Models\Tips;
use App\Models\Tutorial;
use App\Models\TutorialFile;
use App\Models\TutorialNote;
use App\Models\TutorialOrder;
use App\Models\User;
use App\Models\VideoComment;
use App\Models\WatchedTutorial;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $req_data = [];

        $course_id = $course->id;
        $tutorial_id = $request->tutorial_id;
        $subcategoryIds = $request->subcategoryIds;
        $subcategoryIds = explode(",", $subcategoryIds);

        $courseDetailsRow = $course;

        $user_id = auth()->user()->id;
        $catArr = [];
        $catArrTutorial = [];

        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();

        $is_plan_exist = ($check_plan > 0) ? '1' : '0';

        if ($is_plan_exist == 0) {
            $freeCourse = $this->getfreecourse($user_id);
            if (in_array($course_id, $freeCourse)) {
                $is_plan_exist = 1;
            }
        }

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);
        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        $getCatgory = [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::with('subCategory')->where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::with('subCategory')->whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }

        $getCategoryIds = $getCatgory->pluck('id')->toArray();


        // $totalTutorialsQue = Tutorial::where(['status' => 1])->whereIn('category_id', $getCategoryIds)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
        $totalTutorialsQue = Tutorial::where(['status' => 1])->whereIn('category_id', $getCategoryIds);

        $totalTutorialsQue->whereHas('courses', function ($query) use ($course_id) {
            $query->where('course_id', $course_id);
        });

        $categories = Category::where(['status' => 1])->whereIn('id', $getCategoryIds)->withCount([
            'tutorials' => function ($query) use ($course_id) {
                $query->whereHas('courses', function ($query) use ($course_id) {
                    $query->where('course_id', $course_id);
                });
            },
            'watched_tutorials' => function ($query) use ($course_id, $user_id) {
                $query->where(['course_id' => $course_id, 'user_id' => $user_id]);
            }
        ])->orderBy('sort', 'asc')->get();

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();
        if (empty($getWeeklyWebinarDt)) {
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->first();
        }
        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : null;

        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : null;
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        $req_data['category_list'] = $categories;

        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($categories) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }

    public function store(Request $request, Tutorial $tutorial)
    {
        $request->validate([
            'note' => 'required',
        ]);

        $user_id = auth()->user()->id;
        $note = $request->note;

        $req_data = [];

        $newUser = TutorialNote::create([
            'user_id' => $user_id,
            'tutorial_id' => $tutorial->id,
            'notes' => $note,
        ]);
        $req_message = "Tutorial Note Added";

        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message], 200);
    }

    public function show(Request $request, Course $course, Category $category)
    {
        $req_data = [];

        $user_id = auth()->user()->id;
        $course_id = $course->id;
        $category_id = $category->id;

        $tutArr = [];

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where('user_id', $user_id)
            ->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];

        $is_plan_exist = (count($buyTutIdArr) > 0) ? '1' : '0';

        if ($is_plan_exist == 0) {
            $freeCourse = $this->getfreecourse($user_id);
            if (in_array($course_id, $freeCourse)) {
                $is_plan_exist = 1;
            }
        }

        $getTutorialOrders = TutorialOrder::where("course_id", $course_id)->where("category_id", $category_id)->with('tutorial')->orderBy("tutorialorder", "asc")->get();

        if (!empty($getTutorialOrders) && count($getTutorialOrders) > 0) {

            $getTutorialOrderIds = $getTutorialOrders->pluck('tutorial_id')->toArray();

            $getTutorialQue = Tutorial::orderBy('tutorialorder', 'asc')->where(['category_id' => $category_id, 'status' => 1])->whereIn('id', $getTutorialOrderIds)->whereHas('courses', function ($query) use ($course_id) {
                $query->where('course_id', $course_id);
            })->with(['comments', 'assign_tutorials' => function ($query) use ($course_id) {
                $query->where('course_id', $course_id);
            }, 'tutorial_notes' => function ($query) {
                $query->where('user_id', auth()->user()->id);
            }, 'bookmark_tutorials' => function ($query) {
                $query->where('user_id', auth()->user()->id);
            }, 'watched_tutorials' => function ($query) use ($course_id, $category_id) {
                $query->where(['user_id' => auth()->user()->id, 'course_id' => $course_id, 'category_id' => $category_id]);
            }]);

            if ($is_plan_exist == '1') {
                $getTutorialQue->whereIn('id', $buyTutIdArr);
            }

            $getTutorial = $getTutorialQue->first();

            $tutArr = [];
            $tutorialsCount = count($getTutorialOrders);
            foreach ($getTutorialOrders as $tutorialDetail) {
                if (!empty($getTutorial)) {


                    $tutArr[] = [
                        'tutorial_id' => $tutorialDetail->tutorial->id,
                        'test_mode' => $tutorialDetail->tutorial->assign_tutorials->count() > 0 ? '1' : '0',
                        'chapter_name' => $tutorialDetail->tutorial->chapter_name,
                        'video_url' => $tutorialDetail->tutorial->video_url,
                        'pdf_heading' => $tutorialDetail->tutorial->pdf_heading,
                        'custom_code' => $tutorialDetail->tutorial->custom_code,
                        'video_heading' => $tutorialDetail->tutorial->video_heading,
                        'video_pdf_order' => $tutorialDetail->tutorial->video_pdf_order,
                        'video_type' => $tutorialDetail->tutorial->video_type,
                        'pdf_url' => $tutorialDetail->tutorial->pdf_url,
                        'is_tutorial_bookmarked' => $tutorialDetail->tutorial->bookmark_tutorials->count() > 0 ? '1' : '0',
                        'total_video_time' => $tutorialDetail->tutorial->total_video_time,
                        'trans_script' => $tutorialDetail->tutorial->trans_script,
                        'my_total_video_time' => @$tutorialDetail->tutorial->watched_tutorials[0]->total_video_time,
                        'my_watched_time' => @$tutorialDetail->tutorial->watched_tutorials->where('user_id', $user_id)->first()->watched_time,
                        'comment' => @$tutorialDetail->tutorial->comments,
                        'files' => @$tutorialDetail->tutorial->tutorial_files,
                        'get_notes' => $tutorialDetail->tutorial->tutorial_notes,
                        'is_exist_in_plan' => $is_plan_exist,
                    ];
                }
            }
        } else {
            $getTutorialQue = Tutorial::orderBy('tutorialorder', 'asc')->where(['category_id' => $category_id, 'status' => 1])->whereHas('courses', function ($query) use ($course_id) {
                $query->where('course_id', $course_id);
            })->with(['comments', 'assign_tutorials' => function ($query) use ($course_id) {
                $query->where('course_id', $course_id);
            }, 'tutorial_notes' => function ($query) {
                $query->where('user_id', auth()->user()->id);
            }, 'bookmark_tutorials' => function ($query) {
                $query->where('user_id', auth()->user()->id);
            }, 'watched_tutorials' => function ($query) use ($course_id, $category_id) {
                $query->where(['user_id' => auth()->user()->id, 'course_id' => $course_id, 'category_id' => $category_id]);
            }]);

            if ($is_plan_exist == '1') {
                $getTutorialQue->whereIn('id', $buyTutIdArr);
            }

            $getTutorial = $getTutorialQue->get();

            $tutArr = [];
            $tutorialsCount = count($getTutorial);
            foreach ($getTutorial as $val) {
                //     $tutArr[] = $this->tutorialResponse($val, $user_id, $course_id, $category_id, $is_plan_exist);
                if (!empty($getTutorial)) {
                    //         $tutArr[] = $this->tutorialResponse($getTutorial, $user_id, $course_id, $category_id, $is_plan_exist);
                    // $getNote = TutorialNote::where(['user_id' => $user_id, 'tutorial_id' => $tutDt->id])->get(['id', 'notes']);
                    // $getNoteArr = ($getNote->count() > 0) ? $getNote->toArray() : [];

                    // $get_bookmarkCount = Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutDt->id])->count();
                    // $is_tutorial_bookmarked = ($get_bookmarkCount > 0) ? '1' : '0';

                    // $watched_tutorial =  WatchedTutorial::where(['course_id' => $course_id, 'category_id' => $category_id, 'user_id' => $user_id, 'tutorial_id' => $tutDt->id, 'user_id' => $user_id])->first();

                    // $check_test_mode = AssignTutorial::where(['course_id' => $course_id, 'tutorial_id' => $tutDt->id])->first(['id']);
                    // $is_test_mode = (isset($check_test_mode->id)) ? '1' : '0';
                    // $videoComment = VideoComment::where("tutorial_id", $tutDt->id)->where("parent_id", 0)->with("user")->orderBy("likecount", "DESC")->get();
                    // $allComment = array();
                    // foreach ($videoComment as $cmtKey => $cmt) {
                    //     $adminComment = VideoComment::where('parent_id', $cmt->id)->where('admin_reply', 1)->orderBy("likecount", "DESC")->get();
                    //     $adminData = array();
                    //     foreach ($adminComment as $key1 => $admin) {
                    //         $userDetail = User::find($admin->user_id);
                    //         $adminData[$key1] = $admin;
                    //         $adminData[$key1]['user'] = $userDetail;
                    //     }
                    //     $allComment[$cmtKey] = $cmt;
                    //     $allComment[$cmtKey]['adminComment'] = $adminData;
                    // }
                    // $tutorialFile = TutorialFile::where("tutorial_id", $tutDt->id)->with("subfiles")->first();

                    $tutArr[] = [
                        'tutorial_id' => $val->id,
                        'test_mode' => $val->assign_tutorials->count() > 0 ? '1' : '0',
                        'chapter_name' => $val->chapter_name,
                        'video_url' => $val->video_url,
                        'pdf_heading' => $val->pdf_heading,
                        'custom_code' => $val->custom_code,
                        'video_heading' => $val->video_heading,
                        'video_pdf_order' => $val->video_pdf_order,
                        'video_type' => $val->video_type,
                        'pdf_url' => $val->pdf_url,
                        'is_tutorial_bookmarked' => $val->bookmark_tutorials->count() > 0 ? '1' : '0',
                        'total_video_time' => $val->total_video_time,
                        'trans_script' => $val->trans_script,
                        'my_total_video_time' => @$val->watched_tutorials[0]->total_video_time,
                        'my_watched_time' => @$val->watched_tutorials->where('user_id', $user_id)->first()->watched_time,
                        'comment' => @$val->comments,
                        'files' => @$val->tutorial_files,
                        'get_notes' => $val->tutorial_notes,
                        'is_exist_in_plan' => $is_plan_exist,
                    ];
                }
            }
        }

        $getShowIds = Tutorial::where(['category_id' => $category_id, 'status' => 1])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->pluck('id')->toArray();

        $totalTutorials =  Tutorial::where(['category_id' => $category_id, 'status' => 1])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

        $seenedTutorials =  WatchedTutorial::where(['course_id' => $course_id, 'category_id' => $category_id, 'user_id' => $user_id])
            ->whereIn('tutorial_id', $getShowIds)->get()->count();

        if ($seenedTutorials > 0 && $totalTutorials > 0)
            $my_score =  ($seenedTutorials * 100) / $totalTutorials;
        else
            $my_score = '0';

        $getCat = Category::where('id', $category_id)->first();

        $req_data['category_name'] = @$getCat->category_name ?? "";
        $req_data['tutorial_list'] = $tutArr;
        $req_data['total_tutorial'] = $tutorialsCount;
        $req_data['seened_tutorial'] = $seenedTutorials;
        $req_data['score_in_percent'] = $my_score;
        $req_data['is_plan_exist'] = $is_plan_exist;

        if ($tutorialsCount > 0) {
            $req_message = "Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message], 200);
        } else {
            $req_message = "No Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message], 200);
        }
    }

    public function update(Request $request, Tutorial $tutorial, TutorialNote $tutorialNote)
    {
        $request->validate([
            'note' => 'required',
        ]);

        $user_id = auth()->user()->id;
        $note = $request->note;

        $req_data = [];

        $tutorialNote->update([
            'user_id' => $user_id,
            'tutorial_id' => $tutorial->id,
            'notes' => $note,
        ]);
        $req_message = "Tutorial Note Updated";

        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message], 200);
    }

    public function destroy(TutorialNote $tutorialNote)
    {
        $tutorialNote->delete();
        $req_message = "Tutorial Note Deleted";

        return response()->json(['status' => true, 'data' => [], 'message' => $req_message], 200);
    }

    public function bookmark(Tutorial $tutorial)
    {
        $user_id = auth()->user()->id;

        $req_data = [];

        $is_exist = Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutorial->id])->count();
        if ($is_exist == 0) {

            $newUser = Bookmark::create([
                'user_id' => $user_id,
                'tutorial_id' => $tutorial->id,
            ]);
            $req_message = "Tutorial added";
        } else {
            Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutorial->id])->delete();
            $req_message = "Tutorial removed";
        }

        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message], 200);
    }

    public function storeTutorialWatchlist(Request $request, Tutorial $tutorial)
    {
        $request->validate([
            'course_id' => 'required|integer',
            'watched_time' => 'required',
        ]);

        $user_id = auth()->user()->id;
        $course_id = $request->course_id;

        $total_video_time = $tutorial->total_video_time;
        $category_id = $tutorial->category_id;

        $prevWatchedTutorial = WatchedTutorial::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $category_id, 'tutorial_id' => $tutorial->id])->first(['id', 'total_video_time', 'watched_time']);

        $req_data['is_added'] = '0';

        $timeInSeconds = 0;
        $seventyPercentTime = 0;

        if (!empty($prevWatchedTutorial)) {
            $timeInSeconds =  $this->timeToSeconds($prevWatchedTutorial->total_video_time);
            $seventyPercentTime = (70 / 100) * $timeInSeconds;
        }

        $time = $request->watched_time;

        if ($request->watched_time >= $seventyPercentTime) {
            $time = $timeInSeconds;
        }

        if (isset($prevWatchedTutorial->watched_time) && $prevWatchedTutorial->watched_time == $timeInSeconds) {
            $time = 0;
        }

        if (isset($request->watched_time) && empty($prevWatchedTutorial)) {
            $time = $request->watched_time;
        }elseif($prevWatchedTutorial->watched_time != $request->watched_time){
            $time = $request->watched_time;
        }elseif($prevWatchedTutorial->watched_time === $request->watched_time){
            $time = 0;
        }

        $newWatchedTutorial = [
            'user_id' => $user_id,
            'course_id' => $course_id,
            'tutorial_id' => $tutorial->id,
            'category_id' => $category_id,
            'total_video_time' => $total_video_time,
            'watched_time' => $time,
        ];

        if(($time == 0) && isset($prevWatchedTutorial->id)){
            WatchedTutorial::where(['id' => $prevWatchedTutorial->id])->delete();
        }
        elseif (isset($prevWatchedTutorial->id) && $time>0) {
            WatchedTutorial::where(['id' => $prevWatchedTutorial->id])->update($newWatchedTutorial);
        } else {
            WatchedTutorial::create($newWatchedTutorial);
            $req_data['is_added'] = '1';
        }

        $req_message = "Tutorial Added To My Watched List";

        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message], 200);
    }

    public function clearWatchtime(Request $request)
    {
        try {
            $tutorialId = $request->tutorial_id;
            $user_id = auth()->user()->id;
            $courseId = $request->course_id;


            $request->validate([
                'tutorial_id' => 'required',
                'course_id' => 'required'
            ]);


            $courseId = (int) $courseId;
            $tutorialId = (int) $tutorialId;

            $findWatchTimeTutorials = WatchedTutorial::where([
                'user_id' => $user_id,
                'course_id' => $courseId,
                'tutorial_id' => $tutorialId,
            ])->first();

            if (!empty($findWatchTimeTutorials)) {
                $find = WatchedTutorial::find($findWatchTimeTutorials->id);

                $find->delete();

                return response()->json(['status' => true, 'data' => [], 'message' => 'Cleared Watchtime'], 200);
            } else {
                $findQuestion = Tutorial::find($tutorialId);
                $row = new WatchedTutorial;

                $time = (float) $findQuestion->total_video_time;
                $time = $time * 60;
                $row->user_id = $user_id;
                $row->course_id = $courseId;
                $row->tutorial_id = $tutorialId;
                $row->category_id = $findQuestion->category_id;;
                $row->total_video_time = $findQuestion->total_video_time;
                $row->watched_time = !empty($request->video_time) ? $request->video_time : $time;;

                $row->save();
                return response()->json(['status' => false, 'data' => [], 'message' => 'Watchtime Filled Successfully'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'message' => 'Something Went Wrong'], 200);
        }
    }

    public function reactComment(Request $request)
    {
        $commentId = $request->commentId;

        $userId = auth()->user()->id;
        $type = $request->type;
        $checkComment = CommentLike::where("comment_id", $commentId)->where("user_id", $userId)->first();
        if ($checkComment) {


            if ($type != $checkComment->type) {
                if ($type == "like") {
                    $getComment = Comment::find($commentId);
                    $getComment->likeCount = $getComment->likeCount + 1;
                    $getComment->disLikeCount = $getComment->disLikeCount - 1;
                    $getComment->save();
                } else {
                    $getComment = Comment::find($commentId);
                    $getComment->likeCount = $getComment->likeCount - 1;
                    $getComment->disLikeCount = $getComment->disLikeCount + 1;
                    $getComment->save();
                }
            }

            $checkComment->type = $type;
            $checkComment->save();
        } else {
            $insert = new CommentLike();
            $insert->comment_id = $commentId;
            $insert->user_id = $userId;
            $insert->type = $type;
            $insert->save();
            if ($type == "like") {
                $getComment = Comment::find($commentId);
                $getComment->likeCount = $getComment->likeCount + 1;
                $getComment->save();
            } else {
                $getComment = Comment::find($commentId);
                $getComment->disLikeCount = $getComment->disLikeCount + 1;
                $getComment->save();
            }
        }

        return response()->json(['status' =>  true, 'code' => 200, 'message' => 'Comment ' . $type . ' successfully'], 200);
    }

    public function getfreecourse($user_id)
    {
        $buy_packageId = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where('user_id', $user_id)
            ->pluck('order_detail.package_id')
            ->toArray();

        $freeCourseId = Package::whereIn("id", $buy_packageId)->get();
        $freeCourse = array();
        foreach ($freeCourseId as $val) {
            $exploaded = explode(",", $val->freecourse);
            foreach ($exploaded as $val1) {
                if (!empty($val1))
                    $freeCourse[] = $val1;
            }
        }
        return $freeCourse;
    }

    public function tutorialResponse($tutDt, $user_id, $course_id, $category_id, $is_plan_exist)
    {
        $getNote = TutorialNote::where(['user_id' => $user_id, 'tutorial_id' => $tutDt->id])->get(['id', 'notes']);
        $getNoteArr = ($getNote->count() > 0) ? $getNote->toArray() : [];

        $get_bookmarkCount = Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutDt->id])->count();
        $is_tutorial_bookmarked = ($get_bookmarkCount > 0) ? '1' : '0';

        $watched_tutorial =  WatchedTutorial::where(['course_id' => $course_id, 'category_id' => $category_id, 'user_id' => $user_id, 'tutorial_id' => $tutDt->id, 'user_id' => $user_id])->first();

        $check_test_mode = AssignTutorial::where(['course_id' => $course_id, 'tutorial_id' => $tutDt->id])->first(['id']);
        $is_test_mode = (isset($check_test_mode->id)) ? '1' : '0';
        $videoComment = VideoComment::where("tutorial_id", $tutDt->id)->where("parent_id", 0)->with("user")->orderBy("likecount", "DESC")->get();
        $allComment = array();
        foreach ($videoComment as $cmtKey => $cmt) {
            $adminComment = VideoComment::where('parent_id', $cmt->id)->where('admin_reply', 1)->orderBy("likecount", "DESC")->get();
            $adminData = array();
            foreach ($adminComment as $key1 => $admin) {
                $userDetail = User::find($admin->user_id);
                $adminData[$key1] = $admin;
                $adminData[$key1]['user'] = $userDetail;
            }
            $allComment[$cmtKey] = $cmt;
            $allComment[$cmtKey]['adminComment'] = $adminData;
        }
        $tutorialFile = TutorialFile::where("tutorial_id", $tutDt->id)->with("subfiles")->first();

        $tutArr = [
            'tutorial_id' => $tutDt->id,
            'test_mode' => $is_test_mode,
            'chapter_name' => $tutDt->chapter_name,
            'video_url' => $tutDt->video_url,
            'pdf_heading' => $tutDt->pdf_heading,
            'custom_code' => $tutDt->custom_code,
            'video_heading' => $tutDt->video_heading,
            'video_pdf_order' => $tutDt->video_pdf_order,
            'video_type' => $tutDt->video_type,
            'pdf_url' => $tutDt->pdf_url,
            'is_tutorial_bookmarked' => $is_tutorial_bookmarked,
            'total_video_time' => $tutDt->total_video_time,
            'trans_script' => $tutDt->trans_script,
            'my_total_video_time' => @$watched_tutorial->total_video_time,
            'my_watched_time' => @$watched_tutorial->watched_time,
            'comment' => @$allComment,
            'files' => @$tutorialFile,

            'get_notes' => $getNoteArr,
            'is_exist_in_plan' => $is_plan_exist,
        ];

        return $tutArr;
    }

    function timeToSeconds($time)
    {

        if (!empty($time)) {
            $timeExploded = explode(':', $time);

            if (isset($timeExploded[2])) {
                return (int) $timeExploded[0] * 3600 + (int) $timeExploded[1] * 60 + (int) $timeExploded[2];
            }
            return (int) $timeExploded[0] * 3600 + (int) $timeExploded[1] * 60;
        } else {
            return 0;
        }
    }
}
