<?php
  
namespace App\Http\Controllers;
  
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
  
class GoogleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function redirectToGoogle()
    {  
        return Socialite::driver('google')->redirect();
    }
        
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleGoogleCallback()
    {
        try {
      
            $user = Socialite::driver('google')->user();
       
           // $finduser = User::where('google_id',$user->id)->first();
            
            $finduser = User::where('email',$user->email)->first();
 

            if($finduser){
                
                $Data = array(
                    "verify_code" => '',
                    "email_verified_at"=>date("Y-m-d h:m:s",strtotime("now")),
                    'google_id'=> $user->id,
                ); 
                $data =  User::where('id', @$finduser->id)->update($Data); 

                Auth::login($finduser);
      
                return redirect()->intended('dashboard');
       
            }else{
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id'=> $user->id,
                    'password' => encrypt('123456dummy'),
                    "verify_code" => '',
                    "email_verified_at"=>date("Y-m-d h:m:s",strtotime("now"))
                ]);
              
                Auth::login($newUser);
      
                return redirect()->intended('dashboard');
            }
      
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}