<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Js;

class CourseDetailResource extends JsonResource
{
    public static $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'name' => $this->course_name,
            'is_tutorial' => count($this->tutorials) > 0,
            'is_questions' => count($this->questions) > 0,
            'is_package_purchased' => count($this->order_details) > 0,
            'is_question_number' => $this->isQuestionNumber,
            'total_questions' => count($this->questions),
            'attempted_questions_count' => $this->attempted_questions_count,
            'correct_attempted_questions_count' => $this->correct_attempted_questions_count,
            'user_attempted_questions_count' => $this->user_attempted_questions_count,
            'user_correct_attempted_questions_count' => $this->user_correct_attempted_questions_count,
            'percentile' => $this->percentile,
            'total_tutorials' => count($this->tutorials),
            'watched_tutorial_count' => $this->watched_tutorial_count,
            'last_watched_tutorial' => $this->watched_tutorials->last(),
            'questions_by_category' => $this->questions_by_category,
            'tutorials_by_category' => $this->tutorials_by_category,
            'tip' => count($this->exam_date) > 0 ? $this->tips[(int) date('d') % count($this->tips)] : null,
            'personal_support' => $this->supports,
            'exam_date' => count($this->exam_date) > 0 ? $this->exam_date[0]->exam_date : null,
        ];
    }
}
