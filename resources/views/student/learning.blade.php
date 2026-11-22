@extends('layouts.student', [
    'pageTitle' => 'Learning Workspace',
    'navContext' => 'student',
    'bodyClass' => 'select-none overflow-hidden bg-slate-50'
])

@section('student_content')
<style>
    .node {
        width: 18px;
        height: 18px;
        background: #4f46e5;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        border: 3px solid white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15), 0 0 0 1px #cbd5e1;
        cursor: crosshair;
        z-index: 20;
        transition: transform 0.15s ease, background-color 0.15s ease;
    }
    .node:hover {
        transform: translateY(-50%) scale(1.2);
        background: #4338ca;
    }
    .node.out { right: -9px; }
    .node.in { left: -9px; }
    #svg-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 10;
    }
    #line-group path {
        pointer-events: stroke;
        cursor: pointer;
        transition: stroke-width 0.15s ease, stroke 0.15s ease;
    }
    #line-group path:hover {
        stroke: #ef4444 !important;
        stroke-width: 5px !important;
    }
    #view-editor {
        position: relative;
    }
    .kanban-column {
        min-height: 480px;
        padding: 1rem;
    }
</style>

{{-- =========================================================================
     VIEW 1: TOPIC DISCOVERY / MODULE SELECTOR
     ========================================================================= --}}
<div id="view-selector" class="flex-1 w-full max-w-6xl mx-auto px-6 py-10 overflow-y-auto">
    {{-- Header --}}
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
            Halo, <strong>{{ auth()->user()->namalengkap ?? 'Mahasiswa' }}</strong>. Pilih modul pembelajaran untuk memulai rekonstruksi Claim, Evidence, dan Reasoning.
        </p>

        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('student.history') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:border-slate-300 rounded-lg text-xs font-semibold text-slate-700 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Riwayat & Nilai Saya</span>
            </a>
        </div>
    </div>

    {{-- Topic Cards Grid (1 col mobile, 2 tablet, 3 desktop) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="map-grid">
        @forelse($maps as $index => $map)
            @php
                $previousScore = $userScores[$map->id] ?? null;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md hover:border-primary-300 transition-all flex flex-col justify-between group">
                <div>
                    {{-- Top Card Badges --}}
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                            MODUL #{{ $map->id }}
                        </span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-primary-200">
                            {{ $map->triplets->count() }} Komponen CER
                        </span>
                    </div>

                    {{-- Title --}}
                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-primary-600 transition leading-snug mb-3">
                        {{ $map->title }}
                    </h3>

                    {{-- Features indicator --}}
                    <div class="flex items-center gap-2 text-xs text-slate-500 mb-6">
                        @if($map->allow_reading)
                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                Bacaan
                            </span>
                        @endif
                        @if($map->allow_feedback)
                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                Feedback
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status & Actions --}}
                <div class="pt-4 border-t border-slate-100">
                    @if($previousScore)
                        <div class="mb-3 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Status:</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ✓ Selesai ({{ (float)$previousScore->score }}%)
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="startLearning({{ $index }})" class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-lg transition text-center">
                                Kerjakan Lagi
                            </button>
                            <a href="{{ route('map.view', $previousScore->id) }}" class="px-3 py-2 bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-semibold rounded-lg transition text-center">
                                Lihat Hasil
                            </a>
                        </div>
                    @else
                        <button type="button" onclick="startLearning({{ $index }})" class="w-full px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1.5 group-hover:bg-primary-700">
                            <span>Mulai Belajar</span>
                            <span class="sr-only">Mulai Sekarang</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-800">Belum Ada Materi</h4>
                <p class="text-xs text-slate-500 mt-1">Dosen belum menambahkan materi pembelajaran.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- =========================================================================
     VIEW 2: STUDENT LEARNING WORKSPACE (FULLSCREEN-LIKE CANVAS)
     ========================================================================= --}}
<div id="view-editor" class="hidden flex-1 flex flex-col h-[calc(100vh-65px)] overflow-hidden">
    {{-- Minimal Workspace Header --}}
    <div id="active-title-container" class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0 z-20">
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" onclick="backToSelector()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition" title="Kembali ke Pilihan Topik">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Pilihan Materi</span>
            </button>
            <div class="h-4 w-[1px] bg-slate-200"></div>
            <div class="truncate">
                <h2 id="active-title" class="text-sm font-bold text-slate-900 truncate">Judul Materi</h2>
                <p id="active-subtitle" class="text-[11px] text-slate-500">Hubungkan Claim → Evidence → Reasoning</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right">
                <span class="block text-xs font-semibold text-slate-700">{{ auth()->user()->namalengkap }}</span>
                <span class="block text-[10px] text-slate-400 font-mono">{{ '@' . auth()->user()->username }}</span>
            </div>
            <button type="button" onclick="backToSelector()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition" title="Tutup Workspace">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    {{-- Interactive CER Canvas --}}
    <div id="cer-canvas-container" class="flex-1 relative overflow-y-auto pb-24 p-6 bg-slate-50/50">
        <svg id="svg-canvas">
            <g id="line-group"></g>
            <line id="drawing-line" stroke="#4f46e5" stroke-width="2.5" stroke-dasharray="6,4" x1="-1000" y1="-1000" x2="-1000" y2="-1000" />
        </svg>

        <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
            {{-- Column 1: CLAIM --}}
            <div class="flex flex-col bg-white rounded-xl border border-slate-200 border-t-4 border-t-primary-600 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/75 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-primary-900">1. CLAIM</h4>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Pernyataan</span>
                </div>
                <div id="col-claim" class="kanban-column space-y-3 bg-slate-50/25 flex-1 min-h-[460px]"></div>
            </div>

            {{-- Column 2: EVIDENCE --}}
            <div class="flex flex-col bg-white rounded-xl border border-slate-200 border-t-4 border-t-amber-500 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/75 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900">2. EVIDENCE</h4>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Bukti Lapangan</span>
                </div>
                <div id="col-evidence" class="kanban-column space-y-3 bg-slate-50/25 flex-1 min-h-[460px]"></div>
            </div>

            {{-- Column 3: REASONING --}}
            <div class="flex flex-col bg-white rounded-xl border border-slate-200 border-t-4 border-t-violet-600 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/75 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-violet-900">3. REASONING</h4>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Penalaran & Logika</span>
                </div>
                <div id="col-reasoning" class="kanban-column space-y-3 bg-slate-50/25 flex-1 min-h-[460px]"></div>
            </div>
        </div>
    </div>

    {{-- STICKY BOTTOM ACTION BAR (Mandatory UI/UX) --}}
    <div class="sticky bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-6 py-3.5 shadow-md z-30 flex items-center justify-between gap-4">
        {{-- Left: Secondary Learning Actions --}}
        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" id="btn-reading" onclick="toggleReading()" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-sm transition">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <span>Baca Materi</span>
            </button>

            <button type="button" id="btn-feedback" onclick="toggleFeedback()" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-sm transition">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <span>Periksa Hubungan</span>
            </button>

            <button type="button" id="btn-arrange" onclick="autoArrange()" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-sm transition" title="Susun kartu agar lebih mudah dibaca">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                <span>Rapikan Posisi</span>
            </button>
        </div>

        {{-- Right: Status & Primary Submit Button --}}
        <div class="flex items-center gap-4">
            <span id="connection-counter" class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                0 / 0 koneksi dibuat
            </span>

            <button type="button" id="btn-submit" onclick="submitScore()" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center gap-2">
                <span>Kirim Jawaban</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
    </div>

    {{-- Fallback container for backward compatibility --}}
    <div id="editor-actions" class="hidden"></div>
</div>

{{-- =========================================================================
     BLOCKING MODAL UNTUK BACA MATERI (Static Backdrop & Keyboard Locked)
     ========================================================================= --}}
<div id="dialog-reading" class="fixed inset-0 z-[250] bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6 hidden select-none" role="dialog" aria-modal="true" aria-labelledby="dialog-reading-title">
    <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
        {{-- Header --}}
        <div id="dialog-reading-header" class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <h3 id="dialog-reading-title" class="text-base font-bold text-slate-900">Materi Bacaan Pembelajaran</h3>
                    <p class="text-xs text-slate-500">Pahami informasi dan data ilmiah sebelum merekonstruksi Claim-Evidence-Reasoning</p>
                </div>
            </div>
            
            {{-- Timer & Close Button --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200 text-xs font-mono font-semibold text-indigo-700 shadow-xs" title="Indikator Timer Waktu Membaca">
                    <svg class="w-4 h-4 text-indigo-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Waktu: </span>
                    <span id="reading-timer">00:00</span>
                </div>
                <button type="button" onclick="closeReadingModal()" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1" title="Tutup Materi dan Lanjutkan Rekonstruksi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span>Tutup Materi (Close)</span>
                </button>
            </div>
        </div>

        {{-- Content Body --}}
        <div id="dialog-reading-content" class="flex-1 p-6 sm:p-8 overflow-y-auto text-sm leading-relaxed text-slate-700 whitespace-pre-wrap select-text bg-white"></div>
        <div id="dialog-reading-resize" class="sr-only"></div>

        {{-- Footer --}}
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 text-xs text-amber-700 bg-amber-50 px-3 py-1.5 rounded-md border border-amber-200">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <span>Kanvas terkunci selama materi dibaca. Klik tombol tutup untuk mulai merangkai kartu.</span>
            </div>
            <button type="button" onclick="closeReadingModal()" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center gap-2">
                <span>Tutup Materi (Selesai Membaca)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </button>
        </div>
    </div>
</div>

{{-- =========================================================================
     SUBMIT CONFIRMATION MODAL (Validation & Connection Breakdown)
     ========================================================================= --}}
<div id="modal-submit-confirm" class="fixed inset-0 z-[150] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900">Kirim Jawaban?</h3>
                <p class="text-xs text-slate-500">Periksa kembali hubungan CER sebelum mengirim.</p>
            </div>
        </div>

        {{-- Breakdown Summary --}}
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2 mb-4">
            <div class="flex justify-between text-xs">
                <span class="text-slate-600 font-medium">Koneksi Dibuat:</span>
                <span id="confirm-total-conns" class="font-bold text-slate-900 font-mono">0 / 0</span>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-slate-600 font-medium">Claim:</span>
                <span id="confirm-claim-conns" class="font-semibold text-primary-700">0 terhubung</span>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-slate-600 font-medium">Evidence:</span>
                <span id="confirm-evidence-conns" class="font-semibold text-amber-700">0 terhubung</span>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-slate-600 font-medium">Reasoning:</span>
                <span id="confirm-reasoning-conns" class="font-semibold text-violet-700">0 terhubung</span>
            </div>
        </div>

        {{-- Incomplete Warning --}}
        <div id="confirm-warning-incomplete" class="hidden mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>Masih terdapat komponen yang belum terhubung. Anda tetap dapat mengirimkan pengerjaan ini.</span>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2 justify-end">
            <button type="button" onclick="closeSubmitModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                Batal
            </button>
            <button type="button" id="btn-confirm-submit" onclick="confirmSubmit()" class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center gap-2">
                Ya, Kirim Jawaban
            </button>
        </div>
    </div>
</div>

{{-- =========================================================================
     VIEW 3: RESULT CELEBRATION MODAL
     ========================================================================= --}}
<div id="view-result" class="fixed inset-0 z-[200] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden" onclick="handleBackdropClick(event)">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full text-center shadow-2xl border border-slate-200 relative" onclick="event.stopPropagation()">
        {{-- Close button --}}
        <button type="button" onclick="stayOnPage()" title="Tutup dan lihat rekonstruksi" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>

        <h2 class="text-xl font-bold text-slate-900 mb-1">Jawaban Berhasil Dikirim!</h2>
        <p class="text-xs text-slate-500 mb-6">Hasil rekonstruksi CER Anda telah tercatat ke dalam sistem.</p>
        
        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 mb-6">
            <div id="result-score" class="text-5xl font-black text-primary-600 tracking-tight mb-1">0%</div>
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Skor Akhir</div>
        </div>

        <div class="flex flex-col gap-2.5">
            <button type="button" id="btn-stay" onclick="stayOnPage()" class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                Lihat Rekonstruksi
            </button>
            <button type="button" onclick="redoActivity()" class="w-full py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 transition">
                Kerjakan Lagi
            </button>
            <button type="button" onclick="goToSelector()" class="w-full py-2 text-slate-500 hover:text-slate-800 text-xs font-medium transition">
                ← Kembali ke Pilihan Materi
            </button>
        </div>
    </div>
</div>

<script>
    const cerMaps = @json($maps);
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/api-client.js') }}"></script>
<script src="{{ asset('js/dialog-utils.js') }}"></script>
<script src="{{ asset('js/siswa.js') }}"></script>
@endsection
