<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseQuestion;
use App\Models\CourseTutorial;
use App\Models\Package;
use App\Models\PackageQuestion;
use App\Models\PackageTutorial;
use App\Models\QuestionAnswer;
use App\Models\Tutorial;
use Illuminate\Http\Request;

class MigrationSeedController extends Controller
{
    public function __invoke()
    {
        $coursesId = Course::pluck('id')->toArray();
        $questionsId = QuestionAnswer::pluck('id')->toArray();
        $tutorialsId = Tutorial::pluck('id')->toArray();
        $categoriesId = Category::pluck('id')->toArray();

        // don't uncomment it ...
        // CourseTutorial::truncate();
        // PackageQuestion::truncate();
        // PackageTutorial::truncate();
        // CourseCategory::truncate();
        // CourseQuestion::truncate();

        $cousrseTutorialsData = Tutorial::select('id', 'course_id')->get()->map(function ($tutorial) {
            return [
                'id' => $tutorial->id,
                'course_ids' => array_map('intval', explode(',', $tutorial->course_id)),
            ];
        });

        $courseTutorials = collect($cousrseTutorialsData)->flatMap(function ($item) use ($coursesId) {
            $tutorialId = $item['id'];
            $courseIds = $item['course_ids'];

            return collect($courseIds)->map(function ($courseId) use ($tutorialId, $coursesId) {
                if ($courseId === 0 || $courseId === null) {
                    return null;
                }
                // if (!in_array($courseId, $coursesId)) {
                //     return null;
                // }
                return [
                    'course_id' => $courseId,
                    'tutorial_id' => $tutorialId,
                ];
            })->filter();
        });
        CourseTutorial::insertOrIgnore($courseTutorials->toArray());

        $packageQuestionsData = Package::select('id', 'assign_question_id')->get()->map(function ($package) {
            return [
                'id' => $package->id,
                'assign_question_ids' => array_map('intval', explode(',', $package->assign_question_id)),
            ];
        });

        $packageQuestions = collect($packageQuestionsData)->flatMap(function ($item) use ($questionsId) {
            $packageId = $item['id'];
            $questionIds = $item['assign_question_ids'];

            return collect($questionIds)->map(function ($questionId) use ($packageId, $questionsId) {
                if ($questionId === 0 || $questionId === null) {
                    return null;
                }
                // if (!in_array($questionId, $questionsId)) {
                //     return null;
                // }
                return [
                    'question_id' => $questionId,
                    'package_id' => $packageId,
                ];
            })->filter();
        });
        PackageQuestion::insertOrIgnore($packageQuestions->toArray());

        $packageTutorialsData = Package::select('id', 'assign_tutorial_id')->get()->map(function ($package) {
            return [
                'id' => $package->id,
                'assign_tutorial_ids' => array_map('intval', explode(',', $package->assign_tutorial_id)),
            ];
        });

        $packageTutorials = collect($packageTutorialsData)->flatMap(function ($item) use ($tutorialsId) {
            $packageId = $item['id'];
            $tutorialIds = $item['assign_tutorial_ids'];

            return collect($tutorialIds)->map(function ($tutorialId) use ($packageId, $tutorialsId) {
                if ($tutorialId === 0 || $tutorialId === null) {
                    return null;
                }
                // if (!in_array($tutorialId, $tutorialsId)) {
                //     return null;
                // }
                return [
                    'tutorial_id' => $tutorialId,
                    'package_id' => $packageId,
                ];
            })->filter();
        });
        PackageTutorial::insertOrIgnore($packageTutorials->toArray());

        $courseCategoriesData = Course::select('id', 'categories')->get()->map(function ($course) {
            return [
                'id' => $course->id,
                'category_ids' => array_map('intval', explode(',', $course->categories)),
            ];
        });

        $courseCategories = collect($courseCategoriesData)->flatMap(function ($item) use ($categoriesId) {
            $courseId = $item['id'];
            $categoryIds = $item['category_ids'];

            return collect($categoryIds)->map(function ($categoryId) use ($courseId, $categoriesId) {
                if ($categoryId === 0 || $categoryId === null) {
                    return null;
                }
                // if (!in_array($categoryId, $categoriesId)) {
                //     return null;
                // }
                return [
                    'course_id' => $courseId,
                    'category_id' => $categoryId,
                ];
            })->filter();
        });
        CourseCategory::insertOrIgnore($courseCategories->toArray());

        $courseQuestionsData = QuestionAnswer::select('id', 'course_id')->get()->map(function ($question) {
            return [
                'id' => $question->id,
                'course_ids' => array_map('intval', explode(',', $question->course_id)),
            ];
        });

        $courseQuestions = collect($courseQuestionsData)->flatMap(function ($item) use ($coursesId) {
            $questionId = $item['id'];
            $courseIds = $item['course_ids'];

            return collect($courseIds)->map(function ($courseId) use ($questionId, $coursesId) {
                if ($courseId === 0 || $courseId === null) {
                    return null;
                }
                // if (!in_array($courseId, $coursesId)) {
                //     return null;
                // }
                return [
                    'question_id' => $questionId,
                    'course_id' => $courseId,
                ];
            })->filter();
        });

        CourseQuestion::insertOrIgnore($courseQuestions->toArray());
    }
}
