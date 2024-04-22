<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionSubCategoryResource extends JsonResource
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
            "category_id" => $this->category_id,
            "sub_category_name" => $this->sub_category_name,
            "status" => $this->status,
            "questions_count" => $this->questions_count,
            "attempted_questions_count" => $this->attempted_questions_count,
            "correct_attempted_questions_count" => $this->correct_attempted_questions_count,
            "incorrect_attempted_questions_count" => $this->incorrect_attempted_questions_count,
            "status_name" => $this->status_name,
            "category_name" => $this->category_name,
        ];
        return parent::toArray($request);
    }
}
