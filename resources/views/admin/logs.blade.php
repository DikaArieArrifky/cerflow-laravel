@extends('layouts.admin', [
    'pageTitle'    => 'Log Aktivitas - CERFlow',
    'pageHeader'   => 'Log Aktivitas',
    'pageSubtitle' => 'Aktivitas mahasiswa selama proses rekonstruksi CER.'
])

@section('topbar_actions')
<button type="button" onclick="exportToExcel()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    <span>Export to Excel</span>
</button>
@endsection

@section('admin_content')
<div class="max-w-7xl mx-auto space-y-6">
    <span class="sr-only">Interaction Log Analytics</span>

    {{-- Single Horizontal Filter Bar --}}
    <form method="GET" action="{{ route('admin.logs') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[180px]">
            <label for="filter-materi" class="sr-only">Filter Materi</label>
            <select name="materi" id="filter-materi" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 font-medium text-slate-700">
                <option value="">Semua Materi</option>
                @foreach($loggedMaps as $map)
                    <option value="{{ $map->title }}" {{ $filters['materi'] === $map->title ? 'selected' : '' }}>
                        {{ $map->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[180px]">
            <label for="filter-siswa" class="sr-only">Filter Siswa</label>
            <input type="text" id="filter-siswa" name="siswa" value="{{ $filters['siswa'] }}" placeholder="Cari nama / username..." 
                   class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-slate-700">
        </div>

        <div class="w-40">
            <label class="sr-only">Waktu Mulai</label>
            <input type="date" name="start" value="{{ $filters['start'] }}" 
                   class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-slate-700">
        </div>

        <div class="w-40">
            <label class="sr-only">Waktu Selesai</label>
            <input type="date" name="end" value="{{ $filters['end'] }}" 
                   class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-slate-700">
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
                Filter
            </button>
            <a href="{{ route('admin.logs') }}" class="px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Reset
            </a>
        </div>
    </form>

    {{-- Data Table --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="logs-table" class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3 w-16">Sesi</th>
                        <th class="py-3 px-3">Mahasiswa</th>
                        <th class="py-3 px-3">Materi</th>
                        <th class="py-3 px-3">Mulai</th>
                        <th class="py-3 px-3">Durasi</th>
                        <th class="py-3 px-3 text-center">Score</th>
                        <th class="py-3 px-3 text-center">Total</th>
                        <th class="py-3 px-3 text-center">Connect</th>
                        <th class="py-3 px-3 text-center">Disconnect</th>
                        <th class="py-3 px-3 text-center">Move</th>
                        <th class="py-3 px-3 text-center bg-indigo-50/50">Move Dist.</th>
                        <th class="py-3 px-3 text-center">Arrange</th>
                        <th class="py-3 px-3 text-center">Feedback</th>
                        <th class="py-3 px-3 text-center">Reading</th>
                        <th class="py-3 px-3 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($sessionAnalytics as $item)
                        @php
                            $s = $item['session'];
                            $a = $item['analytics'];
                            $start = $s->start_time ? \Carbon\Carbon::parse($s->start_time) : null;
                            $duration = $a['completion_time_seconds'] !== null ? gmdate('H:i:s', $a['completion_time_seconds']) : '-';
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            data-student="{{ $a['student_name'] }}"
                            data-username="{{ $a['username'] }}"
                            data-map="{{ $a['map_title'] }}"
                            data-score="{{ $a['score'] ?? '' }}"
                            data-duration="{{ $a['completion_time_seconds'] ?? '' }}"
                            data-total="{{ $a['total_events'] }}"
                            data-connect="{{ $a['connect_count'] }}"
                            data-disconnect="{{ $a['disconnect_count'] }}"
                            data-move="{{ $a['move_count'] }}"
                            data-distance="{{ $a['total_move_distance'] }}"
                            data-unique="{{ $a['unique_moved_cards'] }}"
                            data-claim="{{ $a['claim_move_count'] }}"
                            data-evidence="{{ $a['evidence_move_count'] }}"
                            data-reasoning="{{ $a['reasoning_move_count'] }}"
                            data-arrange="{{ $a['auto_arrange_count'] }}"
                            data-feedback="{{ $a['feedback_count'] }}"
                            data-reading="{{ $a['reading_count'] }}"
                            data-reading-duration="{{ $a['reading_duration_seconds'] }}">
                            <td class="py-3 px-3 font-mono font-medium text-indigo-600">#S-{{ $s->id }}</td>
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-900">{{ $s->user?->namalengkap ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ '@' . ($s->user?->username ?? '-') }}</div>
                            </td>
                            <td class="py-3 px-3 max-w-[200px] truncate" title="{{ $s->cerMap?->title }}">
                                {{ $s->cerMap?->title ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-slate-500 font-mono">{{ $start ? $start->format('H:i:s') : '-' }}</td>
                            <td class="py-3 px-3 text-slate-500 font-mono">{{ $duration }}</td>
                            <td class="py-3 px-3 text-center">
                                @if($a['score'] !== null)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $a['score'] >= 80 ? 'bg-emerald-50 text-emerald-700' : ($a['score'] >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                                        {{ (float)$a['score'] }}%
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center font-semibold text-slate-800">{{ $a['total_events'] }}</td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $a['connect_count'] }}</td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $a['disconnect_count'] }}</td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $a['move_count'] }}</td>
                            <td class="py-3 px-3 text-center font-semibold text-indigo-700 bg-indigo-50/40">{{ $a['total_move_distance'] }}</td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $a['auto_arrange_count'] }}</td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $a['feedback_count'] }}</td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $a['reading_count'] }}</td>
                            <td class="py-3 px-3 text-center">
                                <a href="{{ route('admin.logs.show', $s->id) }}" 
                                   class="px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="py-12 text-center text-slate-400">
                                Belum ada aktivitas mahasiswa yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    function exportToExcel() {
        const rows = document.querySelectorAll('#logs-table tbody tr');
        if (rows.length === 0 || rows[0].children.length === 1) {
            alert('Tidak ada data untuk diekspor.');
            return;
        }

        const data = [
            [
                'Student Name',
                'Username',
                'Map Title',
                'Score',
                'Completion Time (s)',
                'Total Events',
                'Connect Count',
                'Disconnect Count',
                'Move Count',
                'Move Distance',
                'Unique Moved Cards',
                'Claim Move Count',
                'Evidence Move Count',
                'Reasoning Move Count',
                'Auto Arrange Count',
                'Feedback Count',
                'Reading Count',
                'Reading Duration (s)'
            ]
        ];

        rows.forEach(r => {
            if (!r.dataset.student) return;
            data.push([
                r.dataset.student || '',
                r.dataset.username || '',
                r.dataset.map || '',
                r.dataset.score ? parseFloat(r.dataset.score) : 0,
                r.dataset.duration ? parseInt(r.dataset.duration) : 0,
                parseInt(r.dataset.total) || 0,
                parseInt(r.dataset.connect) || 0,
                parseInt(r.dataset.disconnect) || 0,
                parseInt(r.dataset.move) || 0,
                parseInt(r.dataset.distance) || 0,
                parseInt(r.dataset.unique) || 0,
                parseInt(r.dataset.claim) || 0,
                parseInt(r.dataset.evidence) || 0,
                parseInt(r.dataset.reasoning) || 0,
                parseInt(r.dataset.arrange) || 0,
                parseInt(r.dataset.feedback) || 0,
                parseInt(r.dataset.reading) || 0,
                parseInt(r.dataset.readingDuration) || 0
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Research Analytics");
        XLSX.writeFile(wb, "CERFlow_Research_Analytics_" + new Date().toISOString().slice(0,10) + ".xlsx");
    }
</script>
@endsection
