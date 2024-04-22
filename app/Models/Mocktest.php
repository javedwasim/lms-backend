<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mocktest extends Model
{
    use HasFactory;
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function resumes()
    {
        return $this->hasMany(MocktestResume::class, 'mocktest_id');
    }

    public function attempted_questions()
    {
        return $this->hasMany(MocktestAttemptedQuestion::class, 'mocktest_id');
    }

    public function attemptQuestions()
    {
        return $this->hasManyThrough(AttemptMocktestQuestion::class, AssingQuestionMocktest::class, 'mocktest_id', 'question_id');
    }

    public function assignQuestions()
    {
        return $this->hasMany(AssingQuestionMocktest::class, 'mocktest_id');
    }
}
