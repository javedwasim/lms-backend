<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Category; 

class SubCategory extends Model
{
    use HasFactory,SoftDeletes;
  
    protected  $table = 'sub_category_tbl';

    protected $fillable = [
        'category_id','sub_category_name','created_at','updated_at','deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at', 
    ];

    protected $appends = [
        'status_name','category_name'
    ];
    
    public function category_detail()
    {       
        return $this->hasOne(Category::class, 'id','category_id');
    }

    public function questions()
    {
        return $this->hasMany(QuestionAnswer::class, 'sub_category_ids');
    }
    
    public function attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id');
    }
    
    public function correct_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id')->where('is_correct', 1);
    }

    public function attempted_questions_by_user()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id');
    }
    
    public function correct_attempted_questions_by_user()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id')->where('is_correct', 1);
    }

    public function user_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id')->where('user_id', auth()->user()->id);
    }
    
    public function user_correct_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id')->where(['user_id' => auth()->user()->id, 'is_correct' => 1]);
    }

    public function incorrect_attempted_questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'sub_category_ids', 'id')->where('is_correct', 0);
    }

    public function getCategoryNameAttribute()
    {
        $getResult = Category::where('id',$this->category_id)->first();

        return $getResult->category_name ?? '';
    } 

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