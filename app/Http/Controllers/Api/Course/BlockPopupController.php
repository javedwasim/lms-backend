<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Models\BlockPopup;
use Illuminate\Http\Request;

class BlockPopupController extends Controller
{
    public function destroy(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
            'type' => 'required|integer',
        ]);

        $user_id = auth()->user()->id;

        $req_data = [];

        $course_id = $request->course_id;
        $record_type = $request->type;

        $insArr = [
            'user_id' => $user_id,
            'course_id' => $course_id,
            'type' => $record_type,
        ];

        $recCount = BlockPopup::where($insArr)->count();
        if ($recCount == 0) {
            $newUser = BlockPopup::create($insArr);
            $req_message = "Removed Succefully";
        } else {
            $req_message = "No record found";
        }
        return $this->json_view(true, $req_data, $req_message);
    }
}
