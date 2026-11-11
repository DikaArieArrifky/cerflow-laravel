<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - CER Flow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center p-4 font-sans text-slate-800 antialiased">

<script>
    // Reset any leftover client session state upon visiting login
    if (window.sessionStorage) {
        sessionStorage.clear();
    }
</script>

<div class="max-w-md w-full">
    {{-- Card Container --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 sm:p-10">
        {{-- Header Logo --}}
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-xl bg-primary-600 text-white flex items-center justify-center font-bold text-lg mx-auto mb-3 shadow-sm">
                CF
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                CER<span class="text-primary-600">Flow</span> <span class="text-slate-400 font-normal text-base ml-1">Portal</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Academic Argumentation & Learning Platform
            </p>
        </div>
        
        {{-- Error Alert --}}
        @if ($errors->any())
            <div class="mb-6 p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2.5">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <p class="font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Login Form --}}
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Username
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </span>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="{{ old('username') }}" 
                           autofocus 
                           placeholder="Masukkan Username" 
                           class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition" 
                           required>
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Password
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </span>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Masukkan Password" 
                           class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition" 
                           required>
                </div>
            </div>

            <button type="submit" class="w-full mt-6 py-2.5 px-4 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-semibold text-xs rounded-lg shadow-sm transition inline-flex items-center justify-center gap-2">
                <span>MASUK KE DASHBOARD</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>

        <p class="text-center text-slate-400 text-[11px] font-medium mt-8">
            Cerflow Modular v2.0 (Laravel 11)
        </p>
    </div>
</div>

</body>
</html>
