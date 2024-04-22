<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromQuery;

use DateTime;
 
class UserExport implements WithHeadings,WithMapping,FromQuery
{  
    public  $condition;
    public  $start_date;
    public  $end_date;
    public function __construct($condition,$start_date,$end_date)
    {
        $this->condition = $condition;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function query()
    {
        /*return User::query()->whereBetween('created_at',[$this->start_date,$this->end_date])->orderBy('id','desc');*/
        $data=User::query()->orderBy('id','desc')->first();
        // print_r($data->userCourseWithExpire()); exit;
        return $data;
    }
    public function headings() : array
    {
        return [
            'Id',
            'user',
            'Email',
            'Course Name',
            
            'Date',
            'Time',
            'Last Login',
        ];
    }
    
   public function map($value): array
    {
        $s = $value->created_at;
        $dt = new DateTime($s);
        return [
            $value->id,
            $value->name,
            @$value->email,    
            @$value->userCourseWithExpire(),    
            $dt->format('d/m/Y'),
            $dt->format('H:i:s'),
            @$value->last_login_date,    
        ];
    }


}
