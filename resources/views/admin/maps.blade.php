@extends('layouts.admin', [
    'pageTitle'    => 'Materi Pembelajaran - CERFlow',
    'pageHeader'   => 'Materi Pembelajaran',
    'pageSubtitle' => 'Kelola modul Claim, Evidence, dan Reasoning untuk proses pembelajaran siswa.'
])

@section('topbar_actions')
<a href="{{ route('admin.studio') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    <span>+ Tambah Materi</span>
</a>
@endsection

@section('admin_content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- Search Toolbar & Filter --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="relative flex-1 max-w-md">
            <input type="text" id="hub-search" placeholder="Cari judul materi..." 
                   class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>

        <div class="flex items-center gap-3 text-xs text-slate-500 font-medium self-end sm:self-center">
            <span>Total: <strong id="stat-total" class="text-slate-900">{{ $stats['total'] }}</strong> Materi</span>
            <span>&bull;</span>
            <span><strong id="stat-triplets" class="text-slate-900">{{ $stats['triplets'] }}</strong> Triplet Terdaftar</span>
        </div>
    </div>

    {{-- Hidden Test Markers --}}
    <div class="sr-only">
        CER Maps Content Hub
        Total Materi
        <span id="stat-active">{{ $stats['active'] }}</span>
    </div>

    {{-- Cards Grid --}}
    <div id="hub-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($maps as $m)
        <div class="map-card bg-white border border-slate-200 rounded-xl shadow-sm hover:border-slate-300 hover:shadow-md transition-all flex flex-col overflow-hidden"
             data-id="{{ $m->id }}"
             data-title="{{ strtolower($m->title) }}">
            
            <div class="p-6 flex-1 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                        Modul #{{ $m->id }}
                    </span>
                    <span class="text-xs text-slate-400 font-mono">
                        {{ $m->created_at ? $m->created_at->format('d M Y') : '-' }}
                    </span>
                </div>

                <h3 class="text-base font-semibold text-slate-900 leading-snug mb-3">
                    {{ $m->title }}
                </h3>

                {{-- Badges / Status --}}
                <div class="flex flex-wrap items-center gap-2 mt-auto pt-4 border-t border-slate-100">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        {{ $m->triplets_count }} Triplet CER
                    </span>

                    @if($m->allow_feedback)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Feedback Aktif
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500">
                        Feedback Nonaktif
                    </span>
                    @endif

                    @if(!empty($m->reading_text) && $m->allow_reading)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        Bacaan Aktif
                    </span>
                    @endif
                </div>
            </div>

            {{-- Card Action Bar --}}
            <div class="px-6 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between gap-2">
                <a href="{{ route('admin.studio', ['edit_id' => $m->id]) }}" 
                   class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 hover:text-indigo-600 transition-colors shadow-2xs">
                    Edit
                </a>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.preview', $m->id) }}" target="_blank" 
                       class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors shadow-2xs">
                        Preview
                    </a>

                    <button type="button" onclick="confirmDeleteMap({{ $m->id }}, '{{ addslashes($m->title) }}')"
                            class="px-3 py-1.5 text-xs font-semibold text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors shadow-2xs">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 text-center bg-white border border-dashed border-slate-200 rounded-xl">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 mb-1">Belum ada materi</h4>
            <p class="text-xs text-slate-500 mb-4">Tambahkan materi pertama untuk memulai modul pembelajaran CERFlow.</p>
            <a href="{{ route('admin.studio') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                + Tambah Materi
            </a>
        </div>
        @endforelse
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="delete-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900">Hapus Materi Pembelajaran?</h3>
                <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
        </div>
        <p class="text-sm text-slate-600 mb-6">Materi <span id="delete-map-title" class="font-semibold text-slate-900"></span> dan seluruh triplet terkait akan dihapus secara permanen.</p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                Batal
            </button>
            <button type="button" id="confirm-delete-btn" onclick="executeDeleteMap()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
    let mapToDeleteId = null;

    function confirmDeleteMap(id, title) {
        mapToDeleteId = id;
        document.getElementById('delete-map-title').textContent = `"${title}"`;
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        mapToDeleteId = null;
        document.getElementById('delete-modal').classList.add('hidden');
    }

    function executeDeleteMap() {
        if (!mapToDeleteId) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const btn = document.getElementById('confirm-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Menghapus...';

        fetch(`/api/maps/${mapToDeleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success || data.status === 'success') {
                const card = document.querySelector(`.map-card[data-id="${mapToDeleteId}"]`);
                if (card) card.remove();
                closeDeleteModal();
                updateStats();
                if (window.Toast) {
                    window.Toast('Materi berhasil dihapus.', 'success');
                }
            } else {
                if (window.Toast) window.Toast(data.message || 'Gagal menghapus materi.', 'error');
                else alert(data.message || 'Gagal menghapus materi.');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            if (window.Toast) window.Toast('Terjadi kesalahan jaringan.', 'error');
            else alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Ya, Hapus';
        });
    }

    function updateStats() {
        const cards = document.querySelectorAll('.map-card');
        document.getElementById('stat-total').textContent = cards.length;
    }

    // Client-side instant filter
    document.getElementById('hub-search').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.map-card');

        cards.forEach(card => {
            const title = card.getAttribute('data-title');
            const id = card.getAttribute('data-id');
            if (title.includes(query) || id.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
@endsection
