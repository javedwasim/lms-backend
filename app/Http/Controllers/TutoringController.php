<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tutoring;

class TutoringController extends Controller
{
    public function updateUrl(Request $request)
    {
        $checkUrl = Tutoring::first();
        if ($request->isMethod('post')) {
            if (empty($checkUrl)) {
                $checkUrl = new Tutoring();
                $checkUrl->url = $request->tutoring_url;
            } else {
                $checkUrl->url = $request->tutoring_url;
            }
            $checkUrl->save();
            return redirect()->back()->with('success', 'URL updated successfully');
        }

        return view('admin.tutoring.create', [
            'checkUrl' => $checkUrl
        ]);
    }
}
