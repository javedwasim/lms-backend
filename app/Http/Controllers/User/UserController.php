<?php

namespace App\Http\Controllers\User;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\MailQueue;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Package;
use App\Models\TempPerformance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use yajra\Datatables\Datatables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('users.index', [
            'courses' => Course::where('status', '1')->orderBy('course_name', 'asc')->get(),
            'CourseType' => CourseType::all(),
        ]);
    }

    public function call_data(Request $request)
    {
        $buy_users_ids = array();
        if ($request->course_id) {
            $course_id = $request->course_id;
            $buy_user_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.package_for' => '1'])->where('order_detail.particular_record_id', $course_id)->pluck('order_tbl.user_id')->toArray();

            $buy_users_ids = array_unique($buy_user_arr);
        }

        // $get_data = User::orderBy('id', 'desc')->whereHas('roles', function ($query) {
        //     return $query->where('name', '!=', 'admin');
        // });

        $get_data = User::orderBy('id', 'desc');

        if (!empty($request->signupdate)) {
            $get_data = $get_data->whereDate("created_at", date("Y-m-d", strtotime($request->signupdate)));
        }
        if (!empty($request->last_login_date)) {
            $get_data = $get_data->whereDate("last_login_date", date("Y-m-d", strtotime($request->last_login_date)));
        }
        if (!empty($buy_users_ids)) {
            $get_data = $get_data->whereIn('id', $buy_users_ids);
        }

        if ($request->search['value']) {
            $get_data = $get_data->where(function($query) {
                $query->where('name', 'like', '%' . request('search')['value'] . '%')
                    ->orWhere('email', 'like', '%' . request('search')['value'] . '%');
            });
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;
        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("name", function ($get_data) {
                return $get_data->name ?? 'N/A';
            })
            ->editColumn("checkbox", function ($get_data) {
                return '<div class="form-check">
                <input type="checkbox" name="userId[]" class="userids"  value="' . $get_data->id . '"   />
                <span></span>
            </div>';
            })
            ->editColumn("email", function ($get_data) {
                return $get_data->email ?? 'N/A';
            })
            ->editColumn("course_list", function ($get_data) {
                $user_id = $get_data->id;
                $buy_user_course_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.package_for' => '1'])->where('user_id', $user_id)->pluck('order_detail.particular_record_id')->toArray();

                $buy_user_course_ids = array_unique($buy_user_course_arr);

                $getCourseDt = Course::orderBy('id', 'desc')->whereIn('id', $buy_user_course_ids)->pluck('course_name')->toArray();

                $course_list = (count($getCourseDt) > 0) ? implode(',', $getCourseDt) : "";
                $course_list = wordwrap($course_list, 48, "<br />");

                return !empty($course_list) ? $course_list : 'N/A';
            })
            ->editColumn("status", function ($get_data) {

                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="user"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="user"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at)) ?? 'N/A';
            })
            ->editColumn("last_login", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->last_login_date)) ?? 'N/A';
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('users.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('users.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';
                $cr_form .= '<a class="btn bg-gradient-info btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('users.edit', $get_data->id) . '" ><i class="fa fa-pencil"></i></a>';

                $cr_form .= '<form id="form_del_' . $get_data->id . '" action="' . route('users.destroy', $get_data->id) . '" method="POST">
                                <input type="hidden" name="_token" value="' . csrf_token() . '" />
                                <input type="hidden" name="_method" value="DELETE" />
                                <button type="submit" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm s_btn1 delete_record" data-id="' . $get_data->id . '" data-action="user" ><i class="fa fa-times"></i></button>
                            </form>';

                // $cr_form .= '<a class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('user/delete/' . $get_data->id) . '" ><i class="fa fa-times"></i></a>';

                $cr_form .= '</form>';

                return $cr_form;
            })->rawColumns(['phone_no', 'status', 'action', 'course_list', 'checkbox'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => Role::where('id', '!=', '1')->pluck('name', 'id')->all(),
            'course' => Course::all(),
            'CourseType' => CourseType::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required|same:confirm-password',
        ]);

        $input = $request->all();

        $allInput = $input;
        unset($input['course']);
        unset($input['package']);
        unset($input['confirm-password']);

        if ($request->hasFile('profile_photo_path')) {
            $profile_photo_img_path = $request->profile_photo_path->store('profile_photo_path');
            $input['profile_photo_path'] = $profile_photo_img_path;
        }

        $input['verify_code'] = '';
        $input['email_verified_at'] = date("Y-m-d h:m:s", strtotime("now"));
        $input['phone_verified_at'] = date("Y-m-d h:m:s", strtotime("now"));

        $input['password'] = Hash::make($input['password']);
        // $input['role_id'] = $input['roles'][0];
        unset($input['roles']);
        unset($input['_token']);
        unset($input['course_type_id']);

        $userId = User::insertGetId($input);

        if (!empty($allInput['course']) && count($allInput['course']) > 0 && !empty($allInput['course'][0])) {

            foreach ($allInput['course'] as $key => $val) {

                $getPackageDetail = Package::find($allInput['package'][$key]);
                $expiry_date = '';
                $curr_date = date("Y-m-d H:i:s");
                if ($getPackageDetail->packagetype == "free") {
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($curr_date)));
                } elseif ($getPackageDetail->packagetype == "onetime") {
                    $expiry_date = $getPackageDetail->expire_date;
                } else {
                    $package_for_month = (isset($getPackageDetail->package_for_month)) ? $getPackageDetail->package_for_month : '';
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                }


                $insertOrder = new Order();
                $insertOrder->user_id = $userId;
                $insertOrder->package_for = 1;
                $insertOrder->total_amount = $getPackageDetail->price;
                $insertOrder->payment_status = 1;
                $insertOrder->assignFromAdmin = 1;
                $insertOrder->save();

                $insertOrderDetail = new OrderDetail();
                $insertOrderDetail->order_id = $insertOrder->id;
                $insertOrderDetail->package_for = 1;
                $insertOrderDetail->particular_record_id = $val;
                $insertOrderDetail->package_id = $allInput['package'][$key];
                $insertOrderDetail->price = $getPackageDetail->price;
                $insertOrderDetail->expiry_date = $expiry_date;
                $insertOrderDetail->save();
            }
        }
        $user = User::find($userId);
        // $user->assignRole($request->input('roles'));
        $user->assignRole('user');

        return redirect("users/" . $userId . "/edit")->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        $subscription = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('course_tbl', "order_detail.particular_record_id", "=", "course_tbl.id")
            ->leftjoin('package_tbl', "order_detail.package_id", "=", "package_tbl.id")->where(['order_detail.package_for' => '1'])->where('user_id', $user->id)->get(['order_detail.*', 'course_tbl.course_name', 'package_tbl.package_title']);

        return view('users.show', [
            'user' => $user,
            'subscription' => $subscription,
            'page_title' => 'user'
        ]);
    }

    public function edit(User $user)
    {
        $subscription = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('course_tbl', "order_detail.particular_record_id", "=", "course_tbl.id")
            ->leftjoin('package_tbl', "order_detail.package_id", "=", "package_tbl.id")->where(['order_detail.package_for' => '1'])->where('user_id', $user->id)->get(['order_detail.*', 'course_tbl.course_name', 'package_tbl.package_title']);
        $userRole = $user->whereHas('roles', function ($query) {
            return $query->where('name', '!=', 'admin');
        })->get()[0]->roles->pluck('name', 'id')->all();

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name', 'id')->all(),
            'userRole' => $userRole,
            'page_title' => 'user',
            'course' => Course::all(),
            'subscription' => $subscription,
            'CourseType' => CourseType::all()
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
        ]);


        $input = $request->all();

        $password = $request->password;
        $allInput = $input;
        unset($input['password']);
        unset($input['confirm-password']);
        $page_title = ($request->page_title) ? $request->page_title : 'user';
        if (!empty($password)) {
            $input['password'] = bcrypt($password);
        }

        $input['verify_code'] = '';
        $input['email_verified_at'] = date("Y-m-d h:m:s", strtotime("now"));
        $input['phone_verified_at'] = date("Y-m-d h:m:s", strtotime("now"));

        if ($request->hasFile('profile_photo_path')) {
            $profile_photo_img_path = $request->profile_photo_path->store('profile_photo_path');
            $input['profile_photo_path'] = $profile_photo_img_path;
        }

        // $input['role_id'] = $input['roles'][0];

        unset($input['roles']);
        unset($input['_token']);
        $user->update($input);

        if (!empty($allInput['course']) && count($allInput['course']) > 0 && !empty($allInput['course'][0])) {

            foreach ($allInput['course'] as $key => $val) {

                $getPackageDetail = Package::find($allInput['package'][$key]);
                $expiry_date = '';
                $curr_date = date("Y-m-d H:i:s");
                if ($getPackageDetail->packagetype == "free") {
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($curr_date)));
                } elseif ($getPackageDetail->packagetype == "onetime") {
                    $expiry_date = $getPackageDetail->expire_date;
                } else {

                    $package_for_month = (isset($getPackageDetail->package_for_month)) ? $getPackageDetail->package_for_month : '';
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                }

                $insertOrder = new Order();
                $insertOrder->user_id = $user->id;
                $insertOrder->package_for = 1;
                $insertOrder->total_amount = $getPackageDetail->price;
                $insertOrder->payment_status = 1;
                $insertOrder->assignFromAdmin = 1;
                $insertOrder->save();

                $insertOrderDetail = new OrderDetail();
                $insertOrderDetail->order_id = $insertOrder->id;
                $insertOrderDetail->package_for = 1;
                $insertOrderDetail->particular_record_id = $val;
                $insertOrderDetail->package_id = $allInput['package'][$key];
                $insertOrderDetail->price = $getPackageDetail->price;
                $insertOrderDetail->expiry_date = $expiry_date;
                $insertOrderDetail->save();
            }
        }

        // $user->assignRole($request->input('roles'));
        return redirect()->back()->with('success', 'Profile updated successfully');
        if ($page_title == 'profile') {
            return redirect("/users")->with('success', 'Profile updated successfully');
        } else {
            return redirect("/users")->with('success', 'User updated successfully');
        }
    }

    public function destroy($id)
    {
        User::find($id)->delete();
        return redirect("/users")
            ->with('success', 'User deleted successfully');
    }

    public function profile_show()
    {
        return view('users.show', [
            'user' => auth()->user(),
            'page_title' => 'profile'
        ]);
    }

    public function profile_edit()
    {
        $user = User::find(auth()->user()->id);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name', 'id')->all(),
            'userRole' => $user->getUserRole()->pluck('name', 'id')->all(),
            'page_title' => 'profile'
        ]);
    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        User::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'User status updated.']);
    }

    public function logout()
    {
        auth()->logout();
        return redirect('/login');
    }

    public function redirectToReact(Request $request)
    {
        $userIds = $request->userids;
        $insertData = array();
        $i = 0;
        $rand = rand(000000, 999999);
        foreach ($userIds as $val) {
            $buy_user_course_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.package_for' => '1'])->where('user_id', $val)->pluck('order_detail.particular_record_id')->toArray();

            $buy_user_course_ids = array_unique($buy_user_course_arr);
            foreach ($buy_user_course_ids as $val2) {
                $insertData[$i]['course_id'] = $val2;
                $insertData[$i]['user_id'] = $val;
                $insertData[$i]['uniqueId'] = $rand;
                $i++;
            }
        }
        TempPerformance::insert($insertData);
        echo $rand;
    }

    public function getpackage(Request $request)
    {
        $id = $request->id;
        $getPackage = Package::where("package_for", 1)->where("perticular_record_id", $id)->get(['id', 'package_title']);
        return json_encode($getPackage);
    }

    public function userimport(Request $request)
    {
        $file = $request->file('file');

        $newName = time() . $file->getClientOriginalName();

        $destinationPath = public_path('uploads/studentcsv');
        $file->move($destinationPath, $newName);

        $file = fopen($destinationPath . "/" . $newName, "r");
        $error = array();
        $i = 0;

        while (!feof($file)) {
            $data = fgetcsv($file);
            if ($i == 0) {
                $i++;
                continue;
            }
            if (empty($data[0])) {
                $i++;
                continue;
            }

            $getPackageDetail = Package::find(@$data[6]);

            $checkUser = User::where("email", @$data[1])->first();

            if (!empty($checkUser)) {
                // we will check if the user is enrolled in the course already or not
                $package_ids = OrderDetail::where('particular_record_id', $data[5])->whereHas(
                    'order',
                    function ($query) use ($checkUser) {
                        $query->where('user_id', $checkUser->id);
                    }
                )->pluck('package_id');

                // if user is not enrolled then enrolled him in the course and package otherwise skip
                if (count($package_ids) === 0) {
                    // 1. add new order in order_tbl
                    $order = Order::create([
                        'user_id' => $checkUser->id,
                        'package_for' => 1, // use 1 for courses
                        'total_amount' =>  $getPackageDetail->price,
                        'payment_status' => 1,
                        'assignFromAdmin' => 1,
                    ]);

                    $expiry_date = '';
                    $curr_date = date("Y-m-d H:i:s");

                    if ($getPackageDetail->packagetype == "free") {
                        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($curr_date)));
                    } elseif ($getPackageDetail->packagetype == "onetime") {
                        $expiry_date = $getPackageDetail->expire_date;
                    } else {
                        $package_for_month = (isset($getPackageDetail->package_for_month)) ? $getPackageDetail->package_for_month : '';
                        $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                    }

                    // 2. add new row in order_detail table with package/course id as particular_record_id
                    $orderDetail = OrderDetail::create([
                        'order_id' => $order->id,
                        'package_for' => $order->package_for,
                        'particular_record_id' => $data[5], // 29 for UCAT
                        'package_id' => $data[6], // 49 for 'UCAT Course' package
                        'price' => $order->total_amount,
                        'expiry_date' => $expiry_date, // set an expiry of 1 year
                    ]);
                }
            } else {
                $user = new User();
                $user->name = @$data[0];
                $user->email = @$data[1];
                $user->country_code = @$data[3];
                $user->status = @$data[2];
                $user->password = Hash::make(@$data[4]);
                $user->role_id = 3;
                $user->save();

                if (!empty(@$data[6])) {
                    $getPackageDetail = Package::find(@$data[6]);

                    $expiry_date = '';
                    $curr_date = date("Y-m-d H:i:s");

                    if ($getPackageDetail->packagetype == "free") {
                        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($curr_date)));
                    } elseif ($getPackageDetail->packagetype == "onetime") {
                        $expiry_date = $getPackageDetail->expire_date;
                    } else {
                        $package_for_month = (isset($getPackageDetail->package_for_month)) ? $getPackageDetail->package_for_month : '';
                        $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                    }

                    $insertOrder = new Order();
                    $insertOrder->user_id = $user->id;
                    $insertOrder->package_for = 1;
                    $insertOrder->total_amount = $getPackageDetail->price;
                    $insertOrder->payment_status = 1;
                    $insertOrder->assignFromAdmin = 1;
                    $insertOrder->save();

                    $insertOrderDetail = new OrderDetail();
                    $insertOrderDetail->order_id = $insertOrder->id;
                    $insertOrderDetail->package_for = 1;
                    $insertOrderDetail->particular_record_id = @$data[5];
                    $insertOrderDetail->package_id = @$data[6];
                    $insertOrderDetail->price = $getPackageDetail->price;
                    if (!empty($data[7])) {
                        $insertOrderDetail->expiry_date = @$data[7] ? date('Y-m-d H:i:s', strtotime($data[7])) : '';
                    } else {
                        $insertOrderDetail->expiry_date = $expiry_date;
                    }
                    $insertOrderDetail->save();

                    $user->assignRole(3);
                }
            }
            $i++;
        }

        fclose($file);
        return redirect("/users")->with('success', 'User created successfully');
    }

    public function assignCourse(Request $request)
    {

        $userId = $request->userids;
        $course = $request->course;
        $package = $request->package;

        if (empty($userId)) {
            return redirect()->back()->with('error', 'Please Select Atleast One User');
        }
        if (!empty($userId)) {
            $userIds = explode(",", $userId);
            foreach ($userIds as $val) {

                $getPackageDetail = Package::find(@$package);

                $expiry_date = '';
                $curr_date = date("Y-m-d H:i:s");
                if ($getPackageDetail->packagetype == "free") {
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($curr_date)));
                } elseif ($getPackageDetail->subscription == "subscription") {

                    $package_for_month = (isset($getPackageDetail->package_for_month)) ? $getPackageDetail->package_for_month : '';
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                } elseif ($getPackageDetail->packagetype == "onetime") {

                    $expiry_date = $getPackageDetail->expire_date;
                } else {

                    $package_for_month = isset($getPackageDetail->package_for_month) ? $getPackageDetail->package_for_month : '';
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                }

                $insertOrder = new Order();
                $insertOrder->user_id = $val;
                $insertOrder->package_for = 1;
                $insertOrder->total_amount = $getPackageDetail->price;
                $insertOrder->payment_status = 1;
                $insertOrder->assignFromAdmin = 1;
                $insertOrder->save();

                $insertOrderDetail = new OrderDetail();
                $insertOrderDetail->order_id = $insertOrder->id;
                $insertOrderDetail->package_for = 1;
                $insertOrderDetail->particular_record_id = @$course;
                $insertOrderDetail->package_id = @$package;
                $insertOrderDetail->price = $getPackageDetail->price;
                $insertOrderDetail->expiry_date = $expiry_date;
                $insertOrderDetail->save();
            }
        }

        return redirect("/users")->with('success', 'User Enroll successfully');
    }

    public function unassignCourse(Request $request)
    {

        $userId = $request->userids;
        $course = $request->course;
        if (empty($userId)) {
            return redirect()->back()->with('error', 'Please Select Atleast One User');
        }

        if (!empty($userId)) {
            $userIds = explode(",", $userId);
            foreach ($userIds as $val) {

                $orderIds = Order::where("user_id", $val)->pluck("id");
                $orderDetails = OrderDetail::where("package_for", 1)->where("particular_record_id", $course)->whereIn("order_id", $orderIds)->get();
                foreach ($orderDetails as $val2) {
                    $insertOrder = Order::find($val2->order_id);
                    $insertOrder->user_id = $val;
                    $insertOrder->package_for = 1;
                    $insertOrder->payment_status = 1;
                    $insertOrder->assignFromAdmin = 1;
                    $insertOrder->unassignFromAdmin = 1;
                    $insertOrder->deleted_at = date("Y-m-d H:i:s");
                    $insertOrder->save();

                    $insertOrderDetail = OrderDetail::find($val2->id);
                    $insertOrderDetail->order_id = $insertOrder->id;
                    $insertOrderDetail->package_for = 1;
                    $insertOrderDetail->particular_record_id = @$course;
                    $insertOrderDetail->unassignFromAdmin = 1;
                    $insertOrderDetail->deleted_at = date("Y-m-d H:i:s");
                    $insertOrderDetail->save();
                }
            }
        }
        if ($request->ajax()) {
            return response()->json(array("status" => 200));
        } else {
            return redirect("/users")->with('success', 'User Enroll successfully');
        }
    }
    public function sendmail(Request $request)
    {

        $userId = $request->userids;
        $subject = $request->subject;
        $message = $request->message;
        if (empty($userId)) {
            return redirect()->back()->with('error', 'Please Select Atleast One User');
        }

        if (!empty($userId)) {
            $userIds = explode(",", $userId);
            $userDetail = User::whereIn("id", $userIds)->get();
            $insertData = array();
            foreach ($userDetail as $key => $val) {
                $insertData[$key]['user_id'] = $val->id;
                $insertData[$key]['email'] = $val->email;
                $insertData[$key]['subject'] = $subject;
                $insertData[$key]['message'] = $message;
                $insertData[$key]['status'] = 1;
            }

            MailQueue::insert($insertData);
        }

        return redirect()->back()->with('success', 'Email is added in a queue');
    }
    public function getcourse(Request $request)
    {
        $id = $request->id;
        $getPackage = Course::where("course_type_id", $id)->get(['id', 'course_name']);
        return json_encode($getPackage);
    }

    public function updateExpiryDate(Request $request)
    {
        $id = $request->modal_id;

        $subscription = OrderDetail::where('id', $id)->first();
        if (!empty($subscription)) {
            $subscription->expiry_date = $request->modal_expiry_date;
            $subscription->save();
            return redirect()->back()->with('success', 'Update successfully');
        } else {
            return redirect()->back()->with('error', 'Failed!');
        }
    }
    public function sendemailview(Request $request)
    {
        $mailUserIds = '';
        $userlist = User::where("role_id", 3)->where("status", "1")->get();
        $courseList = Course::where("status", "1")->get();
        if (strtolower($request->method()) == "post" && !isset($request->emailUserids)) {
            $userId = $request->userid;
            $subject = $request->subject;
            $message = $request->message;
            if (empty($userId)) {
                return redirect()->back()->with('error', 'Please Select Atleast One User');
            }

            if (!empty($userId)) {
                $userDetail = User::whereIn("id", $userId)->get();
                $insertData = array();
                foreach ($userDetail as $key => $val) {
                    $insertData[$key]['user_id'] = $val->id;
                    $insertData[$key]['email'] = $val->email;
                    $insertData[$key]['subject'] = $subject;
                    $insertData[$key]['message'] = $message;
                    $insertData[$key]['status'] = 1;
                }

                MailQueue::insert($insertData);
            }
            if (!empty($request->testemail)) {
                $insertData[0]['user_id'] = 0;
                $insertData[0]['email'] = $request->testemail;
                $insertData[0]['subject'] = $subject;
                $insertData[0]['message'] = $message;
                $insertData[0]['status'] = 1;
                MailQueue::insert($insertData);
            }
            return redirect()->back()->with('success', 'Email is added in a queue');
        }
        if (isset($request->emailUserids)) {
            $mailUserIds = implode(",", $request->emailUserids);
        }
        return view('users.sendemail', compact("userlist", "courseList", "mailUserIds"));
    }

    public function getcoursewiseuser(Request $request)
    {
        $id = $request->id;
        $insertOrderDetail = OrderDetail::where("particular_record_id", $id)->where("package_for", 1)->pluck("order_id");
        $alluserId = Order::whereIn("id", $insertOrderDetail)->pluck("user_id");
        if (!empty($id)) {
            $getPackage = User::whereIn("id", $alluserId)->get(['id', 'name', 'email']);
        } else {
            $getPackage = User::where("role_id", 3)->where("status", "1")->get(['id', 'name', 'email']);
        }

        return json_encode($getPackage);
    }
    public function exportIntoExcel(Request $request)
    {
        $start_date = ($request->fromdate) ? $request->fromdate . " 00:00:00" : date("Y-m-d") . " 00:00:00";
        $end_date = ($request->todate) ? $request->todate . " 23:59:59" : date("Y-m-d H:i:s");
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime($end_date));

        $members = User::pluck('id')->toArray();
        return Excel::download(new UserExport($members, $start_date, $end_date), 'studentslist.xlsx');
    }

    public function exportIntoCSV(Request $request)
    {
        $start_date = ($request->fromdate) ? $request->fromdate . " 00:00:00" : date("Y-m-d") . " 00:00:00";
        $end_date = ($request->todate) ? $request->todate . " 23:59:59" : date("Y-m-d H:i:s");
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime($end_date));

        $members = User::pluck('id')->toArray();
        return Excel::download(new UserExport($members, $start_date, $end_date), 'studentslist.csv');
    }
}
