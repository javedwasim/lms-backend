<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $request->validate([
                'social_media_id' => 'required',
                'social_media_type' => 'required',
                'email' => 'required'
            ]);

            $input = $request->all();

            $getSoc_Dt = User::where('email', $request->email)->whereNotNull('email');

            if ($request->social_media_type == "google") {

                $input['google_id'] = $request->social_media_id;
            } else if ($request->social_media_type == "facebook") {

                $input['facebook_id'] = $request->social_media_id;
            }


            $find = $getSoc_Dt->first();
            unset($input['social_media_id']);
            unset($input['social_media_type']);

            if ($find) {
                $input['email_verified_at'] = Carbon::now();

                User::where('email', $request->email)->update($input);

                $user = User::where('email', $request->email)->first();

                Auth::login($user);

                return response()->noContent();
            } else {
                $input['password'] = '';
                $input['email_verified_at'] = Carbon::now();

                $cr = User::create($input);

                $user = User::where(['id' => $cr->id])->first(['id', 'name', 'email', 'phone', 'phone_verified_at', 'status', 'device_token']);

                Auth::login($user);

                return response()->noContent();
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ]);
        }
    }
}
