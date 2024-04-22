@extends('layouts.master')
@section('content')
    <div class="card">
        <div class="card-header pb-0">
            <h5>View
            </h5>
        </div>
        <div class="card-body">
            <div class="row fil_ters">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-actions">

                            <tr>
                                <th>User Name</th>
                                <td>{{ @$getData->user->name }}
                                </td>
                            </tr>
                            <tr>
                                <th>User Email</th>
                                <td>{{ @$getData->user->email }}
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ @$getData->payment_status == 1 ? 'Success' : 'failed' }}
                                </td>
                            </tr>

                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Package Detail<code>*</code></label>

                    </div>
                </div>
                <div class="col-md-12">
                    <table class="table table-bordered" id="dynamicTable">
                        <tr>
                            <th>Package Name</th>
                            <th>Course Name</th>
                            <th>Enroll Date</th>
                            <th>Enroll Expire</th>
                        </tr>
                        <?php
                        use App\Models\Seminar;
use App\Models\FlashCard;
use App\Models\Book;
use App\Models\Course;
                            if(!empty($getDataDetail))
                            {
                                foreach($getDataDetail as $key =>$val)
                                {
                                    $course_id = ($val->package_for=='1') ? $val->particular_record_id : '';
                                    $seminar_id = ($val->package_for=='2') ? $val->particular_record_id : '';
                                    $flashcard_id = ($val->package_for=='3') ? $val->particular_record_id : '';
                                    $book_id = ($val->package_for=='4') ? $val->particular_record_id : '';
                                    if(!empty($course_id))
                                    {
                                        $getCat = Course::where('id',$course_id)->first(['id','course_name']);
                                        $allpackage=$getCat->course_name;
                                    }
                                    if(!empty($book_id))
                                    {
                                        $getCat = Book::where('id',$book_id)->first(['id','title']);
                                        $allpackage=$getCat->title;
                                    }
                                    if(!empty($flashcard_id))
                                    {
                                        $getCat = FlashCard::where('id',$flashcard_id)->first(['id','title']);
                                        $allpackage=$getCat->title;
                                    }
                                    if(!empty($seminar_id))
                                    {
                                        $getCat = Seminar::where('id',$seminar_id)->first(['id','title']);
                                        $allpackage=$getCat->title;
                                    }
                    ?>
                        <tr>
                            <td>
                                {{ $val->package->package_title }}
                            </td>
                            <td>
                                {{ $allpackage }}
                            </td>
                            <td>
                                {{ $val->created_at }}
                            </td>
                            <td>
                                {{ $val->expiry_date }}
                            </td>


                        </tr>
                        <?php
                                }
                            }
                             ?>

                    </table>
                </div>


            </div>
        </div>
    @endsection
