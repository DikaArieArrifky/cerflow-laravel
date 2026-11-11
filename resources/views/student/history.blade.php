@extends('layouts.student', [
    'pageTitle' => 'Hasil Sebelumnya',
    'navContext' => 'history'
])

@section('student_content')
<div class="max-w-6xl w-full mx-auto px-6 py-8">
    <div class="mb-8">
        <span class="sr-only">Riwayat Pembelajaran</span>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
            Hasil <span class="text-primary-600">Sebelumnya</span>
        </h2>
        <p class="text-sm text-slate-500 mt-1">
            Riwayat dan perolehan skor rekonstruksi CER yang telah Anda selesaikan.
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap" id="history-table">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <th class="py-3.5 px-5 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(0)">
                            <div class="flex items-center gap-1.5">
                                <span>Sesi</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-5 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(1)">
                            <div class="flex items-center gap-1.5">
                                <span>Materi Pembelajaran</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-5 cursor-pointer hover:text-primary-600 transition" onclick="sortTable(2)">
                            <div class="flex items-center gap-1.5">
                                <span>Waktu Submit</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-5 text-center cursor-pointer hover:text-primary-600 transition" onclick="sortTable(3)">
                            <div class="flex items-center justify-center gap-1.5">
                                <span>Skor</span>
                                <span class="text-slate-400">↕</span>
                            </div>
                        </th>
                        <th class="py-3.5 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm" id="table-body">
                    @forelse($scores as $s)
                        <tr class="hover:bg-slate-50/75 transition group" data-full-date="{{ $s->submitted_at ? $s->submitted_at->toIso8601String() : '' }}">
                            <td class="py-3.5 px-5 font-mono text-xs font-medium text-slate-500">
                                {{ $s->session_id ? '#S-' . $s->session_id : '-' }}
                            </td>
                            <td class="py-3.5 px-5 font-medium text-slate-800">
                                {{ $s->cerMap?->title ?? '-' }}
                            </td>
                            <td class="py-3.5 px-5 text-xs text-slate-500">
                                {{ $s->submitted_at ? $s->submitted_at->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                @php
                                    $scoreVal = (float) $s->score;
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
                            <td class="py-3.5 px-5 text-center">
                                @if (!empty($s->map_data))
                                    <a href="{{ route('map.view', $s->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 transition shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Lihat Map
                                    </a>
                                @else
                                    <span class="text-slate-300 text-xs">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="max-w-xs mx-auto flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-800 mb-1">Belum Ada Riwayat</h4>
                                    <p class="text-xs text-slate-500 mb-5">Anda belum menyelesaikan pengerjaan modul pembelajaran apapun.</p>
                                    <a href="{{ route('student.index') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                        Mulai Belajar Sekarang
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const tableBody = document.getElementById('table-body');
    const rows = Array.from(tableBody.getElementsByTagName('tr'));
    
    let sortDirection = 1;
    function sortTable(columnIndex) {
        if(rows.length === 0 || rows[0].children.length < 5) return;
        
        sortDirection *= -1;
        const sortedRows = rows.filter(r => r.children.length >= 5).sort((a, b) => {
            let aText = a.children[columnIndex].innerText.trim();
            let bText = b.children[columnIndex].innerText.trim();

            if (columnIndex === 0 || columnIndex === 3) {
                let numA = parseFloat(aText.replace(/[^0-9.-]+/g, "") || 0);
                let numB = parseFloat(bText.replace(/[^0-9.-]+/g, "") || 0);
                return (numA - numB) * sortDirection;
            }
            
            if (columnIndex === 2) {
                let dateA = new Date(a.getAttribute('data-full-date')).getTime();
                let dateB = new Date(b.getAttribute('data-full-date')).getTime();
                return (dateA - dateB) * sortDirection;
            }

            return aText.localeCompare(bText) * sortDirection;
        });

        tableBody.innerHTML = '';
        sortedRows.forEach(row => tableBody.appendChild(row));
    }
</script>
@endsection
