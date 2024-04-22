<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Reportissue;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function data()
    {
        $data =  [
            1 => 'Something not working properly',
            2 => 'Mistake in Stem',
            3 => 'Mistake in Question',
            4 => 'Mistake in Answers',
            5 => 'Right answer marked as wrong',
            6 => 'Mistake in Explanation',
            7 => 'Other',
        ];

        $arr = [];

        foreach ($data as $key => $val) {
            $row = [];
            $row['key'] = $key;
            $row['value'] = $val;
            $arr[] = $row;
        }

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => 'Report Issue Select Data',
            'message' => $arr
        ]);
    }

    public function store(Request $request)
    {
        try {
            $user_id = auth()->user()->id;

            $req_data = [];
            if ($request->email != "mughal50@hotmail.com") {
                $row = new Reportissue();
                $row->options = !empty($request->options) ? json_encode($request->options) : '[{"key":7,"value":"Other"}]';
                $row->email = $request->email ? $request->email : auth()->user()->email;
                $row->description = $request->description;
                $row->question_id = $request->question_id;
                $row->user_id = $user_id;
                $row->created_at = date('Y-m-d H:i:s');
                if ($row->save()) {
                    return response()->json([
                        'status' => false,
                        'code' => 200,
                        'data' => $req_data,
                        'message' => "Issue Posted Successfully",
                    ]);
                }
            }

            return response()->json([
                'status' => false,
                'code' => 200,
                'data' => $req_data,
                'message' => "Something Went Wrong",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 200,
                'data' => [],
                'message' => "Something Went Wrong",
            ]);        
        }
    }
}
