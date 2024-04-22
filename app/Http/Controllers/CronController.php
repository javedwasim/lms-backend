<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Auth;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Course;
use App\Models\Package;
use App\Models\Tutorial;
use App\Models\MailQueue;
use App\Models\TempPerformance;
use yajra\Datatables\Datatables;
use App\Exports\UserExport;
use App\Mail\SendMail;
use Excel;
use Mail;

class CronController extends Controller
{

    public function sendmail(Request $request)
    {

        $getMail = MailQueue::where("status", 1)->get();

        if (!empty($getMail)) {

            foreach ($getMail as $key => $val) {
                MailQueue::where("id", $val->id)->update(['status' => 2]);
                Mail::to($val['email'])->send(new SendMail($val));
                MailQueue::where("id", $val->id)->update(['status' => 3]);
            }
        }
    }
    public function uploadtos3()
    {
        // phpinfo(); exit;
        $getTutorial = Tutorial::where("is_uploaded_s3", 0)->where(function($query){
            $query->where("is_attempted_to_s3","<",4);
            $query->orWhere("is_attempted_to_s3",null);
        })->limit(5)->get();
        $path = '/path/to/your/video/folder';
        foreach ($getTutorial as $val) {

            $tutorail1 = Tutorial::find($val->id);
            $tutorail1->is_attempted_to_s3 = $val->is_attempted_to_s3+1;
            $tutorail1->save();
            // print_r($val); exit;

            if (!empty($val->video_url)) {
                $tempUrl = explode("/", $val->video_url);
                $videFileName = end($tempUrl);

                $videoUrl = "public/uploads/tutorial_video/" . $videFileName;
                $videoUrl1 = "tutorial_video/" . $videFileName;


                //upload video
                $client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => 'ams3',
                    'endpoint' => 'https://ams3.digitaloceanspaces.com',
                    // 'use_path_style_endpoint' => true, // Configures to use subdomain/virtual calling format.
                    'credentials' => [
                        'key'    => getenv('SPACES_KEY'),
                        'secret' => getenv('SPACES_SECRET'),
                    ],
                ]);


                $client->putObject([
                    'Bucket' => 'media-studymind-co-uk',
                    'Key'    => $videoUrl1,
                    'Body'   => fopen($videoUrl, 'rb'),
                    'ACL'    => 'public-read',
                ]);
                $videoUrlNew = 'https://media-studymind-co-uk.ams3.cdn.digitaloceanspaces.com/' . $videoUrl1;

                $tutorail = Tutorial::find($val->id);
                $tutorail->is_uploaded_s3 = 1;
                $tutorail->video_url = $videoUrlNew;
                // $tutorail->pdf_url=$pdfUrl;
                $tutorail->save();
                unlink($videoUrl);
            }
            if (!empty($val->pdf_url)) {
                $tempUrl1 = explode("/", $val->pdf_url);
                $pdfUrlName = end($tempUrl1);

                $pdfUrl = "public/uploads/tutorial_video/" . $pdfUrlName;
                $pdfUrl1 = "tutorial_video/" . $pdfUrlName;

                $client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => 'ams3',
                    'endpoint' => 'https://ams3.digitaloceanspaces.com',
                    // 'use_path_style_endpoint' => true, // Configures to use subdomain/virtual calling format.
                    'credentials' => [
                        'key'    => getenv('SPACES_KEY'),
                        'secret' => getenv('SPACES_SECRET'),
                    ],
                ]);


                $client->putObject([
                    'Bucket' => 'media-studymind-co-uk',
                    'Key'    => $pdfUrl1,
                    'Body'   => fopen($pdfUrl, 'rb'),
                    'ACL'    => 'public-read',
                ]);
                $pdfUrlNew = 'https://media-studymind-co-uk.ams3.cdn.digitaloceanspaces.com/' . $pdfUrl1;
                $tutorail = Tutorial::find($val->id);
                $tutorail->is_uploaded_s3 = 1;
           
                $tutorail->pdf_url = $pdfUrlNew;
                $tutorail->save();
                unlink($pdfUrl);
            }
        }
    }
}
