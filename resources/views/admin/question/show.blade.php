@extends('layouts.master')
@section('content')
    <style>
        .coll2 {
            width: 20%;
        }

        .coll_left {
            width: 79%;
            float: left;
            margin-right: 2px;
        }

        .coll_right {
            width: 20%;
            float: right;
            padding: 15px;
            background: whitesmoke;
        }

        .opt_div {
            padding: 10px;
            background: #e9e9e9 !important;
            border: 1px solid #dbd7d7 !important;
            margin: 7px;
        }
    </style>
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
                                <th>Course</th>
                                <td>
                                    <p style="white-space: pre-wrap;">{{ $my_course_list }}</p>
                                </td>
                            </tr>
                            <tr>
                                <th class="coll2">Category</th>
                                <td>{{ ucwords(@$getData->category_detail->category_name) }}</td>
                            </tr>
                            @if (!empty($getData->sub_category_detail->sub_category_name))
                                <tr>
                                    <th class="coll_2">Sub Category</th>
                                    <td>{{ @$getData->sub_category_detail->sub_category_name }}</td>
                                </tr>
                            @endif
                            @if ($getData->question_type == '1')
                                <tr>
                                    <th class="coll2">Paragraph</th>
                                    <td style="width: 100px;">{!! @$getData->paragraph !!}</td>
                                </tr>
                                <tr>
                                    <th class="coll2">Paragraph Image</th>
                                    <td style="width: 100px;"><?php
                                    if (is_file('public/uploads/' . $getData->paragraph_img)) {
                                        echo '<img src="' . asset('uploads/' . $getData->paragraph_img) . '" width="80" />';
                                    }
                                    ?></td>
                                </tr>
                            @endif
                            <tr>
                                <th class="coll2">Question</th>
                                <td>{!! $getData->question !!}</td>
                            </tr>
                            <tr>
                                <th class="coll2">Question Image</th>
                                <td style="width: 100px;"><?php
                                if (is_file('public/uploads/' . $getData->question_img)) {
                                    echo '<img src="' . asset('uploads/' . $getData->question_img) . '" width="80" />';
                                }
                                ?></td>
                            </tr>
                            @if ($getData->question_type == '1' || $getData->question_type == '5')
                                <tr>
                                    <th class="coll2">Option A.</th>
                                    <td>{!! $getData->option_a !!}</td>
                                </tr>
                                <tr>
                                    <th class="coll2">Option A Image</th>
                                    <td style="width: 100px;"><?php
                                    if (is_file('public/uploads/' . $getData->option_a_img)) {
                                        echo '<img src="' . asset('uploads/' . $getData->option_a_img) . '" width="80" />';
                                    }
                                    ?></td>
                                </tr>
                                <tr>
                                    <th class="coll2">Option B.</th>
                                    <td>{!! $getData->option_b !!}</td>
                                </tr>
                                <tr>
                                    <th class="coll2">Option B Image</th>
                                    <td style="width: 100px;"><?php
                                    if (is_file('public/uploads/' . $getData->option_b_img)) {
                                        echo '<img src="' . asset('uploads/' . $getData->option_b_img) . '" width="80" />';
                                    }
                                    ?></td>
                                </tr>
                                @if (!empty($getData->option_c))
                                    <tr>
                                        <th class="coll2">Option C.</th>
                                        <td>{!! $getData->option_c !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option C Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_c_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_c_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_d))
                                    <tr>
                                        <th class="coll2">Option D.</th>
                                        <td>{!! $getData->option_d !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option D Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_d_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_d_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_e))
                                    <tr>
                                        <th class="coll2">Option E.</th>
                                        <td>{!! $getData->option_e !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option E Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_e_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_e_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_f))
                                    <tr>
                                        <th class="coll2">Option F.</th>
                                        <td>{!! $getData->option_f !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option F Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_f_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_f_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_g))
                                    <tr>
                                        <th class="coll2">Option F.</th>
                                        <td>{!! $getData->option_g !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option F Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_g_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_g_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_h))
                                    <tr>
                                        <th class="coll2">Option F.</th>
                                        <td>{!! $getData->option_h !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option F Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_h_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_h_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_i))
                                    <tr>
                                        <th class="coll2">Option F.</th>
                                        <td>{!! $getData->option_i !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option F Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_i_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_i_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                @if (!empty($getData->option_j))
                                    <tr>
                                        <th class="coll2">Option F.</th>
                                        <td>{!! $getData->option_j !!}</td>
                                    </tr>
                                    <tr>
                                        <th class="coll2">Option F Image</th>
                                        <td style="width: 100px;"><?php
                                        if (is_file('public/uploads/' . $getData->option_j_img)) {
                                            echo '<img src="' . asset('uploads/' . $getData->option_j_img) . '" width="80" />';
                                        }
                                        ?></td>
                                    </tr>
                                @endif
                                
                                <tr>
                                    <th class="coll2">Correct Answer</th>
                                    <td>{{ $getData->correct_answer }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Admin Tags</th>
                                <td>
                                    <p style="white-space: pre-wrap;">{{ $getData->question_tags }}</p>
                                </td>
                            </tr>
                        </table>
                        @if ($getData->question_type == '2' || $getData->question_type == '3' || $getData->question_type == '4')
                            <hr />
                            <div class="coll_left">
                                <table class="table table-bordered table-striped table-actions">
                                    <tr>
                                        <th>Options</th>
                                        <th>Correct Answer</th>
                                    </tr>
                                    @if ($queOption->count() > 0)
                                        @foreach ($queOption as $opt_val)
                                            <tr>
                                                <td style="width:20%">{{ $opt_val->option_name }}</td>
                                                <td>{{ $opt_val->correct_option_answer }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </table>
                            </div>
                            <div class="coll_right">
                                <div class="table table-bordered table-striped table-actions">
                                    @if ($option_answer_type->count() > 0)
                                        @foreach ($option_answer_type as $opt_ans_type_val)
                                            <div class="opt_div">{{ $opt_ans_type_val->answer_type_name }}</div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif

                        <table class="table table-bordered table-striped table-actions">
                            <tr>
                                <th style="width: 10%;">Explanation :</th>
                                <td>
                                    {!! $getData->explanation !!}
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 10%;">Video Explanation :</th>
                                <td>
                                    @if ($getData->explanation_video)
                                        <video style="width:425px;" controls>
                                            <source src="{{ url('uploads/' . $getData->explanation_video) }}">
                                        </video>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
