<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{
    public function profile_show()
    {
        return view('users.show', [
            'user' => auth()->user(),
            'page_title' => 'profile'
        ]);
    }

    public function profile_edit()
    {
        $user = User::find(auth()->user()->id);
        
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name', 'id')->all(),
            'userRole' => $user->getUserRole()->pluck('name', 'id')->all(),
            'page_title' => 'profile'
        ]);
    }
}
