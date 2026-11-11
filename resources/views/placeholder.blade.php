@extends('layouts.app')

@php
    $pageTitle = $title ?? 'Fitur Sedang Dikembangkan';
    $navContext = $context ?? 'admin';
@endphp

@section('content')
<div class="flex-1 p-8 pb-24 overflow-y-auto bg-stone-100">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h2 class="text-3xl font-black text-slate-800 tracking-tight uppercase">
                {{ $title ?? 'Menu' }}
            </h2>
            <p class="text-sm text-slate-500 font-bold uppercase tracking-widest mt-2">
                {{ $subtitle ?? 'Antrian Migrasi Modular CERFlow' }}
            </p>
        </div>

        <div class="bg-white border-2 border-slate-300 shadow-xl p-10 text-center space-y-4">
            <div class="text-4xl">🚧</div>
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-wider">{{ $title ?? 'Fitur' }} — Tahap Migrasi</h3>
            <p class="text-sm text-slate-600 max-w-lg mx-auto">
                Layout dan navbar telah terhubung dengan aman tanpa broken link. Fungsionalitas data dan logika untuk halaman ini akan diimplementasikan pada <strong>{{ $targetPhase ?? 'Phase 3' }}</strong>.
            </p>
            <div class="pt-4">
                <a href="{{ auth()->user()->isGuru() ? route('admin.dashboard') : route('student.index') }}" class="inline-block bg-slate-800 text-white px-6 py-2.5 text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow">
                    ← Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
