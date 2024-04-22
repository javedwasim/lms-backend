<?php

namespace App\Http\Controllers;

use App\Models\ProgressSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function progress_setting()
    {
        return view('admin.tip.progress_setting_edit', [
            'GetData' => ProgressSetting::orderBy('id', 'desc')->get()
        ]);
    }

    public function update_progress_bar_setting(Request $request)
    {
        foreach ($request->progress_id as $key => $pro_id) {
            $color = $request->color_data[$key];
            ProgressSetting::where('id', $pro_id)->update(['color' => $color]);
        }

        return back()->with('success', 'Tutorial updated successfully');

    }

}
