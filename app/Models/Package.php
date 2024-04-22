<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PackageMultiple;
use App\Models\Course;
class Package extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'package_tbl';

    protected $guarded=[];
    // protected $fillable = $gu;

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];

    protected $appends = [
        'status_name'
    ];

    public function getStatusNameAttribute()
    {      
        $status_id = $this->status;

        if($status_id=='1')
           $status = "Active"; 
        else 
            $status = "InActive";

        return $status;
    } 

 

    /**
     * Get all of the comments for the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packagemultiples()
    {
        return $this->hasMany(PackageMultiple::class, 'multi_pack_parent', 'id');
    }
    public function course(){
        return $this->belongsTo(Course::class, 'perticular_record_id', 'id');
    }

    public function questions(){
        return $this->belongsToMany(QuestionAnswer::class, 'package_questions', 'package_id', 'question_id');
    }

    public function tutorials()
    {
        return $this->belongsToMany(Tutorial::class, 'package_tutorials', 'package_id', 'tutorial_id');
    }
}