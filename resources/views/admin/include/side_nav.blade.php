@php
$auth_id = \Auth::id();
$linkk = \Request::segment(1);
@endphp

<style>
    .fa_new_class {
        color: black !important;
        font-size: 12px !important;
    }

    #sidenav-main::-webkit-scrollbar {
        width: 2px;
    }

    #sidenav-main::-webkit-scrollbar-thumb {
        background-color: blue;
        /* color of the scroll thumb */
        border-radius: 20px;
        /* roundness of the scroll thumb */
        border: 3px solid orange;
        /* creates padding around scroll thumb */
    }

    .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link .nav-link-text,
    .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link .sidenav-mini-icon,
    .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link .sidenav-normal,
    .navbar-vertical.navbar-expand-xs .navbar-nav .nav-link i {

        color: #fff;
    }

    .navbar-vertical .navbar-nav .nav-item .collapse .nav .nav-item .nav-link:before,
    .navbar-vertical .navbar-nav .nav-item .collapsing .nav .nav-item .nav-link:before {

        background: #fff;
    }

    .navbar-vertical .navbar-nav .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] {
        color: #fff;

    }

    .navbar-vertical .navbar-nav>.nav-item .nav-link.active span {
        color: #000
    }
</style>
<aside class="my-3 border-0 sidenav navbar navbar-vertical navbar-expand-xs border-radius-xl fixed-start ms-3 ps ps--active-y" id="sidenav-main" style="overflow: auto !important">
    <div class="sidenav-header">
        <i class="top-0 p-3 cursor-pointer fas fa-times text-secondary opacity-5 position-absolute end-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>

        <a class="m-0 navbar-brand" href="{{ route('dashboard') }}">
            <img src="{{ url('img/logo.png') }}" class="navbar-brand-img h-100" alt="main_logo" style="width: 90%;">
            <!-- <span class="ms-1 font-weight-bold">{{ config('app.name') }}</span> -->
        </a>
    </div>
    <hr class="mt-0 horizontal dark">
    <div class="w-auto h-auto collapse navbar-collapse h-100 ps" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'admin' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center me-2">
                        <i class="fa fa-dashboard fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'users' ? 'active' : '' }} " href="{{ route('users.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-users fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'category' ? 'active' : '' }} " href="{{ route('category.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-snowflake-o fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Category</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'sub_category' ? 'active' : '' }} " href="{{ route('sub_category.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-list-alt fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Sub Category</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'ucatscore' ? 'active' : '' }}" href="{{ URL::to('/ucatscore') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-sticky-note fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage Score</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'tutoring' ? 'active' : '' }} " href="{{ route('updateUrl') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-snowflake-o fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Tutoring</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'tip' ? 'active' : '' }}" href="{{ route('tip.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-calendar fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Tip</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'tutorial' ? 'active' : '' }}" href="{{ route('tutorial.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-book-open fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Tutorials </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'question' ? 'active' : '' }}" href="{{ route('question.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-bank fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Questions Bank</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'question-report' ? 'active' : '' }}" href="{{ url('question-report/') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-question fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Reported Question </span>
                </a>
            </li>

            <li class="nav-item">
                <!--  <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ route('course.index') }}">
                   <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                      <i class="fa fa-book fa_new_class"></i>
                   </div>
                   <span class="nav-link-text ms-1">Course</span>
                </a> -->
                <a data-bs-toggle="collapse" href="#dashboardsExamples" class="nav-link " aria-controls="dashboardsExamples" role="button" aria-expanded="false">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center me-2">
                        <i class="fa fa-book fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage Course</span>
                </a>
                <div class="collapse " id="dashboardsExamples">
                    <ul class="nav ms-4 ps-3">
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('/coursetype') }}">

                                <span class="nav-link-text ms-1">Course Types</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ route('course.index') }}">

                                <span class="nav-link-text ms-1">Course List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ route('package.index') }}?type=course">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">Package</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('/transaction/course') }}">

                                <span class="nav-link-text ms-1">Transactions</span>
                            </a>
                        </li>
                        <!-- <li class="nav-item ">
                         <a class="nav-link " href="./dashboards/automotive.html">

                            <span class="sidenav-normal"> Automotive </span>
                         </a>
                      </li>
                      <li class="nav-item ">
                         <a class="nav-link " href="./dashboards/smart-home.html">
                            <span class="sidenav-mini-icon"> S </span>
                            <span class="sidenav-normal"> Smart Home </span>
                         </a>
                      </li>
                      <li class="nav-item ">
                         <a class="nav-link " data-bs-toggle="collapse" aria-expanded="false" href="#vrExamples">
                            <span class="sidenav-mini-icon"> V </span>
                            <span class="sidenav-normal"> Virtual Reality <b class="caret"></b></span>
                         </a>
                         <div class="collapse " id="vrExamples">
                            <ul class="nav nav-sm flex-column">
                               <li class="nav-item">
                                  <a class="nav-link " href="./dashboards/vr/vr-default.html">
                                     <span class="text-xs sidenav-mini-icon"> V </span>
                                     <span class="sidenav-normal"> VR Default </span>
                                  </a>
                               </li>
                               <li class="nav-item">
                                  <a class="nav-link " href="./dashboards/vr/vr-info.html">
                                     <span class="text-xs sidenav-mini-icon"> V </span>
                                     <span class="sidenav-normal"> VR Info </span>
                                  </a>
                               </li>
                            </ul>
                         </div>
                      </li>
                      <li class="nav-item ">
                         <a class="nav-link " href="./dashboards/crm.html">
                            <span class="sidenav-mini-icon"> C </span>
                            <span class="sidenav-normal"> CRM </span>
                         </a>
                      </li> -->
                    </ul>
                </div>

            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'mocktest' ? 'active' : '' }} " href="{{ url('mocktest/index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-snowflake-o fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage Mocktest</span>
                </a>
            </li>


            <!--
    <li class="nav-item">
                   <a class="nav-link {{ $linkk == 'paragraph' ? 'active' : '' }}" href="{{ route('paragraph.index') }}">
                      <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                         <i class="fa fa-file-text fa_new_class"></i>
                      </div>
                      <span class="nav-link-text ms-1">Paragraph</span>
                   </a>
                </li>
 -->




            <li class="nav-item">

                <a data-bs-toggle="collapse" href="#seminar" class="nav-link " aria-controls="seminar" role="button" aria-expanded="false">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center me-2">
                        <i class="fa fa-simplybuilt fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage Seminars</span>
                </a>
                <div class="collapse " id="seminar">
                    <ul class="nav ms-4 ps-3">
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('seminar') }}">

                                <span class="nav-link-text ms-1">Seminar List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ url('seminar/cms') }}">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">CMS</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('testimonial?type=seminar') }}">

                                <span class="nav-link-text ms-1">Testimonial List</span>
                            </a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ route('package.index') }}?type=seminar">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">Package</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('/transaction/seminar') }}">

                                <span class="nav-link-text ms-1">Transactions</span>
                            </a>
                        </li>
                    </ul>
                </div>

            </li>
            <li class="nav-item">

                <a data-bs-toggle="collapse" href="#book" class="nav-link " aria-controls="book" role="button" aria-expanded="false">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center me-2">
                        <i class="fa fa-sticky-note fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage Books</span>
                </a>
                <div class="collapse " id="book">
                    <ul class="nav ms-4 ps-3">
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('book') }}">

                                <span class="nav-link-text ms-1">Book List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ url('book/cms') }}">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">CMS</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('testimonial?type=book') }}">

                                <span class="nav-link-text ms-1">Testimonial List</span>
                            </a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ route('package.index') }}?type=book">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">Package</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('/transaction/book') }}">

                                <span class="nav-link-text ms-1">Transactions</span>
                            </a>
                        </li>

                    </ul>
                </div>

            </li>
            <li class="nav-item">

                <a data-bs-toggle="collapse" href="#flashcard" class="nav-link " aria-controls="flashcard" role="button" aria-expanded="false">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center me-2">
                        <i class="fa fa-flash fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage FlashCards</span>
                </a>
                <div class="collapse " id="flashcard">
                    <ul class="nav ms-4 ps-3">

                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('flashcard') }}">

                                <span class="nav-link-text ms-1">FlashCard List</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ url('flashcard/cms') }}">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">CMS</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('testimonial?type=flashcard') }}">

                                <span class="nav-link-text ms-1">Testimonial List</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'package' ? 'active' : '' }}" href="{{ route('package.index') }}?type=flashcard">
                                <!-- <span class="sidenav-mini-icon"> P</span> -->
                                <span class="nav-link-text ms-1">Package</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $linkk == 'course' ? 'active' : '' }}" href="{{ url('/transaction/flashcard') }}">

                                <span class="nav-link-text ms-1">Transactions</span>
                            </a>
                        </li>

                    </ul>
                </div>

            </li>



            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'personal_support' ? 'active' : '' }}" href="{{ route('personal_support.index') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-support fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Personal Support</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'progress_bar_setting' ? 'active' : '' }}" href="{{ url('progress_bar_setting') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-cog fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Setting</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'question-list' ? 'active' : '' }}" href="{{ URL::to('/report_issue') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-sticky-note fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Report Issue</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $linkk == 'question-notes' ? 'active' : '' }}" href="{{ URL::to('/question_notes') }}">
                    <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md me-2 d-flex align-items-center justify-content-center">
                        <i class="fa fa-sticky-note fa_new_class"></i>
                    </div>
                    <span class="nav-link-text ms-1">Question Notes</span>
                </a>
            </li>
            {{-- <li class="nav-item">

               <a data-bs-toggle="collapse" href="#mocktest" class="nav-link " aria-controls="mocktest" role="button" aria-expanded="false">
                  <div class="text-center bg-white shadow icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center me-2">
                     <i class="fa fa-book fa_new_class"></i>
                  </div>
                  <span class="nav-link-text ms-1">Mock Test</span>
               </a>
               <div class="collapse " id="mocktest">
                  <ul class="nav ms-4 ps-3">

                     <li class="nav-item">
                        <a class="nav-link {{ ($linkk=='mocktest') ? 'active' : ''}}" href="{{ url('mocktest/index') }}">

            <span class="nav-link-text ms-1">Mock Test List</span>
            </a>
            </li>
        </ul>
    </div>

    </li> --}}



    </ul>
</aside>
