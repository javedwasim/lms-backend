@extends('layouts.master')
@section('content')
    <div class="card">
        <div class="card-header pb-0">
            <h5>View {{ $page_title }}
                @if ($page_title == 'user')
                    <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('event.index') }}"> Back</a>
                @endif
            </h5>
        </div>
        <div class="card-body">
            <div class="row fil_ters">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-actions">
                            <tr>
                                <th>{{ @$getData->package_for == '2' ? 'Seminar' : (@$getData->package_for == '4' ? 'Book' : (@$getData->package_for == '3' ? 'FlashCard' : 'Course')) }}
                                    Name</th>
                                <td>{{ @$getData->package_for == '1' ? ucwords($getCourse->course_name) : ucwords($getCourse->title) }}
                                </td>
                            </tr>
                            <tr>
                                <th>Package Title</th>
                                <td>{{ $getData->package_title }}</td>
                            </tr>
                            <tr>
                                <th>Package For Month</th>
                                <td>{{ $getData->package_for }}</td>
                            </tr>
                            <tr>
                                <th>Package Expire Date</th>
                                <td>{{ $getData->expire_date }}</td>
                            </tr>
                            <tr>
                                <th>Package Type</th>
                                <td>{{ $getData->packagetype }}</td>
                            </tr>
                            <tr>
                                <th>Price</th>
                                <td>{{ $getData->price }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{!! $getData->status == '1'
                                    ? '<span class="label label-success sdd">Active</span>'
                                    : '<span class="label label-warning  sdd">InActive</span>' !!}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td style="white-space: pre-wrap;">{!! $getData->description !!}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Package Features<code>*</code></label>

                    </div>
                </div>
                <div class="col-md-12">
                    <table class="table table-bordered" id="dynamicTable">
                        <tr>
                            <th>Value</th>
                            <th>Icon Color Status</th>
                        </tr>
                        <?php
                            if(!empty($multipledata))
                            {
                                foreach($multipledata as $key =>$val)
                                {
                                    ?>
                        <tr>
                            <td><input type="text" name="multi_pack_value[]" placeholder="Enter your Name"
                                    class="form-control" readonly value="{{ $val->multi_pack_value }}" required /></td>
                            <td>
                                <select name="multi_pack_status[]" id="" class="form-control" required disabled>
                                    <option value="1" <?= $val->multi_pack_status == 1 ? 'selected' : '' ?>>Active
                                    </option>
                                    <option value="2" <?= $val->multi_pack_status == 2 ? 'selected' : '' ?>>Inactive
                                    </option>
                                </select>
                            </td>


                        </tr>
                        <?php
                                }
                            }
                             ?>

                    </table>
                </div>
                @if ($getData->package_for == '1')
                    <div class="row">
                        <h5>Assigned Questions</h5>
                        <div class="col-lg-12" style="max-height:400px; overflow: auto;">
                            <table class="table table-bordered table-striped table-actions" id="question_div">
                                @if (count($getQuestion) > 0)
                                    @foreach ($getQuestion as $qkey => $QueDt)
                                        <tr>
                                            <th>
                                                {{ $qkey + 1 }}
                                            </th>
                                            <td>{!! $QueDt->question_name !!}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>No record exist</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <h5>Assigned Tutorial</h5>
                        <div class="col-lg-12" style="max-height:400px; overflow: auto;">
                            <table class="table table-bordered table-striped table-actions" id="tutorial_div">
                                @if (count($getTutorial) > 0)
                                    @foreach ($getTutorial as $tkey => $TutDt)
                                        <tr>
                                            <th>
                                                {{ $tkey + 1 }}
                                            </th>
                                            <td>{{ $TutDt->chapter_name }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>No record exist</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endsection
