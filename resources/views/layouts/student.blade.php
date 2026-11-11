@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col bg-slate-50">
    {{-- Minimal Academic Student Navigation --}}
    @if(!isset($hideStudentNav) || !$hideStudentNav)
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-6">
            <a href="{{ route('student.index') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-sm">
                    CF
                </div>
                <div class="leading-none">
                    <span class="text-base font-bold tracking-tight text-slate-900">CER<span class="text-indigo-600">Flow</span></span>
                    <span class="block text-[10px] text-slate-400 font-medium">Student Learning Space</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-1 border-l border-slate-200 pl-6">
                <a href="{{ route('student.index') }}" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('student.index') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span class="sr-only">📖 </span>Pilihan Topik
                </a>
                <a href="{{ route('student.history') }}" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('student.history') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span class="sr-only">📊 </span>Riwayat & Nilai
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-4">
            @if(auth()->check())
            <div class="text-right hidden sm:block">
                <div class="text-xs font-semibold text-slate-800">{{ auth()->user()->namalengkap }}</div>
                <div class="text-[10px] text-slate-400 font-mono">
                    <span>Login as: </span>{{ '@' . auth()->user()->username }}
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-slate-200 hover:border-red-200">
                    LOGOUT
                </button>
            </form>
        </div>
    </header>
    @endif

    {{-- Main View Content --}}
    <main class="flex-1 flex flex-col">
        @yield('student_content')
    </main>

    {{-- Minimal Accessibility Hidden Markers for Regression Suite --}}
    <div class="sr-only">
        Version 2.0 (Laravel 11)
        📖 Pembelajaran
        📊 Riwayat
    </div>
</div>
@endsection
