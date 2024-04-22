<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Providers\RouteServiceProvider; 
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Auth\Events\Registered;
use Session;
use Auth;  
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ApiHelper;     
use DB;  
use Carbon\Carbon; 
use DateTime;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Course;
use App\Models\Tutorial;
use App\Models\QuestionAnswer; 
use App\Models\WatchedTutorial;
use App\Models\TempTest;
use App\Models\AttemptQuestion;
use App\Models\Package;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TempOrder;
use App\Models\TempOrderDetail;
use App\Models\CourseExamDate;
use App\Models\TutorialNote;
use App\Models\Tips;
use App\Models\Bookmark;
use App\Models\PersonalSupport;
use App\Models\Cart;
use App\Models\ProgressSetting;
use App\Models\Payments;
use App\Models\QueOption;
use App\Models\TempBeforeFinishTest;
use App\Models\BlockPopup;
use App\Models\QueOptionAnswerType;
use App\Models\Rating;
use App\Models\Comment;
use App\Models\LikeUnlike;
use App\Models\TempSrQuestion;
use App\Models\AssignTutorial;
use App\Models\AssignQuestion;
use App\Models\Reportissue;
use Exception;
class ScoreController extends Controller
{    
    private $offset;
    private $limit;
    private $page;
    
    private $_api_context;


    public function score_question_list(Request $request){
        
        $data = $request->all();

        $req_data = [];
            
            $record_type = $request->record_type; //1
            $course_id = $request->course_id; // 1
            $filter_type = $request->filter_type; // 3
            $tutorial_id = $request->tutorial_id; // 1

            $courseDetailsRow = Course::find($course_id);
            
            $user_id = Auth::id();
            $catArr = []; $catArrTutorial = [];  $total_all_correct = '0';
            $tot_all_incorrect = '0';  $tot_all_que = '0'; 

            $selected_question_arr = [];
            TempSrQuestion::where(['user_id' => $user_id])->delete();

            $check_plan = Order::leftjoin('order_detail','order_detail.order_id','=','order_tbl.id')->
            leftjoin('package_tbl','package_tbl.id','=','order_detail.package_id')
            ->where(['order_detail.package_for'=>'1'])
            ->where('order_detail.expiry_date','>',date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id'=>$course_id,'order_detail.package_for'=>'1','order_tbl.user_id'=>$user_id])
            ->count();
            $is_plan_exist = ($check_plan>0) ? '1' : '0';

            $buy_question_ids = Order::leftjoin('order_detail','order_detail.order_id','=','order_tbl.id')->
            leftjoin('package_tbl','package_tbl.id','=','order_detail.package_id')->where(['order_detail.package_for'=>'1'])->where('order_detail.expiry_date','>',date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id'=>$course_id,'order_detail.package_for'=>'1','order_tbl.user_id'=>$user_id])->pluck('package_tbl.assign_question_id')->join(',');
            
            $buyQuesIdArr = (!empty($buy_question_ids)) ? explode(',',$buy_question_ids) : [];
            $buyQuesIdArr = array_unique($buyQuesIdArr);  

            $buy_tutorial_ids = Order::leftjoin('order_detail','order_detail.order_id','=','order_tbl.id')->
            leftjoin('package_tbl','package_tbl.id','=','order_detail.package_id')->where(['order_detail.package_for'=>'1'])->where('order_detail.expiry_date','>',date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id'=>$course_id,'order_detail.package_for'=>'1','order_tbl.user_id'=>$user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');
            
            $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',',$buy_tutorial_ids) : [];
            $buyTutIdArr = array_unique($buyTutIdArr);  

            $getCatgory = Category::whereIn('id',!empty([$courseDetailsRow->categories])?explode(',',$courseDetailsRow->categories):[])->where('status',1)
            ->orderBy('sort','asc')->get();


            $typeOneData = [];
            $typeTwoData = [];
            $typeThreeData = [];

            
            $filterAllRecords = [];
            if(!empty($getCatgory))
            {
                
                foreach($getCatgory as $key =>$catDt)
                {

                    if($filter_type == 3)
                    {
                        /// Total Question
                        $cat_countQuery = QuestionAnswer::where(['status'=>1,'category_id'=>$catDt->id])->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)');
                        if($is_plan_exist==1){ 
                            $cat_countQuery->whereIn('id',$buyQuesIdArr);
                        }else{ 
                            $testModeQueId = AssignQuestion::where('course_id',$course_id)->pluck('question_id')->toArray();
                            $cat_countQuery->whereIn('id',$testModeQueId);
                        }
                 
                        $cat_filter_question_id = $cat_countQuery->pluck('id')->toArray(); 
                        $tot_cat_que_dt = count($cat_filter_question_id);
                        

                        //  Incorrect Question

                        $tot_cat_correct = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'1'])->whereIn('question_id',$cat_filter_question_id)->count();

                        $tot_cat_incorrect = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'0'])->whereIn('question_id',$cat_filter_question_id)->count();

                        $filterAllRecords['category']['tot_cat_que_dt'][] = $tot_cat_que_dt;
                        $filterAllRecords['category']['tot_cat_correct_dt'][] = $tot_cat_correct;
                        $filterAllRecords['category']['tot_cat_incorrect_dt'][] = $tot_cat_incorrect;
                        $filterAllRecords['category']['tot_cat_remain_dt'][] = $tot_cat_que_dt-($tot_cat_correct+$tot_cat_incorrect);


                    }
                    if($filter_type == 2)
                    {
                        $attenptQue = AttemptQuestion::select('question_id')->where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>1]);
                        $attenptQueArr = $attenptQue->pluck('question_id')->toArray();
                        /// Total Question
                        $cat_countQuery = QuestionAnswer::where(['status'=>1,'category_id'=>$catDt->id])
                        ->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)')->whereNotIn('id',$attenptQueArr);

                        /// Generate Issue ? line no 155 to 160 ///
                        if($is_plan_exist==1){ 
                            $cat_countQuery->whereIn('id',$buyQuesIdArr);
                        }else{ 
                            $testModeQueId = AssignQuestion::where('course_id',$course_id)->whereNotIn('question_id',$attenptQueArr)->pluck('question_id')->toArray();
                            $cat_countQuery->whereIn('id',$testModeQueId);
                        }
                 
                        $cat_filter_question_id = $cat_countQuery->pluck('id')->toArray(); 
                        $tot_cat_que_dt = count($cat_filter_question_id);
                        
                        //  Incorrect Question

                        $tot_cat_correct = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'1'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                        $tot_cat_incorrect = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'0'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                        $filterAllRecords['category']['tot_cat_que_dt'][] = $tot_cat_que_dt;
                        $filterAllRecords['category']['tot_cat_correct_dt'][] = $tot_cat_correct;
                        $filterAllRecords['category']['tot_cat_incorrect_dt'][] = $tot_cat_incorrect;
                        $filterAllRecords['category']['tot_cat_remain_dt'][] = $tot_cat_que_dt-($tot_cat_correct+$tot_cat_incorrect);


                    }
                    if($filter_type == 1 || $filter_type == '')
                    {
                        $attenptQue = AttemptQuestion::select('question_id')->where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id]);
                        $attenptQueArr = $attenptQue->pluck('question_id')->toArray();
                        /// Total Question
                        $cat_countQuery = QuestionAnswer::where(['status'=>1,'category_id'=>$catDt->id])
                        ->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)')->whereNotIn('id',$attenptQueArr);

                        /// Generate Issue ? line no 155 to 160 ///
                        if($is_plan_exist==1){ 
                            $cat_countQuery->whereIn('id',$buyQuesIdArr);
                        }else{ 
                            $testModeQueId = AssignQuestion::where('course_id',$course_id)->whereNotIn('question_id',$attenptQueArr)->pluck('question_id')->toArray();
                            $cat_countQuery->whereIn('id',$testModeQueId);
                        }
                 
                        $cat_filter_question_id = $cat_countQuery->pluck('id')->toArray(); 
                        $tot_cat_que_dt = count($cat_filter_question_id);
                        
                        //  Incorrect Question

                        $tot_cat_correct = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'1'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                        $tot_cat_incorrect = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'0'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                        $filterAllRecords['category']['tot_cat_que_dt'][] = $tot_cat_que_dt;
                        $filterAllRecords['category']['tot_cat_correct_dt'][] = $tot_cat_correct;
                        $filterAllRecords['category']['tot_cat_incorrect_dt'][] = $tot_cat_incorrect;
                        $filterAllRecords['category']['tot_cat_remain_dt'][] = $tot_cat_que_dt-($tot_cat_correct+$tot_cat_incorrect);


                    }

                 
                     $getSubCat = SubCategory::orderBy('id','asc')->where(['category_id'=>$catDt->id,'status'=>1])->get();
                     if(!empty($getSubCat))
                     {
                        foreach($getSubCat as $subCatDt){
                            if($filter_type == 3)
                            {
                                /// Total Question sub_category_ids
                                $cat_countQuery = QuestionAnswer::where(['status'=>1,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id])->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)');
                                if($is_plan_exist==1){ 
                                    $cat_countQuery->whereIn('id',$buyQuesIdArr);
                                }else{ 
                                    $testModeQueId = AssignQuestion::where('course_id',$course_id)->pluck('question_id')->toArray();
                                    $cat_countQuery->whereIn('id',$testModeQueId);
                                }
                        
                                $cat_filter_question_id = $cat_countQuery->pluck('id')->toArray(); 
                              
                                $tot_cat_que_dt = count($cat_filter_question_id);
                                

                                //  Incorrect Question

                                $tot_cat_correct = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id,'is_correct'=>'1'])->whereIn('question_id',$cat_filter_question_id)->count();

                                $tot_cat_incorrect = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id,'is_correct'=>'0'])->whereIn('question_id',$cat_filter_question_id)->count();

                                $filterAllRecords['subcategory']['cat_subcat'][] = ['sub_category_ids'=>$subCatDt->id,'category_id'=>$subCatDt->category_id];
                                $filterAllRecords['subcategory']['tot_cat_que_dt'][] = $tot_cat_que_dt;
                                $filterAllRecords['subcategory']['tot_cat_correct_dt'][] = $tot_cat_correct;
                                $filterAllRecords['subcategory']['tot_cat_incorrect_dt'][] = $tot_cat_incorrect;
                                $filterAllRecords['subcategory']['tot_cat_remain_dt'][] = $tot_cat_que_dt-($tot_cat_correct+$tot_cat_incorrect);


                            }
                            if($filter_type == 2)
                            {
                                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'is_correct'=>1]);
                                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();
                                /// Total Question
                                $cat_countQuery = QuestionAnswer::where(['status'=>1,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id])
                                ->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)')->whereNotIn('id',$attenptQueArr);

                                /// Generate Issue ? line no 155 to 160 ///
                                if($is_plan_exist==1){ 
                                    $cat_countQuery->whereIn('id',$buyQuesIdArr);
                                }else{ 
                                    $testModeQueId = AssignQuestion::where('course_id',$course_id)->whereNotIn('question_id',$attenptQueArr)->pluck('question_id')->toArray();
                                    $cat_countQuery->whereIn('id',$testModeQueId);
                                }
                        
                                $cat_filter_question_id = $cat_countQuery->pluck('id')->toArray(); 
                                $tot_cat_que_dt = count($cat_filter_question_id);
                                
                                //  Incorrect Question

                                $tot_cat_correct = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id,'is_correct'=>'1'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                                $tot_cat_incorrect = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id,'is_correct'=>'0'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                                $filterAllRecords['subcategory']['tot_cat_que_dt'][] = $tot_cat_que_dt;
                                $filterAllRecords['subcategory']['tot_cat_correct_dt'][] = $tot_cat_correct;
                                $filterAllRecords['subcategory']['tot_cat_incorrect_dt'][] = $tot_cat_incorrect;
                                $filterAllRecords['subcategory']['tot_cat_remain_dt'][] = $tot_cat_que_dt-($tot_cat_correct+$tot_cat_incorrect);


                            }
                            if($filter_type == 1 || $filter_type == '')
                            {
                                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id]);
                                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();
                                /// Total Question
                                $cat_countQuery = QuestionAnswer::where(['status'=>1,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id])
                                ->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)')->whereNotIn('id',$attenptQueArr);

                                /// Generate Issue ? line no 155 to 160 ///
                                if($is_plan_exist==1){ 
                                    $cat_countQuery->whereIn('id',$buyQuesIdArr);
                                }else{ 
                                    $testModeQueId = AssignQuestion::where('course_id',$course_id)->whereNotIn('question_id',$attenptQueArr)->pluck('question_id')->toArray();
                                    $cat_countQuery->whereIn('id',$testModeQueId);
                                }
                        
                                $cat_filter_question_id = $cat_countQuery->pluck('id')->toArray(); 
                                $tot_cat_que_dt = count($cat_filter_question_id);
                                
                                //  Incorrect Question

                                $tot_cat_correct = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id,'is_correct'=>'1'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                                $tot_cat_incorrect = AttemptQuestion::where(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$subCatDt->category_id,'sub_category_ids'=>$subCatDt->id,'is_correct'=>'0'])->whereIn('question_id',$cat_filter_question_id)->whereNotIn('question_id',$attenptQueArr)->count();

                                $filterAllRecords['subcategory']['tot_cat_que_dt'][] = $tot_cat_que_dt;
                                $filterAllRecords['subcategory']['tot_cat_correct_dt'][] = $tot_cat_correct;
                                $filterAllRecords['subcategory']['tot_cat_incorrect_dt'][] = $tot_cat_incorrect;
                                $filterAllRecords['subcategory']['tot_cat_remain_dt'][] = $tot_cat_que_dt-($tot_cat_correct+$tot_cat_incorrect);


                            }

                        }
                     }
                     
                    

                }
            }
            print_r($filterAllRecords);die;
            

    }

    ///  New Question ( total Question - attempt questiong == New Question)
    public function filterTypeOne(){

    }

     ///   New questions and previously incorrect ( total Question - (attempt questiong - correct question ) )  == all Question)
     /// 100 - ( 40 - 20 ) === 80 
     public function filterTypeTwo(){

    }

    ///  All Question ( All Question , All Attemp, All Correct ,All Inccrect )  == all Question)
     /// all (100 ) == total attempt + total Remainng = all
     public function filterTypeThree($getCatgory){
        
       
     }


    public function json_view($req_status=false,$req_data="",$req_message="")
    {
        $this->status = $req_status;
        $this->code = ($req_status==false) ? "404" : "101";
        $this->data = $req_data;
        $this->message = $req_message;
        return  response()->json($this);  
    }  

}
