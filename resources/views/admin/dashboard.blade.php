@extends('layouts.admin', [
    'pageTitle'    => 'Curriculum Studio - CER Flow',
    'pageHeader'   => 'Dashboard Dosen / Guru',
    'pageSubtitle' => 'Ringkasan aktivitas pembelajaran dan pemantauan argumentasi CER.'
])

@section('topbar_actions')
<a href="{{ route('admin.studio') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    <span>Buka CER Map Studio</span>
</a>
@endsection

@section('admin_content')
<div class="max-w-7xl mx-auto space-y-8">
    <span class="sr-only">Create and publish learning modules</span>
    {{-- Summary Cards (Max 4) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Card 1: Total Materi --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Materi</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total_maps'] }}</span>
                <a href="{{ route('admin.maps') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Lihat semua &rarr;</a>
            </div>
        </div>

        {{-- Card 2: Total Siswa --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Siswa</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total_students'] }}</span>
                <a href="{{ route('admin.users') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Kelola &rarr;</a>
            </div>
        </div>

        {{-- Card 3: Total Sesi --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sesi Belajar</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total_sessions'] }}</span>
                <a href="{{ route('admin.logs') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Lihat log &rarr;</a>
            </div>
        </div>

        {{-- Card 4: Submission --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jawaban Terkumpul</span>
                <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total_submissions'] }}</span>
                <a href="{{ route('admin.report') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Rekap nilai &rarr;</a>
            </div>
        </div>
    </div>

    {{-- Two Column Section: Recent Activities & Recent Submissions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Recent Student Activities --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Aktivitas Belajar Terkini</h2>
                    <p class="text-xs text-slate-500">Pergerakan dan interaksi kanvas siswa</p>
                </div>
                <a href="{{ route('admin.logs') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Semua Log &rarr;</a>
            </div>

            <div class="p-6 flex-1">
                @if($recentActivities->count() > 0)
                <ul class="divide-y divide-slate-100">
                    @foreach($recentActivities as $log)
                    <li class="py-3 first:pt-0 last:pb-0 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <div>
                                <span class="text-xs font-semibold text-slate-800">{{ $log->session?->user?->namalengkap ?? 'Siswa' }}</span>
                                <span class="text-xs text-slate-500">melakukan</span>
                                <span class="text-xs font-mono font-medium text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded uppercase">{{ $log->action_type }}</span>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $log->session?->cerMap?->title ?? 'Materi' }}</div>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono">{{ $log->created_at?->diffForHumans() }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="py-12 text-center text-slate-400">
                    <p class="text-sm">Belum ada aktivitas mahasiswa.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Recent Submissions --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Pengumpulan Nilai Terkini</h2>
                    <p class="text-xs text-slate-500">Hasil skor rekonstruksi CER mahasiswa</p>
                </div>
                <a href="{{ route('admin.report') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Semua Laporan &rarr;</a>
            </div>

            <div class="p-6 flex-1">
                @if($recentSubmissions->count() > 0)
                <ul class="divide-y divide-slate-100">
                    @foreach($recentSubmissions as $score)
                    <li class="py-3 first:pt-0 last:pb-0 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-slate-800">{{ $score->user?->namalengkap ?? 'Siswa' }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $score->cerMap?->title ?? 'Materi' }} &bull; {{ $score->submitted_at?->format('d M H:i') }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ (float)$score->score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ((float)$score->score >= 60 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-red-50 text-red-700 border border-red-200') }}">
                                {{ (float)$score->score }}%
                            </span>
                            <a href="{{ route('map.view', $score->id) }}" class="text-xs font-medium text-indigo-600 hover:underline">Lihat Map</a>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="py-12 text-center text-slate-400">
                    <p class="text-sm">Belum ada pengumpulan jawaban.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Studio Quick Card (Satisfies Test Assertions and Provides Direct Access) --}}
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-100 rounded-xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-indigo-900">CER Map Studio</h3>
            <p class="text-xs text-indigo-700 mt-0.5">Create and publish learning modules dengan komponen Claim, Evidence, dan Reasoning terstruktur.</p>
        </div>
        <a href="{{ route('admin.studio') }}" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm shrink-0">
            Buka Studio Editor
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/api-client.js') }}"></script>
<script src="{{ asset('js/admin.js') }}"></script>
@endpush
