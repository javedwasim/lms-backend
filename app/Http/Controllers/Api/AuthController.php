<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helpers\ApiHelper;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Auth\Events\Registered;
use Session;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PropertyCollection;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PhoneVerification;
use App\Models\Property;
use App\Models\City;
use Passport;
use Carbon\Carbon;
use Str;
use App\Models\TempUser;

class AuthController extends Controller
{
    public function json_view($req_status = false, $req_data = "", $req_message = "", $status_code = "")
    {
        $this->status = $req_status;

        if ($status_code != "") {
            $this->code = $status_code;
        } else {
            $this->code = ($req_status == false) ? "101" : "104";
        }

        $this->data = $req_data;
        $this->message = $req_message;
        return  response()->json($this);
    }


    public function login_api(Request $request)
    {
        $req_data = (object)[];

        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $error = '';
                if (!empty($validator->errors())) {
                    $error = $validator->errors()->first();
                }
                return $this->json_view(false, $req_data, $error, '101');
            }
            $usr_data = $request->all();
            $credentials = [
                'email' => $usr_data['email'],
                'password' => $usr_data['password'],
            ];

            $user = User::where('email', trim($request->email))->first();
            if ($user) {

                if (Auth::attempt($credentials)) {
                    $data = User::where('email', $request->email)->first();
                    $data->last_login_date = date("Y-m-d H:i:s");
                    $data->save();
                    $data2 = ApiHelper::setJosnData($data->toArray());

                    $req_data = $data2;
                    $req_data['access_token'] = $data->createToken('authToken', ['user'])->accessToken;
                    $message = "Login successful";
                } else {
                    $message = "Invalid credentials";
                    return $this->json_view(false, $req_data, $message, '101');
                }
            } else {
                $message = "The login details unfortunately do not match";
                return $this->json_view(false, $req_data, $message, '101');
            }
            return $this->json_view(true, $req_data, $message);
        } catch (Exception $e) {
            $message = $e->getMessage();
            return $this->json_view(false, $req_data, $message, '101');
        }
    }

    public function signup_api(Request $request)
    {
        $req_data = [];
        $req_data_obj = (object)[];
        try {
            $rules = [
                'user_name' => 'required',
                'email' => 'required|unique:users,email',
                'password' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $error = '';
                if (!empty($validator->errors())) {
                    $error = $validator->errors()->first();
                }
                return $this->json_view(false, $req_data_obj, $error);
            }
            $input = [
                'name' => $request->user_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ];

            $id2 = [
                'email' => $request->only('email')
            ];
            $insert =  User::updateOrCreate($id2, $input);

            if ($insert) {
                $data = User::where('email', $request->email)->first();

                $data2 = ApiHelper::setJosnData($data->toArray());

                $req_data = $data2;
                $req_data['access_token'] = $data->createToken('authToken', ['user'])->accessToken;

                $message = 'Registered successfully';
                return $this->json_view(true, $req_data, $message);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            return $this->json_view(false, $req_data_obj, $message);
        }
    }

    public function social_media_login(Request $request)
    {
        $req_data = [];
        try {
            $rules = array(
                'social_media_id' => 'required',
                'email' => 'required',
                'social_media_type' => 'required'
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $error = '';
                if (!empty($validator->errors())) {
                    $error = $validator->errors()->first();
                }
                $message = $error;
                return $this->json_view(false, $req_data, $message);
            }

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

                $data = User::where('email', $request->email)->first();
                $data['access_token'] = $data->createToken('authToken', ['user'])->accessToken;

                $data = ApiHelper::setJosnData($data->toArray());

                $req_data['api_data'] = $data;
                $message = 'Your account login successfully.';
                return $this->json_view(true, $req_data, $message);
            } else {
                $cr = User::create($input);

                $data = User::where(['id' => $cr->id])->first(['id', 'name', 'email', 'phone', 'phone_verified_at', 'status', 'device_token']);
                $data['access_token'] = $data->createToken('authToken', ['user'])->accessToken;

                $data = ApiHelper::setJosnData($data->toArray());

                $req_data['api_data'] = $data;
                $message = 'Your account login successfully.';
                return $this->json_view(true, $req_data, $message);
            }
        } catch (Exception $e) {

            $message = $e->getMessage();
            return $this->json_view(false, $req_data, $message);
        }
    }

    public function reset_password_api(Request $request)
    {
        $rules = array(
            'email' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        }

        $req_data = [];

        $user_count = User::where('email', $request->email);
        $UserData    = $user_count->first();

        $random_verify_str = substr(md5(mt_rand()), 0, 49);

        if ($user_count->count()) {
            if ($UserData->email) {

                $verify_link = '<a href="' . env('APP_URL') . '/reset_password/verify_mail/' . $random_verify_str . '" style="background-color: #7087A3; font-size: 12px; padding: 10px 15px; color: #fff; text-decoration: none">Reset Password</a>';

                $mail_data = [
                    'receiver' => ucwords(@$UserData->name),
                    'email' => $UserData->email,
                    'web_url' => env('APP_URL'),
                    'verify_link' => $verify_link
                ];
                if (env('MAIL_ON_OFF_STATUS') == "on") {
                    \Mail::send('mails.reset_password_mail', $mail_data, function ($message) use ($mail_data) {
                        $message->to($mail_data['email']);
                        $message->from(env('MAIL_FROM_ADDRESS'), env('APP_NAME'));
                        $message->subject(env('APP_NAME') . ' Reset Password Notification');
                    });
                }

                $message = 'Reset Password link has been sent to your email. Please check your account!';
                return $this->json_view(true, $req_data, $message);
            } else {
                $message = 'Email not found for reset password.';
                return $this->json_view(false, $req_data, $message);
            }
        } else {
            $message = 'Account Not Found';
            return $this->json_view(false, $req_data, $message);
        }
    }

    public function update_password_api(Request $request)
    {
        $rules = array(
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        }

        $user_count = User::where('verify_code', $request->verify_reset_email_token);

        if ($request->verify_reset_email_token && $user_count->count() > 0) {
            $UserData = $user_count->first();

            $confirm_password = $request->confirm_password;
            $Data = array(
                "password" => Hash::make($confirm_password),
                "verify_code" => ''
            );
            $data = User::where('id', @$UserData->id)->update($Data);

            $message = 'Password updated successfully. Please login with new password !';
            return $this->json_view(true, $req_data, $message);
        } else {
            $message = 'Token mismatch for reset password !';
            return $this->json_view(false, $req_data, $message);
        }
    }
}
