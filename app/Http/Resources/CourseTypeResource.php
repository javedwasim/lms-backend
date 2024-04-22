<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [            
            'id' => $this->id,
            'type' => $this->course_type->name,
            'name' => $this->course_name,
            'thumbnail' => $this->course_image,
            'noOfLessons' => $this->tutorials->count(),
            'totalHours' => $this->total_hours,
            'numberOfStudents' => $this->order_details->count(),
        ];
    }
}
