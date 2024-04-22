<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "course_name" => $this->course_name,
            "video_image" => $this->video_image,
            "course_image" => $this->course_image,
            "status" => $this->status,
            "banner_content" => $this->banner_content,
            "banner_link" => $this->banner_link,
            "popup_content" => $this->popup_content,
            "popup_course_image" => $this->popup_course_image,
            "popup_link" => $this->popup_link,
            "sort" => $this->sort,
            "is_modal" => $this->is_modal,
            "is_test" => $this->is_test,
            "is_question" => $this->is_question,
            "is_tutorial" => $this->is_tutorial,
            "total_hours" => $this->total_hours,
            "categories_detail" => QuestionCategoryResource::collection($this->categories_detail),
        ];
    }
}
