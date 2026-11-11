@props(['navContext' => 'admin', 'activeTitle' => ''])

@php
    $user = auth()->user();
    $role = $user?->role;
@endphp

<nav class="bg-white border-b-2 border-slate-300 px-8 py-4 flex justify-between items-center sticky top-0 z-50 shadow-sm">
    <div class="flex items-center gap-4">
        <a href="{{ url('/') }}">
            <h1 class="text-xl font-black text-blue-700 italic uppercase">CER<span class="text-slate-800">Flow</span></h1>
        </a>

        @if ($role === 'siswa')
            <div id="active-title-container" class="{{ !empty($activeTitle) ? 'flex' : 'hidden' }} items-center gap-2">
                <span class="text-slate-300">/</span>
                <div id="active-title" class="text-blue-700 font-black text-[11px] uppercase italic tracking-tight bg-blue-50 px-3 py-1.5 border border-blue-200 shadow-inner">
                    {{ $activeTitle }}
                </div>
                <button type="button" onclick="backToSelector()" class="bg-white border border-slate-300 text-slate-600 hover:text-blue-700 hover:border-blue-400 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest transition-all ml-1">
                    Pilih materi lain
                </button>
            </div>
            @if (empty($activeTitle))
                <span class="text-slate-300">/</span>
                <span class="text-sm font-black text-slate-500 uppercase tracking-wider">
                    {{ $navContext === 'history' ? 'Riwayat Belajar' : 'Student Learning' }}
                </span>
            @endif
        @endif

        @if ($role === 'guru')
            <span class="text-slate-300">/</span>
            <span class="text-sm font-black text-slate-500 uppercase tracking-wider">
                @switch($navContext)
                    @case('logs') Log Aktivitas @break
                    @case('report') Laporan Nilai @break
                    @case('users') User Management @break
                    @case('settings') Pengaturan @break
                    @case('maps') Manajer Materi @break
                    @default Studio
                @endswitch
            </span>
        @endif
    </div>

    <div class="flex items-center gap-3">
        @if ($role === 'guru')
            @if ($navContext === 'admin')
                {{-- CER Map Studio (Home) Navbar --}}
                <a href="{{ route('admin.maps') }}" class="text-sm font-bold text-slate-600 hover:text-blue-700 hover:bg-blue-50 px-4 py-2 border border-transparent hover:border-blue-200 transition-all">📚 Materi</a>
                <a href="{{ route('admin.users') }}" class="text-sm font-bold text-slate-600 hover:text-blue-700 hover:bg-blue-50 px-4 py-2 border border-transparent hover:border-blue-200 transition-all">👥 Users</a>
                <a href="{{ route('admin.logs') }}" class="text-sm font-bold text-slate-600 hover:text-blue-700 hover:bg-blue-50 px-4 py-2 border border-transparent hover:border-blue-200 transition-all">📝 Log Aktivitas</a>
                <a href="{{ route('admin.report') }}" class="text-sm font-bold text-slate-600 hover:text-blue-700 hover:bg-blue-50 px-4 py-2 border border-transparent hover:border-blue-200 transition-all">📊 Laporan Nilai</a>
                <a href="{{ route('admin.settings') }}" class="text-sm font-bold text-slate-600 hover:text-blue-700 hover:bg-blue-50 px-4 py-2 border border-transparent hover:border-blue-200 transition-all">⚙️ Settings</a>
            @else
                {{-- Subpages Navbar (Maps, Users, Logs, Report, Settings) --}}
                <a href="{{ route('admin.maps') }}" class="text-sm font-bold {{ $navContext === 'maps' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">📚 Materi</a>
                <a href="{{ route('admin.users') }}" class="text-sm font-bold {{ $navContext === 'users' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">👥 Users</a>
                <a href="{{ route('admin.logs') }}" class="text-sm font-bold {{ $navContext === 'logs' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">📝 Log Aktivitas</a>
                <a href="{{ route('admin.report') }}" class="text-sm font-bold {{ $navContext === 'report' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">📊 Laporan Nilai</a>
                <a href="{{ route('admin.settings') }}" class="text-sm font-bold {{ $navContext === 'settings' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">⚙️ Settings</a>
                <a href="{{ route('admin.dashboard') }}" class="bg-slate-800 text-white px-6 py-2 text-sm font-bold shadow hover:bg-black transition-all">← Kembali ke Studio</a>
            @endif
        @elseif ($role === 'siswa')
            @if ($navContext === 'history')
                <a href="{{ route('student.index') }}" class="bg-slate-800 text-white px-4 py-1.5 text-[10px] font-black uppercase tracking-widest shadow hover:bg-black transition-all mr-1">← Kembali</a>
            @else
                <div id="editor-actions" class="hidden flex gap-2">
                    <button type="button" id="btn-reading" onclick="toggleReading()" class="bg-white border border-slate-300 text-slate-700 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 hover:border-slate-400 transition-all">📖 Bacaan</button>
                    <button type="button" id="btn-feedback" onclick="toggleFeedback()" class="bg-white border border-slate-300 text-slate-700 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 hover:border-slate-400 transition-all">💡 Feedback</button>
                    <button type="button" onclick="autoArrange()" class="bg-white border border-slate-300 text-slate-700 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 hover:border-slate-400 transition-all">🪄 Arrange</button>
                    <button type="button" onclick="submitScore()" class="bg-blue-700 text-white px-4 py-1.5 text-[10px] font-black uppercase tracking-widest hover:bg-blue-800 shadow transition-all">Submit</button>
                </div>
            @endif
            <a href="{{ route('student.index') }}" class="text-sm font-bold {{ $navContext === 'student' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">📖 Pembelajaran</a>
            <a href="{{ route('student.history') }}" class="text-sm font-bold {{ $navContext === 'history' ? 'text-blue-700 border-b-2 border-blue-700' : 'text-slate-600 hover:text-blue-700' }} px-4 py-2 transition-all">📊 Riwayat</a>
        @endif

        @if ($user)
            <form method="POST" action="{{ route('logout') }}" class="inline m-0 p-0">
                @csrf
                <button type="submit" class="text-[10px] font-black text-red-600 hover:bg-red-50 px-3 py-1.5 border border-red-200 hover:border-red-400 transition-all ml-1 uppercase">
                    LOGOUT
                </button>
            </form>
        @endif
    </div>
</nav>
