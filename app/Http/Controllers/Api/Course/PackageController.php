<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'package_for' => 'required|integer',
            'course_id' => 'required|integer',
        ]);

        $package =  Package::with('packagemultiples')->orderBy('price', 'asc')->where([
            'status' => 1,
            'package_for' => $request->package_for,
            'perticular_record_id' => $request->course_id,
        ])->get();

        return PackageResource::collection($package);
    }
}
