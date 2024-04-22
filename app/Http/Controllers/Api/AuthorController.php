<?php
    
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;    
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

   
class AuthorController extends Controller
{ 
    public function register(Request $request)
    {
        echo "register api call"; die;
    }

    public function show(Request $request)
    {
        echo "show api call"; die;
    }
    

}