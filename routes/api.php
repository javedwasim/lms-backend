<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Course\BlockPopupController;
use App\Http\Controllers\Api\MocktestController;
use App\Http\Controllers\Api\Quiz\QuestionController;
use App\Http\Controllers\Api\Course\CourseController;
use App\Http\Controllers\Api\Course\PackageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\Quiz\CommentController;
use App\Http\Controllers\Api\Quiz\ExamController;
use App\Http\Controllers\Api\Quiz\QuizController;
use App\Http\Controllers\Api\Quiz\RatingController;
use App\Http\Controllers\Api\Quiz\ReportController;
use App\Http\Controllers\Api\Quiz\ReviewController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\ProgressController;
use App\Http\Controllers\Api\Quiz\ScoreController;
use App\Http\Controllers\Api\Quiz\SmartStudyController;
use App\Http\Controllers\Api\Tutorial\TutorialCommentController;
use App\Http\Controllers\Api\Tutorial\TutorialController;
use App\Http\Controllers\Api\Tutorial\VideoController;
use App\Http\Controllers\Api\User\SocialController;
use App\Models\Tutorial;
use App\Models\VideoComment;
use App\Models\TutorialNote;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test', function () {
    return response()->json([
        'name' => 'John',
        'city' => 'New York'
    ]);
});

Route::middleware(['auth:sanctum', 'verified'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('social-media-login', SocialController::class); // social_media_login
Route::get('packages', [PackageController::class, 'index']);
Route::get('courses', [CourseController::class, 'index']); // get_course_list
Route::get('courses/{course}', [CourseController::class, 'show']); // get_course_detail
Route::get('courses/{course}/questions', [QuestionController::class, 'courseQuestions']); // get_category_list
Route::get('courses/{course}/instructions', [CourseController::class, 'courseInstructions']); // courseInstruction

Route::get('page/{type}', [ApiController::class, 'getPageDetail']);
Route::get('/user/{user_id}/tutorials/{tutorial_id}/notes', [TutorialNote::class, 'getUserTutorials']);
Route::get('/user/{user_id}/tutorials/{tutorial_id}/comments', [VideoComment::class, 'getUserVideoComments']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('carts', [CartController::class, 'index']);
    Route::post('carts', [CartController::class, 'store']);
    Route::delete('carts/{cart}', [CartController::class, 'destroy']);

    Route::get('my-courses', [CourseController::class, 'index']); // get_course_list

    Route::post('purchase/plan', [PaymentController::class, 'purchasePlan']);
    Route::post('stripe/call', [PaymentController::class, 'stripeCall']);

    Route::delete('quiz', [QuizController::class, 'destroy']); // deleteapi
    Route::post('quiz/reset', [QuizController::class, 'resetQuiz']); // reset_quiz
    Route::post('quiz/additional-time-category', [QuizController::class, 'additionalTimeCategory']); // additional_time_category_for_quiz

    Route::get('profiles/user', [ProfileController::class, 'show']); // get_user_profile
    Route::post('profiles/{user}', [ProfileController::class, 'update']); // update_user_profile
    Route::put('profiles/update-password', [ProfileController::class, 'updatePassword']); // changepassword

    Route::get('courses/{course}/smart-study', SmartStudyController::class);

    Route::post('questions/{questions}/rating', [RatingController::class, 'store']); // add_rating

    Route::get('questions/{question}/comments', [CommentController::class, 'index']); // get_comment
    Route::post('questions/{question}/comments', [CommentController::class, 'store']); // add_comment

    Route::post('questions/{question}/react', [QuestionController::class, 'react']); // like_unlike

    Route::any('reports', [ReportController::class, 'data']); // report_issue_data
    Route::post('reports', [ReportController::class, 'store']); // report_issue

    Route::post('courses/{course}/progress', [ProgressController::class, 'index']); // get_progress_list

    Route::post('questions/review', [ReviewController::class, 'store']); // add_question_for_review

    Route::get('questions/{question}', [QuestionController::class, 'show']); // get_current_question
    Route::get('questions/{question}/feedback', [QuestionController::class, 'questionFeedback']); // get_questions_list
    Route::post('questions/quiz', [QuizController::class, 'applyfilter']); // applyfilter

    Route::post('/tests/review-before-finish', [ReviewController::class, 'reviewTestBeforeFinish']); // review_test_before_finish x
    Route::post('/tests/review-after-finish', [ReviewController::class, 'reviewTestAfterFinish']); // review_test_after_finish x

    Route::post('/tests/score-after-finish', [ScoreController::class, 'getScoreAfterTestFinish']); // get_score_after_test_finish x
    Route::post('/tests/score-and-questions-after-finish', [ScoreController::class, 'getScoreAfterTestFinishWithAllQuestions']); // get_score_after_test_finish_with_all_question x

    Route::post('courses/{course}/examdate', [ExamController::class, 'courseExamdate']); // add_examdate_to_course

    Route::post('tutorials/{tutorial}/notes', [TutorialController::class, 'store']); // add_tutorial_note
    Route::put('tutorials/{tutorial}/notes/{tutorialNote}/edit', [TutorialController::class, 'update']); // edit_tutorial_note
    Route::delete('tutorials/notes/{tutorialNote}', [TutorialController::class, 'destroy']); // delete_tutorial_note
    Route::post('tutorials/{tutorial}/bookmarks', [TutorialController::class, 'bookmark']); // add_tutorial_bookmark
    Route::post('tutorials/{tutorial}/watchlist', [TutorialController::class, 'storeTutorialWatchlist']); // add_watched_tutorial

    Route::post('tests/make', [QuizController::class, 'makeTest']); // make_test x
    Route::post('tests/finish', [QuizController::class, 'finishTest']); // finish_test x

    Route::delete('blockpopups', [BlockPopupController::class, 'destroy']); // delete_popup_or_banner

    // Route::post('allQuestionFilterTypeNew', [ApiController::class, 'allQuestionFilterTypeNew']); what is this api about ??
    // Route::post('get_category_list_new', [ApiController::class, 'get_category_list_new']);
    Route::get('courses/{course}/tutorials', [TutorialController::class, 'index']); // get_category_list_tutorial
    Route::get('courses/{course}/tutorials/category/{category}', [TutorialController::class, 'show']); // get_tutorial_list x

    Route::get('tutorials/{tutorial}/comments', [TutorialCommentController::class, 'index']); // getComment

    Route::post('transactions', [PaymentController::class, 'transactions']); // tranactions x
    Route::put('transaction', [PaymentController::class, 'updateTransaction']); // update_transaction x

    Route::post('tutorials/{tutorial}/clear-watchtime', [TutorialController::class, 'clearWatchTime']); // clear_watch_time

    Route::post('comments/{comment}/react', [TutorialController::class, 'reactComment']); // like_comment

    Route::get('contact', ContactController::class); // contactDetail

    Route::post('tutorials/{tutorial}/comments', [TutorialCommentController::class, 'store']); // addVideoComment
    Route::post('tutorials/comments/like', [TutorialCommentController::class, 'videoCommentLike']); // videoCommentLike

    Route::get('mocktests', [MocktestController::class, 'index']); // mocktest
    Route::post('mocktests', [MocktestController::class, 'store']); // make_mocktest_test
    Route::get('mocktests/{mocktest}', [MocktestController::class, 'show']); // getmocktestDetail
    Route::get('mocktests/{mocktest}/category/{category}', [MocktestController::class, 'mocktestCategory']); // mocktestcategory/{id}

    Route::post('mocktests/{mocktest}/start', [MocktestController::class, 'startMocktest']); // startmocktest
    Route::post('mocktests/{mocktest}/questions', [MocktestController::class, 'mocktestQuestions']); // get_mocktest_questions_list
    Route::get('mocktest/{mocktest}/questions-after', [MocktestController::class, 'mocktestQuestionsAfter']); // get_mocktest_questions_list_after

    Route::post('mocktest/{mocktest}/review-before-finish', [MocktestController::class, 'mocktestReviewBeforeFinish']); // review_mocktest_before_finish
    Route::post('mocktest/{mocktest}/review-after-finish', [MocktestController::class, 'mocktestReviewAfterFinish']); // review_mocktest_after_finish
    Route::post('mocktest/{mocktest}/review-after-finish-by-category', [MocktestController::class, 'mocktestReviewAfterFinishByCategory']); // review_mocktest_after_finish_category

    Route::post('mocktests/{mocktest}/finish', [MocktestController::class, 'mocktestFinish']); // finish_mocktest
    Route::get('mocktests/{mocktest}/score', [MocktestController::class, 'mocktestScore']); // get_score_after_mocktest_finish
    Route::get('mocktests/questions/{question}/feedback', [QuestionController::class, 'mockQuestionFeedback']); // get_questions_list

    Route::get('performance/{id}', PerformanceController::class); // getPerformanceAll
    Route::post('performance', [PerformanceController::class, 'createUserPerformanceReport']); // getPerformanceAll
});

Route::get('unauthorized', function () {
    return response()->json(['status' => false, 'code' => 401, 'data' => [], 'message' => 'Unauthorized user.']);
})->name('api.unauthorized');

Route::post('signup_api', [AuthController::class, 'signup_api']);
Route::post('login_api', [AuthController::class, 'login_api']);
Route::post('social_media_login', [AuthController::class, 'social_media_login']);
Route::post('reset_password_api', [AuthController::class, 'reset_password_api']);
Route::post('test_finish', [MocktestController::class, 'mocktestFinish']); // finish_mocktest

Route::post('get_course_list', [ApiController::class, 'get_course_list']);
Route::post('get_course_list_old', [ApiController::class, 'get_course_listold']);

Route::post('get_package_list', [ApiController::class, 'get_package_list']);

Route::post('get_course_detail', [ApiController::class, 'get_course_detail']);
Route::post('get_percentile_data', [ApiController::class, 'getPercentileData']);
Route::get('getPerformanceAll/{id}', [ApiController::class, 'getPerformanceAll']);
Route::get('course/Instruction/{id}', [ApiController::class, 'courseInstruction']);

Route::group(['middleware' => ['auth:api']], function () {

    Route::get('reset_quiz', [ApiController::class, 'reset_quiz']);
    Route::post('additional_time_category_for_quiz', [ApiController::class, 'additional_time_category_for_quiz']);

    Route::get('get_user_profile', [ApiController::class, 'get_user_profile']);
    Route::post('update_user_profile', [ApiController::class, 'update_user_profile']);
    Route::post('deleteapi', [ApiController::class, 'deleteapi']);
    Route::post('add_rating', [ApiController::class, 'add_rating']);
    Route::post('add_comment', [ApiController::class, 'add_comment']);
    Route::post('get_comment', [ApiController::class, 'get_comment']);
    Route::post('like_unlike', [ApiController::class, 'like_unlike']);
    Route::post('report_issue', [ApiController::class, 'report_issue']);
    Route::any('report_issue_data', [ApiController::class, 'report_issue_data']);

    Route::any('stripe_call', [ApiController::class, 'stripe_call']);

    Route::post('get_progress_list', [ApiController::class, 'get_progress_list']);

    Route::post('add_question_for_review', [ApiController::class, 'add_question_for_review']);

    Route::post('add_to_cart', [ApiController::class, 'add_to_cart']);
    Route::post('get_cart_list', [ApiController::class, 'get_cart_list']);
    Route::post('delete_cart_item', [ApiController::class, 'delete_cart_item']);

    Route::post('get_questions_list', [ApiController::class, 'get_questions_list']);
    Route::post('review_test_before_finish', [ApiController::class, 'review_test_before_finish']);
    Route::post('review_test_after_finish', [ApiController::class, 'review_test_after_finish']);
    Route::post('get_score_after_test_finish', [ApiController::class, 'get_score_after_test_finish']);
    Route::post('get_score_after_test_finish_with_all_question', [ApiController::class, 'get_score_after_test_finish_with_all_question']);

    Route::post('add_examdate_to_course', [ApiController::class, 'add_examdate_to_course']);
    Route::post('add_tutorial_note', [ApiController::class, 'add_tutorial_note']);
    Route::post('edit_tutorial_note', [ApiController::class, 'edit_tutorial_note']);
    Route::post('delete_tutorial_note', [ApiController::class, 'delete_tutorial_note']);
    Route::post('add_tutorial_bookmark', [ApiController::class, 'add_tutorial_bookmark']);

    Route::post('add_watched_tutorial', [ApiController::class, 'add_watched_tutorial']);
    Route::post('make_test', [ApiController::class, 'make_test']);
    Route::post('finish_test', [ApiController::class, 'finish_test']);

    Route::post('delete_popup_or_banner', [ApiController::class, 'delete_popup_or_banner']);
    Route::post('get_category_list', [ApiController::class, 'get_category_list']);
    Route::post('get_category_list_question', [QuestionController::class, 'get_category_list']); // 1
    Route::post('get_category_list_tutorial', [ApiController::class, 'get_category_list_tutorial']);
    Route::post('allQuestionFilterTypeNew', [ApiController::class, 'allQuestionFilterTypeNew']);
    Route::post('get_category_list_new', [ApiController::class, 'get_category_list_new']);
    Route::get('get_tutorial_list', [ApiController::class, 'get_tutorial_list']);
    Route::post('getComment', [ApiController::class, 'getComment']);

    Route::post('purchase_plan', [ApiController::class, 'purchase_plan']);

    Route::post('update_transaction', [ApiController::class, 'update_transaction']);

    Route::post('score_question_list', [ScoreController::class, 'score_question_list']);

    Route::post('clear_watch_time', [ApiController::class, 'clearWatchTime']);

    Route::post('like_comment', [ApiController::class, 'like_comment']);

    Route::get('getprofile', [ApiController::class, 'getprofile']);
    Route::post('updateprofile', [ApiController::class, 'updateprofile']);
    Route::post('changepassword', [ApiController::class, 'changePassword']);
    Route::post('tranactions', [ApiController::class, 'tranactions']);
    Route::get('contactDetail', [ApiController::class, 'contactDetail']);
    Route::post('addVideoComment', [ApiController::class, 'addVideoComment']);
    Route::post('videoCommentLike', [ApiController::class, 'videoCommentLike']);
    Route::get('mocktest', [MocktestController::class, 'index']);
    Route::get('mocktestcategory/{id}', [MocktestController::class, 'mocktestcategory']);
    Route::post('getmocktestDetail', [MocktestController::class, 'getmocktestDetail']);
    Route::post('startmocktest', [MocktestController::class, 'startmocktest']);
    Route::post('get_mocktest_questions_list', [MocktestController::class, 'get_mocktest_questions_list']);
    Route::post('get_mocktest_questions_list_after', [MocktestController::class, 'get_mocktest_questions_list_after']);
    Route::post('make_mocktest_test', [MocktestController::class, 'make_mocktest_test']);
    Route::post('review_mocktest_before_finish', [MocktestController::class, 'review_mocktest_before_finish']);
    Route::post('review_mocktest_after_finish', [MocktestController::class, 'review_mocktest_after_finish']);
    Route::post('review_mocktest_after_finish_category', [MocktestController::class, 'review_mocktest_after_finish_categoryWise']);
    Route::post('finish_mocktest', [MocktestController::class, 'finish_mocktest']);
    Route::post('get_score_after_mocktest_finish', [MocktestController::class, 'get_score_after_mocktest_finish']);
    // Route::post('applyfilter', [ApiController::class, 'applyfilter']);
});
Route::get('get_category_list_mn', [ApiController::class, 'get_category_list_mn']);

Route::get('check_mailsend_api', [ApiController::class, 'check_mailsend_api']);

Route::get('mailchimp_test', [ApiController::class, 'mailchimp_test']);

Route::stripeWebhooks('/stripe_webhook');
