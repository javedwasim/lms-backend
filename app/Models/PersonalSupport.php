<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalSupport extends Model
{
    use HasFactory;
  
    protected  $table = 'personal_support_tbl';

    protected $fillable = [
        'course_id','support_title','support_link','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    
    protected $appends = ['status_name'];
    
    public function getStatusNameAttribute()
    {      
        $status_id = $this->status;

        if($status_id=='1')
           $status = "Active"; 
        else 
            $status = "InActive";

        return $status;
    }
    
}