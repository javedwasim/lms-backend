@extends('layouts.master')

@section('content')  
     
     
    <!-- <nav aria-label="breadcrumb">
       <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
         <li class="breadcrumb-item text-sm">
           <a class="opacity-3 text-dark" href="javascript:;">
              <i class="fa fa-home fa_new_class"></i> 
           </a>
         </li>
         <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Dashboard</li>
       </ol> 
     </nav>  -->  
    <div class="row cbc" style="height: 82vh; background: #e9ced533;">
      <div class="col-lg-12 position-relative z-index-2">
         <div class="card card-plain mb-4">
            <div class="card-body p-3">
               <div class="row">
                  <div class="col-lg-6">
                  <div class="d-flex flex-column h-100">
                     <h2><span class="fa fa-dashboard"></span> Dashboard</h2>
                  </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="row">
            <div class="col-lg-4 col-sm-6">
               <a href="{{ route('users.index') }}">
                  <div class="card  mb-4">
                     <div class="card-body p-3">
                     <div class="row">
                        <div class="col-8">
                           <div class="numbers">
                           <p class="text-sm mb-0 text-capitalize font-weight-bold">Users</p>
                           <h5 class="font-weight-bolder mb-0">
                              <a href="{{url('users')}}">{{ $totalUsers }}</a>
                           </h5>
                           </div>
                        </div>
                        <div class="col-4 text-end">
                           <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                           <i class="fa fa-users text-lg opacity-10" aria-hidden="true"></i>
                           </div>
                        </div>
                     </div>
                     </div>
                  </div>
               </a>
            </div>
            <div class="col-lg-4 col-sm-6">
            <a href="{{ route('course.index') }}">
               <div class="card ">
                  <div class="card-body p-3">
                  <div class="row">
                     <div class="col-8">
                        <div class="numbers">
                        <p class="text-sm mb-0 text-capitalize font-weight-bold">Course</p>
                        <h5 class="font-weight-bolder mb-0">
                           <a href="">{{ $totalCourse }}</a>
                        </h5>
                        </div>
                     </div>
                     <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                        <i class="fa fa-book text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                     </div>
                  </div>
                  </div>
               </div>
            </a>
            </div>
            <div class="col-lg-4 col-sm-6">
            <a href="{{ route('tutorial.index') }}">
               <div class="card  mb-4">
                  <div class="card-body p-3">
                     <div class="row">
                        <div class="col-8">
                           <div class="numbers">
                           <p class="text-sm mb-0 text-capitalize font-weight-bold">Tutorial</p>
                           <h5 class="font-weight-bolder mb-0">
                              <a href="">{{ $totalTutorial }}</a>
                           </h5>
                           </div>
                        </div>
                        <div class="col-4 text-end">
                           <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                           <i class="fa fa-book-open text-lg opacity-10" aria-hidden="true"></i>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
         </a>
            </div>
            <div class="col-lg-4 col-sm-6 mt-sm-0 mt-4">   
            <a href="{{ route('question.index') }}">            
               <div class="card ">
                  <div class="card-body p-3">
                     <div class="row">
                        <div class="col-8">
                           <div class="numbers">
                           <p class="text-sm mb-0 text-capitalize font-weight-bold">Questions</p>
                           <h5 class="font-weight-bolder mb-0">
                              {{ \App\Models\QuestionAnswer::where('status',1)->get()->count() }}
                           </h5>
                           </div>
                        </div>
                        <div class="col-4 text-end">
                           <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                           <i class="fa fa-user-secret text-lg opacity-10" aria-hidden="true"></i>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
</a>
            </div>
            <div class="col-lg-4 col-sm-6 mt-sm-0 mt-4">
            <a href="{{ route('category.index') }}">                          
               <div class="card ">
                  <div class="card-body p-3">
                     <div class="row">
                        <div class="col-8">
                           <div class="numbers">
                           <p class="text-sm mb-0 text-capitalize font-weight-bold">Category</p>
                           <h5 class="font-weight-bolder mb-0">
                              <a href="" >{{ $totalCategory }}</a>
                           </h5>
                           </div>
                        </div>
                        <div class="col-4 text-end">
                           <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                           <i class="fa fa-list text-lg opacity-10" aria-hidden="true"></i>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
</a>
            </div>
           <!--  <div class="col-lg-4 col-sm-6 mt-sm-0 mt-4">       
            <a href="{{ route('package.index') }}">                   
               <div class="card ">
                  <div class="card-body p-3">
                     <div class="row">
                        <div class="col-8">
                           <div class="numbers">
                           <p class="text-sm mb-0 text-capitalize font-weight-bold">Package</p>
                           <h5 class="font-weight-bolder mb-0">
                              {{ $totalPackages }}
                           </h5>
                           </div>
                        </div>
                        <div class="col-4 text-end">
                           <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                           <i class="fa fa-empire text-lg opacity-10" aria-hidden="true"></i>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
</a>
            </div> -->
         </div>
         
      </div>
    </div>
  
    <div id="globe" class="position-absolute end-0 top-0 mt-sm-3 mt-7 me-lg-7 peekaboo" style="width: 422px !important;" >
         <canvas width="700" height="655" class="w-lg-100 h-lg-100 w-75 h-75 me-lg-0 me-n10 mt-lg-5" style="width: 700px; height: 655.594px;"></canvas>
         </div>

@endsection

@section('script')
<script>

   (function() {
           const container = document.getElementById("globe");
           const canvas = container.getElementsByTagName("canvas")[0];
         
           const globeRadius = 100;
           const globeWidth = 4098 / 2;
           const globeHeight = 1968 / 2;
         
           function convertFlatCoordsToSphereCoords(x, y) {
             let latitude = ((x - globeWidth) / globeWidth) * -180;
             let longitude = ((y - globeHeight) / globeHeight) * -90;
             latitude = (latitude * Math.PI) / 180;
             longitude = (longitude * Math.PI) / 180;
             const radius = Math.cos(longitude) * globeRadius;
         
             return {
               x: Math.cos(latitude) * radius,
               y: Math.sin(longitude) * globeRadius,
               z: Math.sin(latitude) * radius
             };
           }
         
           function makeMagic(points) {
             const {
               width,
               height
             } = container.getBoundingClientRect();
         
             // 1. Setup scene
             const scene = new THREE.Scene();
             // 2. Setup camera
             const camera = new THREE.PerspectiveCamera(45, width / height);
             // 3. Setup renderer
             const renderer = new THREE.WebGLRenderer({
               canvas,
               antialias: true
             });
             renderer.setSize(width, height);
             // 4. Add points to canvas
             // - Single geometry to contain all points.
             const mergedGeometry = new THREE.Geometry();
             // - Material that the dots will be made of.
             const pointGeometry = new THREE.SphereGeometry(0.5, 1, 1);
             const pointMaterial = new THREE.MeshBasicMaterial({
               color: "#989db5",
             });
         
             for (let point of points) {
               const {
                 x,
                 y,
                 z
               } = convertFlatCoordsToSphereCoords(
                 point.x,
                 point.y,
                 width,
                 height
               );
         
               if (x && y && z) {
                 pointGeometry.translate(x, y, z);
                 mergedGeometry.merge(pointGeometry);
                 pointGeometry.translate(-x, -y, -z);
               }
             }
         
             const globeShape = new THREE.Mesh(mergedGeometry, pointMaterial);
             scene.add(globeShape);
         
             container.classList.add("peekaboo");
         
             // Setup orbital controls
             camera.orbitControls = new THREE.OrbitControls(camera, canvas);
             camera.orbitControls.enableKeys = false;
             camera.orbitControls.enablePan = false;
             camera.orbitControls.enableZoom = false;
             camera.orbitControls.enableDamping = false;
             camera.orbitControls.enableRotate = true;
             camera.orbitControls.autoRotate = true;
             camera.position.z = -265;
         
             function animate() {
               // orbitControls.autoRotate is enabled so orbitControls.update
               // must be called inside animation loop.
               camera.orbitControls.update();
               requestAnimationFrame(animate);
               renderer.render(scene, camera);
             }
             animate();
           }
         
           function hasWebGL() {
             const gl =
               canvas.getContext("webgl") || canvas.getContext("experimental-webgl");
             if (gl && gl instanceof WebGLRenderingContext) {
               return true;
             } else {
               return false;
             }
           }
         
           function init() {
             if (hasWebGL()) {
               window
               window.fetch("https://raw.githubusercontent.com/creativetimofficial/public-assets/master/soft-ui-dashboard-pro/assets/js/points.json")
                 .then(response => response.json())
                 .then(data => {
                   makeMagic(data.points);
                 });
             }
           }
           init();
         })();
      </script>
@endsection