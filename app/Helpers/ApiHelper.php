<?php


namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 
use App\Models\User;

class ApiHelper
{
    public static function setJosnData($value)
    {
        array_walk_recursive($value, function (&$item, $key) {
            $item = null === $item ? '' : strval($item);
        });
        return $value;
    }

    public static function sendNotification($user_id=false,$title=false,$message=false)
    { 
        $GetUserDetail = User::where('id', @$user_id)->first(['device_token']); 
        $device_token = @$GetUserDetail->device_token;
        
        try 
        {
            if (!empty($device_token)) {

                $ch = curl_init("https://fcm.googleapis.com/fcm/send"); 
                $token = @$device_token;   
                $title = @$title;   
                $message = strip_tags(@$message);   
 
                $notification = array('title' =>$title , 'text' => $message);
 
                $arrayToSend = array('to' => $token, 'notification' => $notification, 'data' => $notification, 'priority'=>'high');
 
                $json = json_encode($arrayToSend); 
                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization:key='.config('app.firebase_auth_key'); // API key here
         
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $resp = curl_exec($ch); 

                if(!curl_errno($ch)){
                    $info = curl_getinfo($ch);  
                } 
                curl_close($ch);
                return response()->json(['status' => true, 'msg' => 'Notification sent successfully.', 'data' => $resp]); 
                }else{ 
                    return response()->json(['status' => false, 'error' => 'token not found']);
                }   
             }catch (\Exception $e) { 
                 return response()->json(['status' => false, 'error' => $e->getMessage()]);
            }  
    }
 

    public static function sendOTPSMS($otp, $mobile_numbers)
    { 
        $post_data = [
                'flow_id' => config('app.msg91_flow_id'),
                'mobiles' => $mobile_numbers,
                'app_name' => config('app.name'),
                'otp' => $otp
            ];

        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          /*CURLOPT_POSTFIELDS => "{\n  \"flow_id\": \"EnterflowID\",\n  \"sender\": \"EnterSenderID\",\n  \"mobiles\": \"919XXXXXXXXX\",\n  \"VAR1\": \"VALUE 1\",\n  \"VAR2\": \"VALUE 2\"\n}",*/

          CURLOPT_POSTFIELDS => json_encode($post_data),
          CURLOPT_HTTPHEADER => [
            "authkey: ".config('app.msg91_auth_key'),
            "content-type: application/JSON"
          ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        /*if ($err) { 
          echo "cURL Error #:" . $err;
        } else {
          echo $response; 
        }*/

    } 
 
}
