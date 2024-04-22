<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
  
    protected  $table = 'cart_tbl';

    protected $fillable = [
        'user_id','record_id','package_id','record_type','created_at','updated_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public const COURSE = 1;
    public const SEMINAR = 2;
    public const FLASH_CARD = 3;
    public const BOOK = 4;

    protected $appends = ['course_name'];
    
    public function getCourseNameAttribute()
    {  
        $courseName='';
         if($this->record_type==self::COURSE)
        {
            $getResult = Course::where(['id'=>$this->record_id])->first();
            $courseName= $getResult->course_name ;
        }
        if($this->record_type==self::BOOK)
      
        {
            $getCat = Book::where('id',$this->record_id)->first(['id','title']);
            $courseName=$getCat->title;
        }
        if($this->record_type==self::FLASH_CARD)
     
        {
            $getCat = FlashCard::where('id',$this->record_id)->first(['id','title']);
            $courseName=$getCat->title;
        }
        if($this->record_type==self::SEMINAR)
        {
            $getCat = Seminar::where('id',$this->record_id)->first(['id','title']);
            $courseName=$getCat->title;
        }
        

        return $courseName ?? '';
    } 

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}