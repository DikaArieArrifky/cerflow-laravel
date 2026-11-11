@extends('layouts.admin', [
    'pageTitle'    => 'CER Map Studio - CERFlow',
    'pageHeader'   => 'CER Map Studio',
    'pageSubtitle' => 'Editor penyusunan materi dan triplet Claim, Evidence, dan Reasoning.'
])

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.maps') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        <span>Daftar Materi</span>
    </a>

    <button type="button" onclick="openAIExtractor()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors shadow-2xs">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <span>AI Extract</span>
    </button>

    <a id="btn-preview-map" href="#" target="_blank" class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs">
        <span>Preview</span>
    </a>

    <button type="button" id="btn-delete-map" onclick="deleteMap(currentMapId)" class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors shadow-2xs">
        <span>Hapus</span>
    </button>

    <button type="button" id="btn-save-map" onclick="saveToDB()" class="inline-flex items-center gap-2 px-4 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
        </svg>
        <span>Simpan Map</span>
    </button>
</div>
@endsection

@section('admin_content')
<div class="max-w-6xl mx-auto space-y-8 pb-16">

    {{-- Card 1: Informasi Modul Materi --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Informasi Materi</h2>
                <p class="text-xs text-slate-500">Tentukan judul topik dan fitur yang dapat diakses mahasiswa.</p>
            </div>
            <button type="button" onclick="newMap()" class="px-3 py-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                + Bersihkan & Buat Baru
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label for="map-title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Judul Materi Pembelajaran <span class="text-red-500">*</span>
                </label>
                <input id="map-title" type="text" placeholder="Contoh: Fotosintesis pada Tumbuhan Air" 
                       class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
            </div>

            {{-- Feature Switches --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                {{-- Toggle Feedback --}}
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <span class="text-xs font-semibold text-slate-800 block">Visual Feedback</span>
                        <span class="text-[11px] text-slate-500">Siswa dapat memeriksa kebenaran koneksi sementara</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="allow-feedback" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                {{-- Toggle Reading --}}
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-800">Teks Bacaan Materi</span>
                            <button type="button" onclick="openReadingEditor()" class="text-[11px] text-indigo-600 hover:underline font-medium">
                                [Edit Bacaan]
                            </button>
                        </div>
                        <span class="text-[11px] text-slate-500">Menyediakan panel materi ilmiah untuk dibaca siswa</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="allow-reading" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Komponen CER (Claim, Evidence, Reasoning) --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Komponen Argumen Ilmiah (CER Triplets)</h2>
                <p class="text-xs text-slate-500">Satu triplet terdiri dari tepat 1 Klaim, 1 Bukti, dan 1 Penalaran.</p>
            </div>
        </div>

        {{-- Triplet Input Form --}}
        <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Claim --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-indigo-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        1. Claim (Pernyataan)
                    </label>
                    <textarea id="in-c" rows="4" placeholder="Ketik pernyataan ilmiah di sini..." 
                              class="w-full p-3 text-xs bg-white border border-indigo-200 rounded-lg focus:outline-none focus:border-indigo-500 transition-colors leading-relaxed resize-none"></textarea>
                </div>

                {{-- Evidence --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-indigo-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        2. Evidence (Bukti Empiris)
                    </label>
                    <textarea id="in-e" rows="4" placeholder="Ketik bukti empiris atau fakta observasi..." 
                              class="w-full p-3 text-xs bg-white border border-indigo-200 rounded-lg focus:outline-none focus:border-indigo-500 transition-colors leading-relaxed resize-none"></textarea>
                </div>

                {{-- Reasoning --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-indigo-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        3. Reasoning (Penalaran Ilmiah)
                    </label>
                    <textarea id="in-r" rows="4" placeholder="Ketik logika atau prinsip sains penghubung..." 
                              class="w-full p-3 text-xs bg-white border border-indigo-200 rounded-lg focus:outline-none focus:border-indigo-500 transition-colors leading-relaxed resize-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="btn-cancel-triplet" onclick="cancelTripletEdit()" 
                        class="hidden px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors">
                    Batal Edit
                </button>
                <button type="button" id="btn-save-triplet" onclick="addLocal()" 
                        class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
                    + Tambahkan Triplet
                </button>
            </div>
        </div>

        {{-- Triplet Table List --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="py-3 px-4 font-semibold uppercase tracking-wider w-1/3">Claim</th>
                        <th class="py-3 px-4 font-semibold uppercase tracking-wider w-1/3">Evidence</th>
                        <th class="py-3 px-4 font-semibold uppercase tracking-wider w-1/3">Reasoning</th>
                        <th class="py-3 px-4 font-semibold uppercase tracking-wider text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody id="table-body" class="divide-y divide-slate-100 text-slate-700">
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400">
                            Belum ada triplet yang ditambahkan ke materi ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Reading Material Drawer Modal --}}
<div id="dialog-reading-edit" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
        <div id="dialog-reading-edit-header" class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <span>📖</span> Teks Bacaan Ilmiah
            </h3>
            <button type="button" onclick="closeReadingEditor()" class="text-slate-400 hover:text-slate-600 transition-colors">✕</button>
        </div>
        <div class="p-6 flex-1 flex flex-col space-y-3">
            <p class="text-xs text-slate-500">Teks ini akan dibaca oleh mahasiswa di dalam workspace sebagai bahan rekonstruksi.</p>
            <textarea id="reading-text-input" rows="12" class="w-full p-4 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white leading-relaxed resize-none flex-1" placeholder="Tempelkan atau tulis teks materi ilmiah di sini..."></textarea>
        </div>
        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button type="button" onclick="closeReadingEditor()" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                Simpan & Tutup
            </button>
        </div>
    </div>
</div>

{{-- AI CER Extractor Modal --}}
<div id="dialog-ai-extract" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
        <div id="dialog-ai-extract-header" class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <span>🪄</span> AI CER Extractor (Google Gemini)
            </h3>
            <button type="button" onclick="closeAIExtractor()" class="text-slate-400 hover:text-slate-600 transition-colors">✕</button>
        </div>
        <div class="p-6 flex-1 flex flex-col space-y-3">
            <p class="text-xs text-slate-500">Tempelkan artikel atau teks ilmiah di bawah ini. AI akan menganalisis dan mengekstrak komponen Claim, Evidence, dan Reasoning secara otomatis.</p>
            <textarea id="ai-extract-input" rows="10" class="w-full p-4 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white leading-relaxed resize-none flex-1" placeholder="Tempelkan teks artikel di sini..."></textarea>
            
            <div id="ai-loading" class="hidden flex items-center gap-2 text-xs font-semibold text-indigo-600">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menganalisis teks ilmiah dengan Gemini AI...</span>
            </div>
        </div>
        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <button type="button" onclick="closeAIExtractor()" class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-800">
                Batal
            </button>
            <button type="button" id="btn-run-ai" onclick="runAIExtraction()" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
                Jalankan Ekstraksi
            </button>
        </div>
    </div>
</div>

{{-- Hidden Compatibility Elements for JS/admin.js --}}
<div class="hidden">
    <input type="text" id="map-search">
    <div id="map-list"></div>
    <div id="dialog-reading-edit-resize"></div>
    <div id="dialog-ai-extract-resize"></div>
</div>

<script>
    window.INITIAL_EDIT_MAP_ID = {{ $initialEditMapId ?? 0 }};
    window.INITIAL_EDIT_MAP_DATA = @json($initialEditMap);
</script>
<script src="{{ asset('js/api-client.js') }}"></script>
<script src="{{ asset('js/dialog-utils.js') }}"></script>
<script src="{{ asset('js/admin.js') }}"></script>
@endsection
