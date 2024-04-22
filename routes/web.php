<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\HomeController;

use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\PermissionController;
use App\Http\Controllers\Course\PackageController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Category\SubCategoryController;
use App\Http\Controllers\Course\CourseController;
use App\Http\Controllers\Tutorial\TutorialController;
use App\Http\Controllers\Quiz\QuestionController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\Question\ParagraphController;
use App\Http\Controllers\PersonalSupportController;
use App\Http\Controllers\TutoringController;
use App\Http\Controllers\SeminarController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\Course\CourseTypeController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlashCardController;
use App\Http\Controllers\MigrationSeedController;
use App\Http\Controllers\Quiz\QuestionReportController;
use App\Http\Controllers\Quiz\MockTestController;
use App\Http\Controllers\Quiz\ScoreController;
use App\Http\Controllers\ReportissueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('', function () {
    return view('welcome');
});

Route::get('fr_stripe', [StripeController::class, 'stripeSuccess']);
Route::match(['get', 'post'], 'stripe/success', [StripeController::class, 'stripeSuccess'])->name('stripe.success');
Route::match(['get', 'post'], 'stripe/cancel', [StripeController::class, 'stripeCancel'])->name('stripe.cancel');
Route::get('payment/status', [StripeController::class, 'showPaymentStatus'])->name('payment.status');

Route::get('fr_logout', [UserController::class, 'fr_logout'])->name('fr_logout');
Route::get('logout', [UserController::class, 'logout'])->name('logout');
Route::get('login', [HomeController::class, 'front_login_check'])->name('login');
Route::any('uploadckeditorimage', [HomeController::class, 'uploadckeditorimage']);
Route::get('uploadtos3', [CronController::class, 'uploadtos3']);

Route::get('/', function () {
    return view('auth.login');
});

Route::get('check_user_is_logged_in', [HomeController::class, 'check_user_is_logged_in']);

Route::group(['middleware' => ['auth', 'verified', 'role:admin']], function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class);
    Route::get('user_call_data', [UserController::class, 'call_data']);
    Route::post('user_status_update', [UserController::class, 'status_update']);
    Route::any('redirectToReact', [UserController::class, 'redirectToReact']);
    Route::get('user_excel_export', [UserController::class, 'exportIntoExcel']);
    Route::get('user_csv_export', [UserController::class, 'exportIntoCSV']);
    Route::get('getpackage', [UserController::class, 'getpackage']);
    Route::any('userimport', [UserController::class, 'userimport']);
    Route::any('assignCourse', [UserController::class, 'assignCourse']);
    Route::any('unassignCourse', [UserController::class, 'unassignCourse']);
    Route::any('sendmail', [UserController::class, 'sendmail']);
    Route::get('getcourse', [UserController::class, 'getcourse']);
    Route::post('updateExpiryDate', [UserController::class, 'updateExpiryDate'])->name('updateExpiryDate');
    Route::any('sendemail', [UserController::class, 'sendemailview']);
    Route::any('getcoursewiseuser', [UserController::class, 'getcoursewiseuser']);

    Route::resource('roles', RoleController::class);
    Route::resource('permission', PermissionController::class);

    Route::resource('category', CategoryController::class);
    Route::get('category_call_data', [CategoryController::class, 'call_data']);
    Route::post('category_get_data', [CategoryController::class, 'get_data']);
    Route::post('category_status_update', [CategoryController::class, 'status_update']);

    Route::resource('sub_category', SubCategoryController::class);
    Route::get('sub_category_call_data', [SubCategoryController::class, 'call_data']);
    Route::post('sub_category_get_data', [SubCategoryController::class, 'get_data']);
    Route::post('sub_category_status_update', [SubCategoryController::class, 'status_update']);

    Route::resource('package', PackageController::class);
    Route::get('package_call_data', [PackageController::class, 'call_data']);
    Route::post('package_get_data', [PackageController::class, 'get_data']);
    Route::post('package_status_update', [PackageController::class, 'status_update']);

    Route::get('call_question_list_data', [PackageController::class, 'call_question_list_data']);
    Route::get('call_question_list_data_course_wise', [PackageController::class, 'call_question_list_data_course_wise']);

    Route::get('assign_question/{record_id?}/{page_type?}', [PackageController::class, 'assing_que_index']);
    Route::get('assign_tutorial/{record_id?}/{page_type?}', [PackageController::class, 'assing_tutorial_index']);

    Route::get('assign_question_course_wise/{record_id?}', [PackageController::class, 'assing_question_index_course_wise']);
    Route::get('assign_tutorial_course_wise/{record_id?}', [PackageController::class, 'assing_tutorial_index_course_wise']);

    Route::get('call_tutorial_list_data', [PackageController::class, 'call_tutorial_list_data']);
    Route::get('call_tutorial_list_data_course_wise', [PackageController::class, 'call_tutorial_list_data_course_wise']);

    Route::post('question_common_assign_submit', [PackageController::class, 'question_common_assign_submit']);
    Route::post('question_common_assign_submit_course_wise', [PackageController::class, 'question_common_assign_submit_course_wise']);
    Route::post('tutorial_common_assign_submit_course_wise', [PackageController::class, 'tutorial_common_assign_submit_course_wise']);
    Route::any('updateautoassign', [PackageController::class, 'updateautoassign']);
    Route::any('updateautoassigntutorial', [PackageController::class, 'updateautoassigntutorial']);

    Route::resource('course', CourseController::class);
    Route::get('course_call_data', [CourseController::class, 'call_data']);
    Route::post('course_get_data', [CourseController::class, 'get_data']);
    Route::post('course_status_update', [CourseController::class, 'status_update']);
    Route::get('copy_course/{id}', [CourseController::class, 'copy_course']);
    Route::get('add_banner_popup/{record_id?}', [CourseController::class, 'add_banner_popup']);
    Route::any('instruction/{courseId}', [CourseController::class, 'instruction']);
    Route::post('banner_store', [CourseController::class, 'banner_store']);
    Route::resource('coursetype', CourseTypeController::class);

    Route::resource('tutorial', TutorialController::class);
    Route::any('tutorialfile/add/{tutorialId}', [TutorialController::class, 'tutorialfileadd']);
    Route::any('getcoursecategory', [TutorialController::class, 'getcoursecategory']);
    Route::any('deleteFileFromTutorial/', [TutorialController::class, 'deleteFileFromTutorial']);
    Route::get('tutorial_comment_list/{tutorialId}', [TutorialController::class, 'comment_list']);
    Route::any('replyCommentTutorial/{commentId}', [TutorialController::class, 'replyComment']);
    Route::any('editCommentTutorial/{commentId}', [TutorialController::class, 'editComment']);
    Route::any('deleteCommentTutorial/{commentId}', [TutorialController::class, 'deleteComment']);
    Route::get('tutorial_call_data', [TutorialController::class, 'call_data']);
    Route::post('tutorial_status_update', [TutorialController::class, 'status_update']);
    Route::get('assign_tutorial/{course_id?}', [TutorialController::class, 'index']);
    Route::any('settutorialorder', [TutorialController::class, 'settutorialorder']);
    Route::any('storeTutorialOrder', [TutorialController::class, 'storeTutorialOrder']);

    Route::resource('paragraph', ParagraphController::class);
    Route::post('paragraph_status_update', [ParagraphController::class, 'status_update']);
    Route::get('paragraph_call_data', [ParagraphController::class, 'call_data']);

    Route::resource('question', QuestionController::class);
    Route::get('questiontag', [QuestionController::class, 'questiontaglist']);
    Route::any('questiontag/add', [QuestionController::class, 'questiontagadd']);
    Route::any('questiontag/edit/{id}', [QuestionController::class, 'questiontagedit']);
    Route::any('questiontag/delete/{id}', [QuestionController::class, 'questiontagdelete']);
    Route::get('question-comments/{id}', [QuestionController::class, 'question_comments_list'])->name('question-comments');
    Route::post('question-comments-reply/{id}', [QuestionController::class, 'question_comments_reply'])->name('question-comments-reply');
    Route::get('question_call_data', [QuestionController::class, 'call_data']);
    Route::post('question_status_update', [QuestionController::class, 'status_update']);
    Route::get('question_drag_drop', [QuestionController::class, 'question_drag_drop']);
    Route::post('get_ajax_course_question', [QuestionController::class, 'get_ajax_course_question']);
    Route::post('get_subcategory_ajax', [QuestionController::class, 'get_subcategory']);
    Route::post('get_tutorial_ajax', [QuestionController::class, 'get_tutorial']);
    Route::get('comment_list/{questionId}', [QuestionController::class, 'comment_list']);
    Route::get('rating_list/{questionId}', [QuestionController::class, 'rating_list']);
    Route::any('replyComment/{commentId}', [QuestionController::class, 'replyComment']);
    Route::any('editComment/{commentId}', [QuestionController::class, 'editComment']);
    Route::any('deleteComment/{commentId}', [QuestionController::class, 'deleteComment']);
    Route::get('rating_list/{questionId}', [QuestionController::class, 'rating_list']);
    Route::any('deletequestion', [QuestionController::class, 'deletequestion']);

    Route::get('question-report', [QuestionReportController::class, 'index']);
    Route::get('question-report/call_data', [QuestionReportController::class, 'call_data']);
    Route::any('question_report/reply/{id}', [QuestionReportController::class, 'reply']);
    Route::any('deletequestionreport', [QuestionReportController::class, 'deletequestionreport']);

    Route::resource('tip', TipController::class);
    Route::get('tip_call_data', [TipController::class, 'call_data']);
    Route::post('tip_get_data', [TipController::class, 'get_data']);
    Route::post('tip_status_update', [TipController::class, 'status_update']);

    Route::get('progress_bar_setting', [SettingController::class, 'progress_setting']);
    Route::post('update_progress_bar_setting', [SettingController::class, 'update_progress_bar_setting']);

    Route::resource('personal_support', PersonalSupportController::class);
    Route::get('personal_support_call_data', [PersonalSupportController::class, 'call_data']);
    Route::post('personal_support_get_data', [PersonalSupportController::class, 'get_data']);
    Route::post('personal_support_status_update', [PersonalSupportController::class, 'status_update']);

    Route::get('report_issue', [ReportissueController::class, 'index']);
    Route::get('report_issue_api', [ReportissueController::class, 'call_data']);

    Route::any('tutoring', [TutoringController::class, 'updateUrl'])->name('updateUrl');

    Route::resource('seminar', SeminarController::class);
    Route::get('seminar/cms', [SeminarController::class, 'cms']);
    Route::post('seminar/cmssave', [SeminarController::class, 'cmssave']);

    Route::resource('book', BookController::class);
    Route::get('book/cms', [BookController::class, 'cms']);
    Route::post('book/cmssave', [BookController::class, 'cmssave']);

    Route::resource('flashcard', FlashCardController::class);
    Route::get('flashcard/cms', [FlashCardController::class, 'cms']);
    Route::post('flashcard/cmssave', [FlashCardController::class, 'cmssave']);

    Route::resource('testimonial', TestimonialController::class)->except(['show']);

    Route::get('transaction/{type}', [TransactionController::class, 'index']);
    Route::get('transaction/detail/{order}', [TransactionController::class, 'show']);
    Route::get('tranaction_call_data', [TransactionController::class, 'call_data']);

    Route::get('mocktest/index', [MockTestController::class, 'index']);
    Route::get('mocktest/call_data', [MockTestController::class, 'call_data']);
    Route::any('mocktest/add', [MockTestController::class, 'add']);
    Route::any('mocktest/edit/{id}', [MockTestController::class, 'edit']);
    Route::any('mocktest/delete/{id}', [MockTestController::class, 'delete']);
    Route::any('assign_question_mocktest_wise/{id}', [MockTestController::class, 'assign_question_mocktest_wise']);
    Route::any('question_common_assign_submit_mocktest_wise', [MockTestController::class, 'question_common_assign_submit_mocktest_wise']);
    Route::any('call_question_list_data_mocktest_wise', [MockTestController::class, 'call_question_list_data_mocktest_wise']);
    Route::any('getcategorywisecourse', [MockTestController::class, 'getcategorywisecourse']);
    Route::any('unassignquestion', [MockTestController::class, 'unassignquestion']);
    Route::any('unassignquestionAll/{primaryId}/{type}', [MockTestController::class, 'unassignquestionAll']);

    Route::resource('ucatscore', ScoreController::class);
    Route::get('ucat_call_data', [ScoreController::class, 'ucat_call_data']);
});

Route::any('mail/cron', [CronController::class, 'sendmail']);

Route::get('migrations-seed', MigrationSeedController::class);
