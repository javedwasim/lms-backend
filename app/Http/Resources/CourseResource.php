<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'isEnrolled' => count($this->order_details) > 0,
            'isAttempted' => $this->watched_tutorials->count() > 0,
            'lastWatchedTutorialId' => $this->watched_tutorials->count() > 0 ? $this->watched_tutorials->last()->tutorial_id : null,
            'lastWatchedTutorialName' => $this->watched_tutorials->count() > 0 ? $this->watched_tutorials->last()->tutorial->chapter_name : null,
            'lastWatchedCategoryId' => $this->watched_tutorials->count() > 0 ? $this->watched_tutorials->last()->category_id : null,
            'lastWatchedCategoryName' => $this->watched_tutorials->count() > 0 ? $this->watched_tutorials->last()->category->category_name : null,
        ];
    }
}
