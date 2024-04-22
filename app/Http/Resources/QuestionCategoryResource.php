<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionCategoryResource extends JsonResource
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
            "category_name" => $this->category_name,
            "time" => $this->time,
            "short_name" => $this->short_name,
            "status" => $this->status,
            "sort" => $this->sort,
            "tag" => $this->tag,
            "questions_count" => $this->sub_categories->sum('questions_count'),
            "attempted_questions_count" => $this->sub_categories->sum('attempted_questions_count'),
            "correct_attempted_questions_count" => $this->sub_categories->sum('correct_attempted_questions_count'),
            "incorrect_attempted_questions_count" => $this->sub_categories->sum('incorrect_attempted_questions_count'),
            "status_name" => $this->status_name,
            "pivot" => [
                "course_id" => $this->pivot->course_id,
                "category_id" => $this->pivot->category_id,
            ],
            "sub_categories" => QuestionSubCategoryResource::collection($this->sub_categories),
        ];
    }
}
