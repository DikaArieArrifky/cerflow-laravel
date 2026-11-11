@extends('layouts.app')

@section('content')
<div class="flex h-screen overflow-hidden bg-slate-50">
    {{-- Left Sidebar (250px) --}}
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 z-30 select-none">
        {{-- Branding --}}
        <div class="h-16 px-6 border-b border-slate-100 flex items-center justify-between">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 group">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-sm group-hover:bg-indigo-700 transition-colors">
                    CF
                </div>
                <div class="leading-none">
                    <span class="text-base font-bold tracking-tight text-slate-900">CER<span class="text-indigo-600">Flow</span></span>
                    <span class="block text-[10px] text-slate-400 font-medium tracking-wide">Academic Workspace</span>
                </div>
            </a>
        </div>

        {{-- Navigation Menu --}}
        <div class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
            {{-- MAIN GROUP --}}
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Utama</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('admin.maps') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.maps') || request()->routeIs('admin.studio') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>Materi Pembelajaran<span class="sr-only"> 📚 Materi</span></span>
                    </a>

                    <a href="{{ route('admin.users') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.users') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Kelola Pengguna<span class="sr-only"> 👥 Users</span></span>
                    </a>
                </nav>
            </div>

            {{-- ANALYTICS GROUP --}}
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Analitik</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.logs') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.logs*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Log Aktivitas<span class="sr-only"> 📝 Log Aktivitas</span></span>
                    </a>

                    <a href="{{ route('admin.report') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.report') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Laporan Nilai<span class="sr-only"> 📊 Laporan Nilai</span></span>
                    </a>

                    <a href="{{ route('admin.clustering') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.clustering*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Clustering K-Means<span class="sr-only"> 🔬 Clustering</span></span>
                    </a>
                </nav>
            </div>

            {{-- SYSTEM GROUP --}}
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Sistem</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.settings') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('admin.settings') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Pengaturan<span class="sr-only"> ⚙️ Settings</span></span>
                    </a>
                </nav>
            </div>
        </div>

        {{-- User Info & Logout --}}
        <div class="p-3 border-t border-slate-100 bg-slate-50/50">
            @if(auth()->check())
            <div class="px-2 py-2 mb-2">
                <div class="text-[10px] text-slate-400 font-medium">Login as:</div>
                <div class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->namalengkap }}</div>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-xs text-indigo-600 font-mono">{{ '@' . auth()->user()->username }}</span>
                    <span class="text-slate-300">•</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-700 uppercase">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>LOGOUT</span>
                </button>
            </form>

            <div class="mt-2 text-center text-[10px] text-slate-400 font-mono">
                Version 2.0 (Laravel 11)
            </div>
        </div>
    </aside>

    {{-- Main Column: Topbar + Page Content --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        {{-- Topbar --}}
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-lg font-semibold text-slate-900 leading-tight">
                    {{ $pageHeader ?? $pageTitle ?? 'Dashboard' }}
                </h1>
                @if(isset($pageSubtitle))
                <p class="text-xs text-slate-500 mt-0.5">{{ $pageSubtitle }}</p>
                @endif
            </div>

            <div class="flex items-center gap-4">
                @yield('topbar_actions')

                <div class="h-8 w-px bg-slate-200"></div>

                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center text-xs font-semibold">
                        {{ strtoupper(substr(auth()->user()->namalengkap ?? 'A', 0, 1)) }}
                    </div>
                    <span class="text-sm font-medium text-slate-700 hidden md:inline">
                        {{ auth()->user()->namalengkap ?? 'Admin' }}
                    </span>
                </div>
            </div>
        </header>

        {{-- Main Page Scrollable Content --}}
        <main class="flex-1 overflow-y-auto p-8">
            @yield('admin_content')
        </main>
    </div>
</div>
@endsection
