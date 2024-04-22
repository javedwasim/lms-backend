<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __invoke()
    {
        $result = array("contactNo" => 9876543210, "email" => "test@gmail.com");
        return response()->json([
            'status' => true,
            'code' => 200, 
            'message' => 'Success', 
            'data' => $result
        ]);
    }
}
