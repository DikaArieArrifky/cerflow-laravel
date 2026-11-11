@extends('layouts.admin', [
    'pageTitle' => 'Laporan Nilai',
    'pageSubtitle' => 'Laporan hasil belajar dan analisis perolehan skor mahasiswa',
    'navContext' => 'report'
])

@section('topbar_actions')
    <button type="button" onclick="exportToExcel()" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <span>Export to Excel</span>
    </button>
@endsection

@section('admin_content')
<div class="space-y-6">
    <span class="sr-only">Performance Analytics</span>

    <!-- Horizontal Filter Toolbar -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label for="filter-student" class="block text-xs font-semibold text-slate-600 mb-1.5">Filter Nama Siswa</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" id="filter-student" placeholder="Cari nama / username..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition">
                </div>
            </div>

            <div>
                <label for="filter-module" class="block text-xs font-semibold text-slate-600 mb-1.5">Filter Materi</label>
                <select id="filter-module" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition">
                    <option value="">Semua Materi</option>
                    @foreach ($uniqueModules as $mod)
                        <option value="{{ $mod }}">{{ $mod }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-id" class="block text-xs font-semibold text-slate-600 mb-1.5">Map / Session ID</label>
                <input type="text" id="filter-id" placeholder="Contoh: 12 atau S-5" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition">
            </div>

            <div>
                <label for="filter-date" class="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Submit</label>
                <input type="date" id="filter-date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent transition">
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="applyFilters()" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    Terapkan
                </button>
                <button type="button" onclick="resetFilters()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-lg transition" title="Reset Filter">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Reports Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap" id="reports-table">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <th class="py-3.5 px-4 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(0)">
                            <div class="flex items-center gap-1.5">
                                <span>Map ID</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-4 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(1)">
                            <div class="flex items-center gap-1.5">
                                <span>Session ID</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-4 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(2)">
                            <div class="flex items-center gap-1.5">
                                <span>Mahasiswa</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-4 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(3)">
                            <div class="flex items-center gap-1.5">
                                <span>Materi Pembelajaran</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-4 text-center cursor-pointer hover:text-primary-600 transition" onclick="sortTable(4)">
                            <div class="flex items-center justify-center gap-1.5">
                                <span>Skor</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-4 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(5)">
                            <div class="flex items-center gap-1.5">
                                <span>Waktu Submit</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm" id="table-body">
                    @forelse($reports as $r)
                        <tr class="hover:bg-slate-50/75 transition group" 
                            data-date="{{ $r->submitted_at ? $r->submitted_at->format('Y-m-d') : '' }}"
                            data-full-date="{{ $r->submitted_at ? $r->submitted_at->toIso8601String() : '' }}">
                            <td class="py-3 px-4 font-mono font-medium text-primary-600">
                                #{{ $r->map_id }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-500">
                                {{ $r->session_id ? '#S-' . $r->session_id : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-800">{{ $r->user?->namalengkap ?? '-' }}</span>
                                    <span class="text-xs text-slate-400">User: {{ $r->user?->username ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ $r->cerMap?->title ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @php
                                    $scoreVal = (float) $r->score;
                                    $badgeClass = $scoreVal >= 80 
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                        : ($scoreVal >= 60 
                                            ? 'bg-amber-50 text-amber-700 border-amber-200' 
                                            : 'bg-rose-50 text-rose-700 border-rose-200');
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                    {{ $scoreVal }}%
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                {{ $r->submitted_at ? $r->submitted_at->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5 justify-center">
                                    @if (!empty($r->map_data))
                                        <a href="{{ route('map.view', $r->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Lihat Map
                                        </a>
                                    @endif
                                    @if ($r->session_id)
                                        <a href="{{ route('admin.logs', ['materi' => $r->cerMap?->title, 'siswa' => $r->user?->username]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            Lihat Log
                                        </a>
                                    @else
                                        @if (empty($r->map_data))
                                            <span class="text-slate-300 text-xs font-medium">N/A</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="max-w-xs mx-auto flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-800 mb-1">Belum Ada Data Skor</h4>
                                    <p class="text-xs text-slate-500">Hasil penilaian rekonstruksi CER mahasiswa akan ditampilkan di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script>
    const tableBody = document.getElementById('table-body');
    const rows = Array.from(tableBody.getElementsByTagName('tr'));
    
    // Filtering Logic
    const studentInput = document.getElementById('filter-student');
    const moduleInput = document.getElementById('filter-module');
    const idInput = document.getElementById('filter-id');
    const dateInput = document.getElementById('filter-date');

    [studentInput, idInput, dateInput, moduleInput].forEach(input => {
        if (!input) return;
        input.addEventListener('change', applyFilters);
        input.addEventListener('input', applyFilters);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') applyFilters();
        });
    });

    function applyFilters() {
        const studentVal = studentInput.value.toLowerCase().trim();
        const moduleVal = moduleInput.value.toLowerCase().trim();
        const idVal = idInput.value.toLowerCase().trim();
        const dateVal = dateInput.value;

        rows.forEach(row => {
            if (row.children.length < 5) return;
            const idText = row.children[0].innerText.toLowerCase();
            const sessionText = row.children[1].innerText.toLowerCase();
            const studentText = row.children[2].innerText.toLowerCase();
            const moduleText = row.children[3].innerText.toLowerCase();
            const dateText = row.getAttribute('data-date'); 

            const matchesStudent = !studentVal || studentText.includes(studentVal);
            const matchesModule = !moduleVal || moduleText.includes(moduleVal);
            const matchesId = !idVal || idText.includes(idVal) || sessionText.includes(idVal);
            const matchesDate = !dateVal || dateText === dateVal;

            row.style.display = (matchesStudent && matchesModule && matchesId && matchesDate) ? '' : 'none';
        });
    }

    function resetFilters() {
        if (studentInput) studentInput.value = '';
        if (moduleInput) moduleInput.value = '';
        if (idInput) idInput.value = '';
        if (dateInput) dateInput.value = '';
        applyFilters();
    }

    // Sorting Logic
    let sortDirection = 1;
    function sortTable(columnIndex) {
        sortDirection *= -1;
        const sortedRows = rows.filter(r => r.children.length >= 5).sort((a, b) => {
            let aText = a.children[columnIndex].innerText.trim();
            let bText = b.children[columnIndex].innerText.trim();

            if (columnIndex === 0 || columnIndex === 1) {
                return (parseFloat(aText.replace(/[^0-9.]/g, '') || 0) - parseFloat(bText.replace(/[^0-9.]/g, '') || 0)) * sortDirection;
            }

            if (columnIndex === 4) {
                return (parseFloat(aText.replace(/[^0-9.]/g, '') || 0) - parseFloat(bText.replace(/[^0-9.]/g, '') || 0)) * sortDirection;
            }
            
            if (columnIndex === 5) {
                return (new Date(a.getAttribute('data-full-date')) - new Date(b.getAttribute('data-full-date'))) * sortDirection;
            }

            return aText.localeCompare(bText) * sortDirection;
        });

        tableBody.innerHTML = '';
        sortedRows.forEach(row => tableBody.appendChild(row));
    }

    function exportToExcel() {
        const data = [];
        data.push(['Map ID', 'Session ID', 'Materi', 'Username', 'Nama Lengkap', 'Skor (%)', 'Waktu Submit']);
        
        rows.forEach(row => {
            if (row.style.display === 'none') return;
            if (row.children.length < 5) return;

            const mapId = row.children[0].innerText.trim().replace('#', '');
            const sessionId = row.children[1].innerText.trim().replace('#S-', '');
            
            const namaLengkapEl = row.children[2].querySelector('span.text-slate-800, span.font-medium');
            const usernameEl = row.children[2].querySelector('span.text-slate-400');
            const namaLengkap = namaLengkapEl ? namaLengkapEl.innerText.trim() : '';
            const username = usernameEl ? usernameEl.innerText.replace(/user:\s*/i, '').trim() : '';
            
            const materi = row.children[3].innerText.trim();
            const skor = row.children[4].innerText.trim().replace('%', '');
            const waktuSubmit = row.children[5].innerText.trim();

            data.push([mapId, sessionId, materi, username, namaLengkap, skor, waktuSubmit]);
        });

        if (data.length <= 1) {
            Toast.warning('Tidak ada data yang tersedia untuk diekspor (cek filter Anda).');
            return;
        }

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Reports");

        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
        XLSX.writeFile(wb, `CERFlow_Report_${timestamp}.xlsx`);
    }
</script>
@endsection
