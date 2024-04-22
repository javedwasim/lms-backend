@extends('layouts.app_front')
@section('content')
   <style>
      .cat_main{
         overflow: hidden;
         width:90px;
         display: -webkit-box;
         -webkit-line-clamp: 3;
         -webkit-box-orient: vertical;
      }
      .cat_desc{
         overflow: hidden;
         width:100px;
         display: -webkit-box;
         -webkit-line-clamp: 3;
         -webkit-box-orient: vertical;
      }
   </style>
    <section id="hero" class="d-flex align-items-center padng-150">
       <div class="container-fluid">
          <div class="row ms-5">
             <div class="col-md-12 d-flex flex-column justify-content-center pt-4 pt-lg-0 order-2 order-lg-1" data-aos="fade-up" data-aos-delay="200">
                <h1>Buy or Rent House</h1>
                <form id="validate" action="{{ url('property_list') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" >
                  @csrf

                  <input type="hidden" required class="form-control" name="longitude" id="location_lat" value="{{ @$_COOKIE['current_longitude'] }}" placeholder="Longitude">
                  <input type="hidden" required class="form-control" name="latitude" id="location_lang"  value="{{ @$_COOKIE['current_latitude'] }}" placeholder="Latitude">
 
                  <input type="text" required class="form-control lcn-icn" name="location" id="location" placeholder="Where are you going?" style="border:none !important">

                   <input type="submit" value="Discover">
                </form>
             </div>
          </div>
       </div>
    </section> 

    <main id="main"> 
       <section id="services" class="services padng-150">
          <div class="container-fluid" data-aos="fade-up">
             <div class="section-title">
                <h2>Categories</h2>
                <p>It is a long established fact that a reader will be distracted by the readable content of a page<br>when looking at its layout.</p>
             </div>
             <div class="Categories owl-carousel owl-theme">
               @foreach($GetSubCategory as $cat_dt) 
                  <form action="{{ url('property_list')}}" method="POST" id="pro_cat_form_{{$cat_dt->id}}" class="property_by_category" data-id="{{$cat_dt->id}}" > 
                     @csrf 
                        <div class="testimonial-block">
                           <div class="icon-box"  >  
                                 <div class="icon">
                                    <img src="{{ url('uploads/'.$cat_dt->sub_category_image) }}" class="img-fluid cat_css" alt="" />
                                 </div>
                                 @php
                                    $class_nm =$cat_dt->sub_category_name;
                                    $class_desc =$cat_dt->description;
                                 @endphp
                                 <h4>{{ strlen($class_nm) > 7 ? substr($class_nm,0,7)."..." : $class_nm  }}</h4>
                                 <p style="max-height: 45px;" >{{ strlen($class_desc) > 50 ? substr($class_desc,0,50)."..." : $class_desc  }}</p> 

                           </div>
                        </div>
                     <input type="hidden" id="property_sub_category_id" name="property_sub_category_id"  value="{{$cat_dt->id}}" />
                  </form>
               @endforeach
             </div>
          </div>
       </section>
       <section class="team padng-150">
          <div class="container-fluid" data-aos="fade-up">
             <div class="section-title">
                <h2>Best Offers</h2>
             </div>
             <div class="row">
                  @foreach($get_popular_property as $best_prop_key => $best_prop_dt)  
                     <div class="col-lg-6">
                      <div class="member d-flex align-items-start" data-aos="zoom-in" data-aos-delay="100">
                         <div class="pic">
                            <div class="like-hrt">
                              <img id="favourite_heart_{{ $best_prop_dt->property_id }}" src="{{ ($best_prop_dt->is_liked>0) ? asset('front/assets/img/heart-Icon-liked.svg') : asset('front/assets/img/heart-Icon.svg') }}" alt="" data-pid="{{ $best_prop_dt->property_id }}" data-id="property_form" data-form_action="add_to_favourite" class="img-fluid save_to_favourite_form" > 
                           </div>
                              @if(count($best_prop_dt->property_images)>0)
                                 <img src="{{ url('uploads/'.$best_prop_dt->property_images[0]->image_name) }}" class="img-fluid prop_css" alt="">
                              @endif
                         </div>
                         <div class="member-info">
                            <div class="row">
                               <div class="col-md-6">
                                  <!-- <div class="tag-fml">Family</div> -->
                               </div>
                               <div class="col-md-6 text-end">
                                  <div class="str-rat">   
                                    <img src="{{ asset('front/assets/img/star-y.svg') }}" class="img-fluid" alt="">
                                    {{ $best_prop_dt->total_views }}</div>
                               </div>
                            </div>
                            <h4>{{ ucwords($best_prop_dt->property_name) }}</h4>
                            <span><img src="{{ asset('front/assets/img/location.svg') }}" class="img-fluid" alt=""> {{ $best_prop_dt->location }}</span>
                            <p>{{ config('app.currency_symbol').$best_prop_dt->property_price }}</p>
                            <div class="row featrs">
                               <div class="col-md-4"><img src="{{ asset('front/assets/img/bed.svg') }}" class="img-fluid" alt=""><br>Beds({{ ($best_prop_dt->no_of_bedroom>0) ? $best_prop_dt->no_of_bedroom : '0' }})</div>
                               <div class="col-md-4"><img src="{{ asset('front/assets/img/bath-tub.svg') }}" class="img-fluid" alt=""><br>Bath({{ ($best_prop_dt->no_of_bathroom>0) ? $best_prop_dt->no_of_bathroom : '0' }})</div> 
                               <div class="col-md-4"><img src="{{ asset('front/assets/img/square-layout.svg') }}" class="img-fluid" alt=""><br>sqft ({{ ($best_prop_dt->area_square_meter>0) ? $best_prop_dt->area_square_meter : '0' }})</div>
                            </div>
                            <a class="cta-btn align-middle" href="{{ url('property_detail').'/'.$best_prop_dt->property_id}}">View Details</a>
                         </div>
                      </div>
                     </div>
                  @endforeach
             </div>
          </div>
       </section>

       <section id="team" class="team padng-150">
          <div class="container-fluid" data-aos="fade-up">
             <div class="section-title">
                <h2>Nearby Properties</h2>
             </div>
             <div class="row">
                  @foreach($get_property as $prop_key => $prop_dt)  
                     <div class="col-lg-6">
                      <div class="member d-flex align-items-start" data-aos="zoom-in" data-aos-delay="100">
                         <div class="pic">
                            <div class="like-hrt">
                              <img id="favourite_heart_{{ $prop_dt->property_id }}" src="{{ ($prop_dt->is_liked>0) ? asset('front/assets/img/heart-Icon-liked.svg') : asset('front/assets/img/heart-Icon.svg') }}" alt="" data-pid="{{ $prop_dt->property_id }}" data-id="property_form" data-form_action="add_to_favourite" class="img-fluid save_to_favourite_form" > 
                           </div>
                              @if(count($prop_dt->property_images)>0)
                                 <img src="{{ url('uploads/'.$prop_dt->property_images[0]->image_name) }}" class="img-fluid prop_css" alt="">
                              @endif
                         </div>
                         <div class="member-info">
                            <div class="row">
                               <div class="col-md-6">
                                  <!-- <div class="tag-fml">Family</div> -->
                               </div>
                               <div class="col-md-6 text-end">
                                  <div class="str-rat">  
                                    
                                    <img src="{{ asset('front/assets/img/star-y.svg') }}" class="img-fluid" alt="">
                                    {{ $prop_dt->total_views }}</div>
                               </div>
                            </div>
                            <h4>{{ ucwords($prop_dt->property_name) }}</h4>
                            <span><img src="{{ asset('front/assets/img/location.svg') }}" class="img-fluid" alt=""> {{ $prop_dt->location }}</span>
                            <p>{{ config('app.currency_symbol').$prop_dt->property_price }}</p>
                            <div class="row featrs">
                               <div class="col-md-4"><img src="{{ asset('front/assets/img/bed.svg') }}" class="img-fluid" alt=""><br>Beds({{ ($prop_dt->no_of_bedroom>0) ? $prop_dt->no_of_bedroom : '0' }})</div>
                               <div class="col-md-4"><img src="{{ asset('front/assets/img/bath-tub.svg') }}" class="img-fluid" alt=""><br>Bath({{ ($prop_dt->no_of_bathroom>0) ? $prop_dt->no_of_bathroom : '0' }})</div> 
                               <div class="col-md-4"><img src="{{ asset('front/assets/img/square-layout.svg') }}" class="img-fluid" alt=""><br>sqft ({{ ($prop_dt->area_square_meter>0) ? $prop_dt->area_square_meter : '0' }})</div>
                            </div>
                            <a class="cta-btn align-middle" href="{{ url('property_detail').'/'.$prop_dt->property_id}}">View Details</a>
                         </div>
                      </div>
                     </div>
                  @endforeach
             </div>
          </div>
       </section>
       <section id="cta" class="cta">
          <div class="container-fluid" data-aos="zoom-in">
             <div class="row">
                <div class="col-md-12 text-center text-lg-start">
                   <h3>Questions<br>about hosting?</h3>
                   <button class="cta-btn align-middle check_user_is_logged_in" >Post Property</button>
                </div>
             </div>
          </div>
       </section>
       <section id="pricing" class="pricing padng-150">
          <div class="container-fluid" data-aos="fade-up">
             <div class="section-title">
                <h2>Our Testimonials</h2>
                <p>It is a long established fact that a reader will be distracted by the readable content of a page<br> when looking at its layout. </p>
             </div>
             <div class="row">
                <div class="col-md-5">
                   <img src="{{ asset('front/assets/img/test-l.png') }}" alt="" class="img-fluid">
                </div>
                <div class="col-md-7">
                   <div class="testimonial-section">
                      <div class="large-container">
                         <div class="testimonial-carousel owl-carousel owl-theme">
                            <div class="testimonial-block">
                               <div class="inner-box">
                                  <div class="thumb"><img src="{{ asset('front/assets/img/thumb-1.png') }}" alt=""></div>
                                  <div class="text">
                                     <div class="info-box">
                                        <h4 class="name">Kevin Yang</h4>
                                        <span class="designation">Product Management Instapage</span>
                                     </div>
                                     It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. 
                                     <img style="width: 70px;" src="{{ asset('front/assets/img/log.png') }}" alt="" class="img-fluid mt-4">
                                  </div>
                               </div>
                            </div>
                            <div class="testimonial-block">
                               <div class="inner-box">
                                  <div class="thumb"><img src="{{ asset('front/assets/img/thumb-1.png') }}" alt=""></div>
                                  <div class="text">
                                     <div class="info-box">
                                        <h4 class="name">Kevin Yang</h4>
                                        <span class="designation">Product Management Instapage</span>
                                     </div>
                                     It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. 
                                     <img style="width: 70px;" src="{{ asset('front/assets/img/log.png') }}" alt="" class="img-fluid mt-4">
                                  </div>
                               </div>
                            </div>
                         </div>
                      </div>
                   </div>
                </div>
             </div>
          </div>
       </section>
    </main>
@endsection

@section('script')   
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.1.1/owl.carousel.min.js" ></script>

<script>
google.maps.event.addDomListener(window, 'load', initialize);
function initialize() {
var input = document.getElementById('location');
var autocomplete = new google.maps.places.Autocomplete(input);
autocomplete.addListener('place_changed', function () {
var place = autocomplete.getPlace();
// place variable will have all the information you are looking for.

  document.getElementById("location_lat").value = place.geometry['location'].lat();
  document.getElementById("location_lang").value = place.geometry['location'].lng();
});
}
</script>

 <script type="text/javascript">
    (function($) {
  
  "use strict";
  
    // Testimonial Carousel
  if ($('.testimonial-carousel').length) {
    $('.testimonial-carousel').owlCarousel({
      animateOut: 'slideOutDown',
        animateIn: 'zoomIn',
      loop:true,
      margin:0,
      nav:true,
      smartSpeed: 300,
      autoplay: 7000,
      navText: [ '<span class="arrow-left"></span>', '<span class="arrow-right"></span>' ],
      responsive:{
        0:{
          items:1
        },
        600:{
          items:1
        },
        800:{
          items:1
        },
        1024:{
          items:1
        }
      }
    });     
  }

  // Categories Carousel
  if ($('.Categories').length) {
    $('.Categories').owlCarousel({
      animateOut: 'slideOutDown',
        animateIn: 'zoomIn',
      loop:true,
      margin:0,
      nav:true,
      smartSpeed: 300,
      autoplay: 7000,
      navText: [ '<span class="arrow-left"></span>', '<span class="arrow-right"></span>' ],
      responsive:{
        0:{
          items:1
        },
        600:{
          items:1
        },
        800:{
          items:1
        },
        1024:{
          items:4
        }
      }
    });     
  }
  
})(window.jQuery);
  </script>
@endsection
