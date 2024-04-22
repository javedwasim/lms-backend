<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\Package;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {         
        // $totalUsers = User::whereHas('roles', function ($query) {
        //     return $query->where('name', '!=', 'admin');
        // })->count();

        return view('admin.home', [
            'totalUsers' => User::count(),
            'totalCourse' => Course::count(),
            'totalTutorial' => Tutorial::count(),
            'totalSubscribedUser' => Order::count(),
            'totalCategory' => Category::count(),
            'totalPackages' => Package::count(),
        ]);  
    }
}
