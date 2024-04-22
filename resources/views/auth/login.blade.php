<x-guest-layout>
  <main class="mt-0 main-content">
    <div class="pt-5 page-header align-items-start min-vh-50 main_dv_img">
      <span class="mask bg-gradient-dark opacity-6"></span>

      <div class="container">
        <div class="row justify-content-center"></div>
        <div class="row">
          <div class="m-auto col-xl-4 col-lg-5 col-md-7">
            <div class="card z-index-0">
              <div class="pt-4 text-center card-header" style="background: #31144d;">
                <div class="mx-auto text-center col-lg-12">
                  <img src="{{ url('img/logo.png') }}" style="position: relative;z-index: 999; width: 156px;">
                </div>
                <h5>Login </h5>
              </div>
              <div class="card-body">
                @if (session('status'))
                <div class="mb-4 text-sm font-medium text-green-600">
                  {{ session('status') }}
                </div>
                @endif
                @if(Session::has('flash_message'))
                <div class="alert {{ Session::get('flash_type') }}">
                  <button data-dismiss="alert" class="close close-sm" type="button">
                    <i class="icon-remove"></i>
                  </button>
                  {{ Session::get('flash_message') }}
                </div>
                @endif
                @if ($errors->any())
                <div class="text-sm text-red-600">
                  <strong>Errors:</strong>
                  <ul class="list-disc">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                  @csrf
                  <div class="mb-3">
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="Email" aria-label="Email">
                  </div>
                  <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" aria-label="Password">
                  </div>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="my-4 mb-2 btn bg-gradient-primary w-100">Sign in</button>
                  </div>
                  <div class="mb-2 text-center position-relative">
                    <p class="px-3 mb-2 text-sm bg-white font-weight-bold text-secondary text-border d-inline z-index-2">
                      or
                    </p>
                  </div>
                  <div class="text-center">
                    @if (Route::has('password.request'))
                    <a class="mt-2 mb-4 btn bg-gradient-dark w-100" href="{{ url('forgot-password') }}">
                      {{ __('Forgot your password?') }}
                    </a>
                    @endif
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</x-guest-layout>