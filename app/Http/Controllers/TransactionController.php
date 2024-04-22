<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;

use yajra\Datatables\Datatables;

use App\Models\Seminar;
use App\Models\FlashCard;
use App\Models\Book;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderDetail;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        $packagetype = $request->type == "book" ? 4 : ($request->type == "seminar" ? 2 : ($request->type == "flashcard" ? 3 : 1));
        $package = Package::where("package_for", $packagetype)->get();
        $course = Course::all();
        
        return view('admin.tranaction.index', [
            "type" => $type, 
            'package' => $package, 
            'course' => $course
        ]);
    }

    public function call_data(Request $request)
    {
        $type = $request->type == "book" ? 4 : ($request->type == "seminar" ? 2 : ($request->type == "flashcard" ? 3 : 1));

        $get_data = Order::where("package_for", $type);
        if ($request->name) {
            $get_data = $get_data->whereHas('user', function ($q) use ($request) {
                $q->where('name', "like", "%" . $request->name . "%");
            });
        }
        if ($request->email) {
            $get_data = $get_data->whereHas('user', function ($q) use ($request) {
                $q->where('email', "like", "%" . $request->email . "%");
            });
        }
        if ($request->package) {
            $get_data = $get_data->whereHas('orderDetails', function ($q) use ($request) {
                $q->where('package_id', $request->package);
            });
        }
        if ($request->course) {
            $get_data = $get_data->whereHas('orderDetails', function ($q) use ($request) {
                $q->where('package_for', 1);
                $q->where('particular_record_id', $request->course);
            });
        }
        if ($request->status) {
            $get_data = $get_data->where('payment_status', $request->status);
        }
        if ($request->fromdate) {
            $get_data = $get_data->where('created_at', ">=", $request->fromdate);
        }
        if ($request->todate) {
            $get_data = $get_data->where('created_at', "<=", $request->todate);
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;

        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->orderBy('id', 'desc')->offset($start)->limit($limit)->get();

        $this->exportTransaction($get_data);
        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("user_name", function ($get_data) {
                return $get_data->user->name ?? 'N/A';
            })
            ->editColumn("user_email", function ($get_data) {
                return $get_data->user->email ?? 'N/A';
            })
            ->editColumn("package_name", function ($get_data) {

                $allpackage = array();
                foreach ($get_data->orderDetails as $val) {
                    $allpackage[] = @$val->package->package_title ? $val->package->package_title : 'N/A';
                }
                $title = implode(",", $allpackage);
                return $title ?? 'N/A';
            })->editColumn("amount", function ($get_data) {

                return $get_data->total_amount ?? '0';
            })
            ->editColumn("name", function ($get_data) {

                $allpackage = array();
                foreach ($get_data->orderDetails as $val) {

                    $course_id = ($val->package_for == '1') ? $val->particular_record_id : '';
                    $seminar_id = ($val->package_for == '2') ? $val->particular_record_id : '';
                    $flashcard_id = ($val->package_for == '3') ? $val->particular_record_id : '';
                    $book_id = ($val->package_for == '4') ? $val->particular_record_id : '';
                    if (!empty($course_id)) {
                        $getCat = Course::where('id', $course_id)->first(['id', 'course_name']);
                        $allpackage[] = @$getCat->course_name ? $getCat->course_name : '';
                    }
                    if (!empty($book_id)) {
                        $getCat = Book::where('id', $book_id)->first(['id', 'title']);
                        $allpackage[] = $getCat->title;
                    }
                    if (!empty($flashcard_id)) {
                        $getCat = FlashCard::where('id', $flashcard_id)->first(['id', 'title']);
                        $allpackage[] = $getCat->title;
                    }
                    if (!empty($seminar_id)) {
                        $getCat = Seminar::where('id', $seminar_id)->first(['id', 'title']);
                        $allpackage[] = $getCat->title;
                    }
                }
                $title = implode(",", $allpackage);

                return  $title;
            })
            ->editColumn("enroll_date", function ($get_data) {
                return $get_data->created_at;
            })
            ->editColumn("enroll_end", function ($get_data) {
                $allpackage = array();
                foreach ($get_data->orderDetails as $val) {
                    $allpackage[] = $val->expiry_date;
                }
                $title = implode(",", $allpackage);
                return $title;
            })
            ->editColumn("status", function ($get_data) {
                return $get_data->payment_status == 1 ? "Success" : "failed";
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('transaction/') . '/detail/' . $get_data->id . '" ><i class="fa fa-eye"></i></a>';

                return $cr_form;
            })
            ->rawColumns(['course_name', 'assign', 'status', 'action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function show(Order $order)
    {
        $getDataDetail = OrderDetail::where("order_id", $order->id)->get();

        $page_title = 'package';
        return view('admin.tranaction.show', [
            'page_title' => $page_title,
            'getData' => $order,
            'getDataDetail' => $getDataDetail
        ]);
    }

    public function exportTransaction($data)
    {
        $list = array("User Name", "User Email", "Package Name", "Package Amount", 'Course', 'Enroll Date', 'Expire On', 'Status');
        $transactionFile = public_path("transaction.csv");

        $file = fopen($transactionFile, "w");
        fputcsv($file, $list);
        foreach ($data as $line) {
            $allpackage = array();
            foreach ($line->orderDetails as $val) {
                $allpackage[] = @$val->package && $val->package->package_title ? $val->package->package_title : "N/A";
            }
            $title = implode(",", $allpackage);


            $allpackage = array();
            foreach ($line->orderDetails as $val) {

                $course_id = ($val->package_for == '1') ? $val->particular_record_id : '';
                $seminar_id = ($val->package_for == '2') ? $val->particular_record_id : '';
                $flashcard_id = ($val->package_for == '3') ? $val->particular_record_id : '';
                $book_id = ($val->package_for == '4') ? $val->particular_record_id : '';
                if (!empty($course_id)) {

                    $getCat = Course::where('id', $course_id)->first(['id', 'course_name']);
                    $allpackage[] = @$getCat->course_name ? $getCat->course_name : '';
                }
                if (!empty($book_id)) {
                    $getCat = Book::where('id', $book_id)->first(['id', 'title']);
                    $allpackage[] = $getCat->title;
                }
                if (!empty($flashcard_id)) {
                    $getCat = FlashCard::where('id', $flashcard_id)->first(['id', 'title']);
                    $allpackage[] = $getCat->title;
                }
                if (!empty($seminar_id)) {
                    $getCat = Seminar::where('id', $seminar_id)->first(['id', 'title']);
                    $allpackage[] = $getCat->title;
                }
            }
            $title1 = implode(",", $allpackage);

            $allpackage = array();
            foreach ($line->orderDetails as $val) {
                $allpackage[] = $val->expiry_date;
            }
            $title2 = implode(",", $allpackage);

            $insert = array(@$line->user->name, @$line->user->email, $title, $line->total_amount ?? '0', $title1, $line->created_at, $title2, $line->payment_status == 1 ? "Success" : "failed", date("Y-m-d", strtotime($line->created_at)));
            fputcsv($file, $insert);
        }

        fclose($file);
    }
}
