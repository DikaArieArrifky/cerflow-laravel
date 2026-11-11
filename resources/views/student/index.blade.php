@extends('layouts.student', [
    'pageTitle' => 'Learning Workspace',
    'navContext' => 'student'
])

@section('student_content')
<div class="flex-1 w-full max-w-6xl mx-auto px-6 py-10 overflow-y-auto">
    {{-- Header Discovery --}}
    <div class="mb-10 text-center max-w-2xl mx-auto">
        <span class="sr-only">Curriculum Discovery</span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-200 mb-3">
            CERFlow Academic Space
        </span>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Pilihan <span class="text-primary-600">Materi</span>
        </h1>
        <span class="sr-only">Select Topic</span>
        <p class="text-sm text-slate-500 mt-2">
            Pilih modul pembelajaran untuk memulai rekonstruksi Claim, Evidence, dan Reasoning.
        </p>

        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('student.history') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:border-slate-300 rounded-lg text-xs font-semibold text-slate-700 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Lihat Hasil Sebelumnya</span>
            </a>
        </div>
    </div>
    
    {{-- Topic Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="map-grid">
        @forelse ($maps ?? [] as $index => $map)
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md hover:border-primary-300 transition-all flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                            MODUL #{{ $map->id }}
                        </span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-primary-200">
                            {{ $map->triplets ? $map->triplets->count() : 0 }} Komponen
                        </span>
                    </div>

                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-primary-600 transition leading-snug mb-3">
                        {{ $map->title }}
                    </h3>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <a href="{{ route('student.index') }}" class="w-full px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1.5">
                        <span>Mulai Sekarang</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <p class="text-sm font-medium text-slate-500">Belum ada modul pembelajaran yang tersedia.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
