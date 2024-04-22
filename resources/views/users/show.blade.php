@extends('layouts.master')
<style type="text/css">
    label {
        font-size: 15px;
        line-height: 34px;
    }
</style>
@section('content')

<div class="card">
    <div class="card-header pb-0">
        <h5>View {{ $page_title }}
            @if($page_title=='user')
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('users.index') }}"> Back</a>
            @endif
        </h5>
    </div>

    <div class="card-body">
        <div class="row fil_ters">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-bordered table-actions">
                        <tr>
                            <th>User Name</th>
                            <td>{{ ucwords($user->name) }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <!-- <tr>
                                <th>Phone</th>
                                <td>{{ ($user->country_code) ? $user->country_code.'-'.$user->phone : $user->phone }}</td>
                            </tr> -->
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-bordered table-actions">
                        <tr>
                            <th>Status</th>
                            <td>
                                <?= !empty($user->status) ? 'Active' : 'Inactive' ?>

                            </td>
                        </tr>
                        <tr>
                            <th>Profile Image</th>
                            <td>
                                @if($user->profile_photo_path)
                                <a href="{{ url('uploads/'.$user->profile_photo_path) }}" target="_blank">
                                    <img src="{{ url('uploads/'.$user->profile_photo_path) }}" style="width: 50px; padding: 8px; " />
                                </a>
                                @endif
                            </td>
                        </tr>

                    </table>
                </div>


            </div>

        </div>
    </div>

</div>


<div class="card mt-3">
    <div class="card-header pb-0">
        <h5>View Subscription
        </h5>
    </div>


    <div class="card-body">
        <div class="row fil_ters">

            <div class="table-responsive">
                <table class="table table-bordered table-actions">
                    <tr>
                        <th>Course Name</th>
                        <th>Package Name</th>
                        <th>Purchase Date</th>
                        <th>Expire Date</th>
                        <th>Days Left</th>

                    </tr>
                    @foreach(@$subscription as $val)
                    <tr>
                        <td>{{$val->course_name}}</td>
                        <td>{{$val->package_title}}</td>
                        <td>{{$val->created_at}}</td>
                        <td>{{$val->expiry_date}}</td>
                        <td>
                            <?php

                            $date = \Carbon\Carbon::parse($val->expiry_date);
                            $now = \Carbon\Carbon::now();

                            $diff = $date->diffInDays($now);
                            echo $diff;
                            ?></td>

                    </tr>
                    @endforeach
                </table>
            </div>
        </div>


    </div>
</div>

@endsection