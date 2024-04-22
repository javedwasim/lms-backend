<style>
    .goog-te-banner-frame.skiptranslate {
        display: none !important;
    } 
    body {
        top: 0px !important; 
    }
    .goog-logo-link {
        display:none !important;
    }
    .trans-section {
        margin: 100px;
    }
</style>

<header id="header" class="fixed-top">
    <div class="container-fluid d-flex align-items-center">
      <h1 class="logo me-auto"><a href="{{ url('/')}}"><img src="{{ asset('front/assets/img/logo.png') }}" class="img-fluid" alt=""></a></h1>
      <!-- Uncomment below if you prefer to use an image logo -->
      <!-- <a href="index.html" class="logo me-auto"><img src="assets/img/logo.png" alt="" class="img-fluid"></a>-->
      <div class="inr-srch">
        <!-- <form action="" method="post">
              <input type="email" name="email" placeholder="Where are you going?">
              <input type="submit" value="Discover">
        </form> -->
      </div>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link active" href="{{ url('/')}}" >Home</a></li>
          <li><a class="nav-link" href="{{ url('fr_page')}}/1" >About</a></li>
          <li><a class="nav-link" href="{{ url('/property_list')}}" >Properties</a></li>
          <li><a class="nav-link" href="{{ url('/')}}"><img src="{{ asset('front/assets/img/call.svg') }}" class="img-fluid" alt="">&nbsp; (+971) 1900 6587</a></li>
          @if(!\Auth::check()) 
              <li> 
                <a class="getstarted" href="" data-toggle="modal" data-target="#form">Sign Up</a>
              </li> 
          @else
            <!-- <li><a href="#"  data-toggle="modal" data-target="#form_contact">Contact Us</a></li> -->
            <li class="drop getstarted">My Account
              <ul class="profile-dropwn">
                <li><a href="{{ url('profile') }}">My Profile</a></li>
                <!-- <li><a href="#" data-toggle="modal" data-target="#form9">My package</a></li>
                <li><a href="#"  data-toggle="modal" data-target="#form8">Plan</a></li> -->
                <li><a href="{{ url('fr_logout') }}">Logout</a></li>
              </ul>
            </li>
          @endif 
           
          <li class="slct-lang">
            <!-- <select class="selectpicker" data-width="fit">
                <option data-content='<span class="flag-icon flag-icon-us"></span> English'>English</option>
                <option  data-content='<span class="flag-icon flag-icon-mx"></span> Español'>Español</option>
            </select> -->
            <div id="google_translate_element" style="display: none;"></div>
            <select class="selectpicker" data-width="fit" onchange="translateLanguage(this.value);">
                <option  data-content='<span class="flag-icon flag-icon-us"></span> English' value="English">English</option>
                <option  data-content='<span class="flag-icon flag-icon-ar"></span> Arabic' value="Arabic">Arabic</option>
                 
                <!-- <option data-content='<span class="flag-icon flag-icon-af"></span> Afrikaans' value="Afrikaans">Afrikaans</option>
                <option  data-content='<span class="flag-icon flag-icon-al"></span> Albanian' value="Albanian">Albanian</option>
                <option  data-content='<span class="flag-icon flag-icon-ar"></span> Arabic' value="Arabic">Arabic</option>
                <option  data-content='<span class="flag-icon flag-icon-am"></span> Armenian' value="Armenian">Armenian</option>
                <option  data-content='<span class="flag-icon flag-icon-az"></span> Azerbaijani' value="Azerbaijani">Azerbaijani</option>
                <option  data-content='<span class="flag-icon flag-icon-eu"></span> Basque' value="Basque">Basque</option>
                <option  data-content='<span class="flag-icon flag-icon-be"></span> Belarusian' value="Belarusian">Belarusian</option>
                <option  data-content='<span class="flag-icon flag-icon-bn"></span> Bengali' value="Bengali">Bengali</option>
                <option  data-content='<span class="flag-icon flag-icon-bs"></span> Bosnian' value="Bosnian">Bosnian</option>
                <option  data-content='<span class="flag-icon flag-icon-bg"></span> Bulgarian' value="Bulgarian">Bulgarian</option>
                <option  data-content='<span class="flag-icon flag-icon-cu"></span> Catalan' value="Catalan">Catalan</option>
                <option  data-content='<span class="flag-icon flag-icon-cn"></span> Chinese (Simplified)' value="Chinese (Simplified)">Chinese (Simplified)</option>
                <option  data-content='<span class="flag-icon flag-icon-tw"></span> Chinese (Traditional)' value="Chinese (Traditional)">Chinese (Traditional)</option>
                <option  data-content='<span class="flag-icon flag-icon-co"></span> Corsican' value="Corsican">Corsican</option>
                <option  data-content='<span class="flag-icon flag-icon-hr"></span> Croatian' value="Croatian">Croatian</option>
                <option  data-content='<span class="flag-icon flag-icon-cz"></span> Czech' value="Czech">Czech</option>
                <option  data-content='<span class="flag-icon flag-icon-dk"></span> Danish' value="Danish">Danish</option>
                <option  data-content='<span class="flag-icon flag-icon-nl"></span> Dutch' value="Dutch">Dutch</option>
                <option  data-content='<span class="flag-icon flag-icon-us"></span> English' value="English">English</option>
                <option  data-content='<span class="flag-icon flag-icon-et"></span> Estonian' value="Estonian">Estonian</option>
                <option  data-content='<span class="flag-icon flag-icon-fi"></span> Finnish' value="Finnish">Finnish</option>
                <option  data-content='<span class="flag-icon flag-icon-fr"></span> French' value="French">French</option>
                <option  data-content='<span class="flag-icon flag-icon-gl"></span> Galician' value="Galician">Galician</option>
                <option  data-content='<span class="flag-icon flag-icon-ge"></span> Georgian' value="Georgian">Georgian</option>
                <option  data-content='<span class="flag-icon flag-icon-de"></span> German' value="German">German</option>
                <option  data-content='<span class="flag-icon flag-icon-gr"></span> Greek' value="Greek">Greek</option>
                <option  data-content='<span class="flag-icon flag-icon-gu"></span> Gujarati' value="Gujarati">Gujarati</option>
                <option  data-content='<span class="flag-icon flag-icon-ht"></span> Haitian Creole' value="Haitian Creole">Haitian Creole</option>
                <option  data-content='<span class="flag-icon flag-icon-il"></span> Hebrew' value="Hebrew">Hebrew</option>
                <option  data-content='<span class="flag-icon flag-icon-in"></span> Hindi' value="Hindi">Hindi</option>
                <option  data-content='<span class="flag-icon flag-icon-hu"></span> Hungarian' value="Hungarian">Hungarian</option>
                <option  data-content='<span class="flag-icon flag-icon-is"></span> Icelandic' value="Icelandic">Icelandic</option>
                <option  data-content='<span class="flag-icon flag-icon-id"></span> Indonesian' value="Indonesian">Indonesian</option>
                <option  data-content='<span class="flag-icon flag-icon-ga"></span> Irish' value="Irish">Irish</option>
                <option  data-content='<span class="flag-icon flag-icon-it"></span> Italian' value="Italian">Italian</option>
                <option  data-content='<span class="flag-icon flag-icon-jp"></span> Japanese' value="Japanese">Japanese</option>
                <option  data-content='<span class="flag-icon flag-icon-kn"></span> Kannada' value="Kannada">Kannada</option>
                <option  data-content='<span class="flag-icon flag-icon-kz"></span> Kazakh' value="Kazakh">Kazakh</option>
                <option  data-content='<span class="flag-icon flag-icon-km"></span> Khmer' value="Khmer">Khmer</option>
                <option  data-content='<span class="flag-icon flag-icon-rw"></span> Kinyarwanda' value="Kinyarwanda">Kinyarwanda</option>
                <option  data-content='<span class="flag-icon flag-icon-kr"></span> Korean' value="Korean">Korean</option>
                <option  data-content='<span class="flag-icon flag-icon-ir"></span> Kurdish' value="Kurdish (Kurmanji)">Kurdish</option>
                <option  data-content='<span class="flag-icon flag-icon-ky"></span> Kyrgyz' value="Kyrgyz">Kyrgyz</option>
                <option  data-content='<span class="flag-icon flag-icon-la"></span> Lao' value="Lao">Lao</option>
                <option  data-content='<span class="flag-icon flag-icon-lv"></span> Latvian' value="Latvian">Latvian</option>
                <option  data-content='<span class="flag-icon flag-icon-lt"></span> Lithuanian' value="Lithuanian">Lithuanian</option>
                <option  data-content='<span class="flag-icon flag-icon-lb"></span> Luxembourgish' value="Luxembourgish">Luxembourgish</option>
                <option  data-content='<span class="flag-icon flag-icon-mk"></span> Macedonian' value="Macedonian">Macedonian</option>
                <option  data-content='<span class="flag-icon flag-icon-mg"></span> Malagasy' value="Malagasy">Malagasy</option>
                <option  data-content='<span class="flag-icon flag-icon-ms"></span> Malay' value="Malay">Malay</option>
                <option  data-content='<span class="flag-icon flag-icon-ml"></span> Malayalam' value="Malayalam">Malayalam</option>
                <option  data-content='<span class="flag-icon flag-icon-mt"></span> Maltese' value="Maltese">Maltese</option>
                <option  data-content='<span class="flag-icon flag-icon-mr"></span> Marathi' value="Marathi">Marathi</option>
                <option  data-content='<span class="flag-icon flag-icon-mn"></span> Mongolian' value="Mongolian">Mongolian</option>
                <option  data-content='<span class="flag-icon flag-icon-my"></span> Myanmar (Burmese)' value="Myanmar (Burmese)">Myanmar (Burmese)</option>
                <option  data-content='<span class="flag-icon flag-icon-ne"></span> Nepali' value="Nepali">Nepali</option>
                <option  data-content='<span class="flag-icon flag-icon-no"></span> Norwegian' value="Norwegian">Norwegian</option>
                <option  data-content='<span class="flag-icon flag-icon-mw"></span> Nyanja (Chichewa)' value="Nyanja (Chichewa)">Nyanja (Chichewa)</option>
                <option  data-content='<span class="flag-icon flag-icon-ps"></span> Pashto' value="Pashto">Pashto</option>
                <option  data-content='<span class="flag-icon flag-icon-pl"></span> Polish' value="Polish">Polish</option>
                <option  data-content='<span class="flag-icon flag-icon-pt"></span> Portuguese (Portugal, Brazil)' value="Portuguese (Portugal, Brazil)">Portuguese (Portugal, Brazil)</option>
                <option  data-content='<span class="flag-icon flag-icon-pa"></span> Punjabi' value="Punjabi">Punjabi</option>
                <option  data-content='<span class="flag-icon flag-icon-ro"></span> Romanian' value="Romanian">Romanian</option>
                <option  data-content='<span class="flag-icon flag-icon-ru"></span> Russian' value="Russian">Russian</option>
                <option  data-content='<span class="flag-icon flag-icon-sm"></span> Samoan' value="Samoan">Samoan</option>
                <option  data-content='<span class="flag-icon flag-icon-gd"></span> Scots Gaelic' value="Scots Gaelic">Scots Gaelic</option>
                <option  data-content='<span class="flag-icon flag-icon-sr"></span> Serbian' value="Serbian">Serbian</option>
                <option  data-content='<span class="flag-icon flag-icon-st"></span> Sesotho' value="Sesotho">Sesotho</option>
                <option  data-content='<span class="flag-icon flag-icon-sn"></span> Shona' value="Shona">Shona</option>
                <option  data-content='<span class="flag-icon flag-icon-sd"></span> Sindhi' value="Sindhi">Sindhi</option>
                <option  data-content='<span class="flag-icon flag-icon-si"></span> Sinhala (Sinhalese)' value="Sinhala (Sinhalese)">Sinhala (Sinhalese)</option>
                <option  data-content='<span class="flag-icon flag-icon-sk"></span> Slovak' value="Slovak">Slovak</option>
                <option  data-content='<span class="flag-icon flag-icon-sl"></span> Slovenian' value="Slovenian">Slovenian</option>
                <option  data-content='<span class="flag-icon flag-icon-so"></span> Somali' value="Somali">Somali</option>
                <option  data-content='<span class="flag-icon flag-icon-es"></span> Spanish' value="Spanish">Spanish</option>
                <option  data-content='<span class="flag-icon flag-icon-sv"></span> Swedish' value="Swedish">Swedish</option>
                <option  data-content='<span class="flag-icon flag-icon-tl"></span> Tagalog (Filipino)' value="Tagalog (Filipino)">Tagalog (Filipino)</option>
                <option  data-content='<span class="flag-icon flag-icon-tg"></span> Tajik' value="Tajik">Tajik</option>
                <option  data-content='<span class="flag-icon flag-icon-tt"></span> Tatar' value="Tatar">Tatar</option>
                <option  data-content='<span class="flag-icon flag-icon-th"></span> Thai' value="Thai">Thai</option>
                <option  data-content='<span class="flag-icon flag-icon-tr"></span> Turkish' value="Turkish">Turkish</option>
                <option  data-content='<span class="flag-icon flag-icon-tk"></span> Turkmen' value="Turkmen">Turkmen</option>
                <option  data-content='<span class="flag-icon flag-icon-ua"></span> Ukrainian' value="Ukrainian">Ukrainian</option>
                <option  data-content='<span class="flag-icon flag-icon-pk"></span> Urdu' value="Urdu">Urdu</option>
                <option  data-content='<span class="flag-icon flag-icon-ug"></span> Uyghur' value="Uyghur">Uyghur</option>
                <option  data-content='<span class="flag-icon flag-icon-uz"></span> Uzbek' value="Uzbek">Uzbek</option>
                <option  data-content='<span class="flag-icon flag-icon-vi"></span> Vietnamese' value="Vietnamese">Vietnamese</option>
                <option  data-content='<span class="flag-icon flag-icon-cy"></span> Welsh' value="Welsh">Welsh</option>
                <option  data-content='<span class="flag-icon flag-icon-zw"></span> Xhosa' value="Xhosa">Xhosa</option> -->
            </select>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>

  </header>