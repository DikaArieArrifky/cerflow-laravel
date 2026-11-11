@extends('layouts.admin', [
    'pageTitle' => 'Pengaturan Aplikasi',
    'pageHeader' => 'Pengaturan Aplikasi',
    'pageSubtitle' => 'Konfigurasi integrasi AI dan parameter sistem CERFlow',
    'navContext' => 'settings'
])

@section('admin_content')
<div class="max-w-2xl">
    <span class="sr-only">Pengaturan Aplikasi</span>
    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf

        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/75">
            <h3 class="text-sm font-semibold text-slate-800">Gemini Integration</h3>
            <p class="text-xs text-slate-500 mt-0.5">Integrasi Google Gemini untuk otomatisasi ekstraksi CER dari bacaan ilmiah.</p>
        </div>
        
        <div class="p-6 space-y-5">
            <div>
                <label for="gemini_api_key" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Your Gemini API Key
                </label>
                
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="password" 
                               id="gemini_api_key" 
                               name="gemini_api_key" 
                               value="{{ old('gemini_api_key', auth()->user()->gemini_api_key) }}" 
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition" 
                               placeholder="AIzaSy..." 
                               autocomplete="off">
                        <button type="button" 
                                id="toggle-key-visibility" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                title="Lihat/Sembunyikan API Key">
                            <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>

                    <button type="button" 
                            id="btn-test-connection" 
                            class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center gap-2 whitespace-nowrap disabled:opacity-50">
                        <span id="btn-test-text">Test Connection</span>
                        <span id="btn-test-spinner" class="hidden">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>

                <div id="test-result-box" class="hidden mt-3 p-3 rounded-lg text-xs font-medium"></div>

                <p class="text-[11px] text-slate-500 mt-2">
                    API key digunakan untuk fitur ekstraksi CER. Dapatkan API key di 
                    <a href="https://aistudio.google.com/" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 underline font-medium">Google AI Studio</a>. 
                    Tersimpan terenkripsi khusus akun Anda (<strong>{{ auth()->user()->username }}</strong>).
                </p>
            </div>

            <div class="p-3.5 rounded-lg bg-indigo-50/70 border border-indigo-100 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-xs text-indigo-900 leading-relaxed">
                    Fitur AI Extractor di CER Studio memanfaatkan model Gemini untuk menganalisis teks bacaan dan menghasilkan usulan klaim, bukti, dan penalaran secara terstruktur.
                </p>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                Simpan Perubahan
            </button>
            <a href="{{ route('admin.dashboard') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700 transition">
                Batal
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const testBtn = document.getElementById('btn-test-connection');
    const testText = document.getElementById('btn-test-text');
    const testSpinner = document.getElementById('btn-test-spinner');
    const resultBox = document.getElementById('test-result-box');
    const keyInput = document.getElementById('gemini_api_key');
    const toggleBtn = document.getElementById('toggle-key-visibility');

    if (toggleBtn && keyInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = keyInput.type === 'password';
            keyInput.type = isPassword ? 'text' : 'password';
        });
    }

    if (!testBtn) return;

    testBtn.addEventListener('click', async () => {
        const apiKey = keyInput.value.trim();
        if (!apiKey) {
            resultBox.className = 'mt-3 p-3 rounded-lg text-xs font-medium bg-rose-50 border border-rose-200 text-rose-700';
            resultBox.innerText = 'API Key tidak boleh kosong.';
            resultBox.classList.remove('hidden');
            return;
        }

        testBtn.disabled = true;
        testText.innerText = 'Testing...';
        testSpinner.classList.remove('hidden');
        resultBox.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('{{ route('api.ai.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ gemini_api_key: apiKey })
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                resultBox.className = 'mt-3 p-3 rounded-lg text-xs font-medium bg-emerald-50 border border-emerald-200 text-emerald-800';
                resultBox.innerText = '✓ ' + (data.message || 'Koneksi berhasil! API Key valid.');
                Toast.success('Koneksi Gemini API berhasil!');
            } else {
                resultBox.className = 'mt-3 p-3 rounded-lg text-xs font-medium bg-rose-50 border border-rose-200 text-rose-700';
                resultBox.innerText = '✗ ' + (data.message || 'Koneksi gagal.');
                Toast.error(data.message || 'Koneksi gagal.');
            }
            resultBox.classList.remove('hidden');
        } catch (err) {
            resultBox.className = 'mt-3 p-3 rounded-lg text-xs font-medium bg-rose-50 border border-rose-200 text-rose-700';
            resultBox.innerText = '✗ Gagal menghubungi server: ' + err.message;
            resultBox.classList.remove('hidden');
            Toast.error('Gagal menghubungi server.');
        } finally {
            testBtn.disabled = false;
            testText.innerText = 'Test Connection';
            testSpinner.classList.add('hidden');
        }
    });
});
</script>
@endpush
@endsection
