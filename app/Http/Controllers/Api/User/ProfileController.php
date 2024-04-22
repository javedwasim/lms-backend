<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user_id = auth()->user()->id;

        $req_data = [];
        $gender_name = "";
        $user = User::find($user_id);

        if ($user->gender == '1') {
            $gender_name = "Male";
        } else if ($user->gender == '2') {
            $gender_name = "Female";
        }

        $req_data = [
            'user_id' => $user->id,
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'gender_name' => $gender_name,
            'profile_photo_path' => $user->profile_photo_path,
            'google_id' => $user->google_id,
            'facebook_id' => $user->facebook_id,
        ];

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => 'User Information'
        ]);
    }

    public function update(Request $request)
    {
        $user_id = auth()->user()->id;

        $input['name'] = $request->name;

        if ($request->hasFile('image')) {
            $profile_photo_img_path = $request->image->store('profile_photo_path', 's3');
            $input['profile_photo_path'] = Storage::disk('s3')->url($profile_photo_img_path);
        }

        $newUser = User::find($user_id)->update($input);

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $newUser,
            'message' => 'Profile Updated Successfully'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $userId = auth()->user()->id;
        $oldPassword = $request->oldPassword;
        $password = $request->password;
        $user = User::where('id', $userId)->first();
        if ($user) {
            if (Hash::check($oldPassword, $user->password)) {
                $data = array(
                    "password" => Hash::make($password),
                    "verify_code" => ''
                );
                User::where('id', $user->id)->update($data);
                return response()->json(['statusCode' => 200, 'message' => 'Password changed successfully', 'data' => $user], 200);
            } else {
                return response()->json(['statusCode' => 422, 'message' => 'Invalid Credentials', 'data' => []], 422);
            }
        }
    }
}
