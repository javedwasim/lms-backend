<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use DB;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'country',
        'country_code',
        'country_flag_code',
        'gender',
        'phone',
        'concated_phone',
        'phone_verified_at',
        'password',
        'role_id',
        'verify_code',
        'device_token',
        'device_type',
        'facebook_id',
        'google_id',
        'latitude',
        'longitude',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url', 'gender_name'
    ];

    public function getUserRole()
    {
        $getResult = Role::where('id', $this->role_id)->first();

        return $getResult;
    }

    public function getRoleNames()
    {
        $getResult = Role::where('id', $this->role_id)->first();

        return $getResult;
    }

    public function getGenderNameAttribute()
    {
        $intrest_in = $this->intrest_in;

        if ($intrest_in == '1')
            $name = "Male";
        else if ($intrest_in == '2')
            $name = "Female";
        else
            $name = "";

        return $name;
    }

    public static function getUser()
    {
        $records = DB::table('users')->select('id', 'name', 'email')->get()->toArray();
        return $records;
    }

    public function comment()
    {
        return $this->hasMany(Comment::class, 'id', 'user_id');
    }

    public function videoComments()
    {
        return $this->hasMany(VideoComment::class, 'user_id');
    }


    public function userCourseWithExpire()
    {
        $buy_user_course_arr = \App\Models\Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.package_for' => '1'])->where('user_id', $this->id)->get();
        $data = array();
        foreach ($buy_user_course_arr as $val) {
            $getCourseDt =   \App\Models\Course::orderBy('id', 'desc')->where('id', $val['particular_record_id'])->first('course_name');
            $data[$val['particular_record_id']] = $getCourseDt->course_name . " ( ExpireDate : " . $val->expiry_date . " )";
        }
        $finalData = implode(" || ", $data);




        return $finalData;
    }
}
