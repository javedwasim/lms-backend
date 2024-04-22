
<nav class="navbar navbar-main navbar-expand-lg position-sticky mt-4 top-1 px-0 mx-4 border-radius-xl z-index-sticky shadow-none" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1 px-3">
        
        <div class="sidenav-toggler sidenav-toggler-inner d-xl-block d-none ">
          <a href="javascript:;" class="nav-link p-0 text-body">
            <div class="sidenav-toggler-inner">
              <i class="fa fa-align-justify fa_new_class"></i>
            </div>
          </a>
        </div>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
          <div class="ms-md-auto pe-md-3 d-flex align-items-center">
            
          </div>
          <ul class="navbar-nav  justify-content-end">
            <li class="nav-item d-flex align-items-center">
              <a href="" class="nav-link font-weight-bold px-0 text-body" target="_blank">
                <i class="fa fa-user fa_new_class"></i>
                <span class="d-sm-inline d-none">{{ ucwords(\Auth::user()->name)}}</span>
              </a>
            </li> 
            <li class="nav-item px-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link p-0 text-body" data-logout-event="true">
                <i class="fa fa-power-off fa_new_class"></i>
              </a>
            </li> 
          </ul>
        </div>
      </div>
    </nav>

