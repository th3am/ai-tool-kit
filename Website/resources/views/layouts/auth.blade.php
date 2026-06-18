<!DOCTYPE html>
<html lang="en" x-data="{ 
    darkMode: localStorage.getItem('theme') 
        ? localStorage.getItem('theme') === 'dark' 
        : window.matchMedia('(prefers-color-scheme: dark)').matches
}" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>EduTech Platform</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
  <!-- Tailwind & FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Intl Tel Input -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
  <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Axios -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <script>
    // Axios CSRF Setup
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    let token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
    }

    tailwind.config = {
      darkMode: "class",
      theme: {
      extend: {
      colors: {
      primary: "#7C3AED",
      secondary: "#06B6D4",
      },
        },
      },
    };

    // Global Toast Helper using SweetAlert2
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });

    window.showToast = (message, type = 'success') => {
        Toast.fire({
            icon: type,
            title: message
        });
    }
  </script>

    <style>
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        .fade-in.opacity-100 {
            opacity: 1;
            transform: translateY(0);
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body
  class="min-h-screen font-sans relative bg-gray-100 text-gray-800 dark:bg-[#060b21] dark:text-white overflow-x-hidden 
  overflow-y-auto flex items-center justify-center transition-colors duration-500 px-4 sm:px-6 md:px-8"
>
  
  <!-- Toggle Dark Mode -->
  <button
    @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
    class="absolute top-4 right-4 z-50 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-lg text-sm 
    sm:top-6 sm:right-6">
    <span x-text="darkMode ? '☀️' : '🌙'"></span>
  </button>

 <!-- Decorative gradient wave backgrounds -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
       <!-- Top-right wave -->
      <svg class="absolute -top-40 -right-40 w-[800px] h-[800px] opacity-30 wave-animate-1" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#7C3AED;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#06B6D4;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#22D3EE;stop-opacity:1" />
          </linearGradient>
          <filter id="noise1">
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="4" />
            <feColorMatrix type="saturate" values="0" />
            <feBlend mode="multiply" in="SourceGraphic" />
          </filter>
        </defs>
        <path d="M 0 400 Q 200 300 400 400 T 800 400 L 800 0 L 0 0 Z" fill="url(#grad1)" filter="url(#noise1)" />
      </svg>
      
       <!-- Bottom-left wave -->
      <svg class="absolute -bottom-40 -left-40 w-[700px] h-[700px] opacity-25 wave-animate-2" viewBox="0 0 700 700" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="grad2" x1="100%" y1="100%" x2="0%" y2="0%">
            <stop offset="0%" style="stop-color:#22D3EE;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#06B6D4;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#7C3AED;stop-opacity:1" />
          </linearGradient>
          <filter id="noise2">
            <feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="3" />
            <feColorMatrix type="saturate" values="0" />
            <feBlend mode="multiply" in="SourceGraphic" />
          </filter>
        </defs>
        <path d="M 0 300 Q 175 200 350 300 T 700 300 L 700 700 L 0 700 Z" fill="url(#grad2)" filter="url(#noise2)" />
      </svg>
      
       <!-- Blur overlay for depth -->
      <div class="absolute inset-0 backdrop-blur-3xl"></div>
    </div>

  <div class="flex flex-col lg:flex-row items-center justify-center lg:justify-between w-full max-w-6xl
   mx-auto relative z-10 py-10 fade-in opacity-100">
   
    @if(isset($slot))
        {{ $slot }}
    @else
        @yield('content')
    @endif

  </div>

</body>
</html>
