@extends('layouts.admin', [
    'pageTitle' => 'Analisis Clustering K-Means',
    'pageSubtitle' => 'Pengelompokan pola perilaku rekonstruksi CER dan korelasinya terhadap hasil belajar (K=3)',
    'navContext' => 'clustering'
])

@section('topbar_actions')
    @if($selectedMapId)
        <a href="{{ route('admin.clustering.export', ['map_id' => $selectedMapId]) }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Export Dataset (CSV)</span>
        </a>
    @endif
    <button type="button" onclick="openTestScoreModal()" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        <span>Kelola Nilai Pre/Post-Test</span>
    </button>
@endsection

@section('admin_content')
<div class="space-y-6">

    {{-- Notification Alerts --}}
    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.clustering') }}" class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex-1 w-full sm:w-auto flex flex-col sm:flex-row items-center gap-3">
                <label for="map_id" class="text-xs font-semibold text-slate-700 shrink-0">Pilih Materi CER:</label>
                <select name="map_id" id="map_id" class="w-full sm:w-80 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition">
                    @forelse($maps as $m)
                        <option value="{{ $m->id }}" {{ $selectedMapId == $m->id ? 'selected' : '' }}>
                            Modul #{{ $m->id }} - {{ $m->title }}
                        </option>
                    @empty
                        <option value="">Belum ada materi</option>
                    @endforelse
                </select>
                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Proses Clustering (K=3)</span>
                </button>
            </div>

            @if($selectedMapId)
                <div class="text-xs text-slate-500 font-mono">
                    Total Mahasiswa: <strong class="text-slate-900">{{ $clusteringResult['total_students'] ?? 0 }}</strong>
                </div>
            @endif
        </form>
    </div>

    @if(!$selectedMapId || empty($clusteringResult['students']))
        {{-- Empty State --}}
        <div class="py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
            <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-800">Belum Ada Data Pengerjaan Mahasiswa</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Silakan pilih materi lain yang telah dikerjakan oleh mahasiswa, atau minta mahasiswa mengerjakan materi CER terlebih dahulu untuk menghasilkan data log aktivitas.
            </p>
        </div>
    @else

        {{-- Research Correlation Insight Banner --}}
        @if(!empty($clusteringResult['insights']['summary']))
            <div class="p-5 rounded-xl bg-gradient-to-r from-indigo-50 via-white to-indigo-50/50 border border-indigo-200 shadow-xs flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-sm font-bold text-slate-900">Temuan Korelasi Pola Belajar (Research Hypothesis)</h4>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-700">K-Means K=3</span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-700">
                        {{ $clusteringResult['insights']['summary'] }}
                    </p>
                </div>
            </div>
        @endif

        {{-- 3 Cluster Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($clusteringResult['clusters'] as $cluster)
                @php
                    $isTop = $cluster['cluster_id'] === 1;
                    $isMid = $cluster['cluster_id'] === 2;
                    $colorBg = $isTop ? 'border-t-emerald-500' : ($isMid ? 'border-t-blue-500' : 'border-t-amber-500');
                    $badgeBg = $isTop ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($isMid ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200');
                    $iconColor = $isTop ? 'text-emerald-600' : ($isMid ? 'text-blue-600' : 'text-amber-600');
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 border-t-4 {{ $colorBg }} p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $badgeBg }}">
                                {{ $cluster['cluster_label'] }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">
                                {{ $cluster['member_count'] }} Mahasiswa ({{ round(($cluster['member_count'] / max(1, $clusteringResult['total_students'])) * 100) }}%)
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                            <span>{{ $cluster['tier_name'] }}</span>
                        </h3>
                        <p class="text-xs text-slate-500 mb-5">Karakteristik pengerjaan peta CER</p>

                        {{-- Academic Scores Metric Box --}}
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-3 mb-5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-600">Rata-rata Nilai Map CER:</span>
                                <span class="text-lg font-black text-slate-900 font-mono">{{ $cluster['avg_map_score'] }}%</span>
                            </div>
                            <div class="h-px bg-slate-200"></div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-semibold">Pre-Test</div>
                                    <div class="text-xs font-bold text-slate-800 font-mono">{{ $cluster['avg_pre_test'] ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-semibold">Post-Test</div>
                                    <div class="text-xs font-bold text-slate-800 font-mono">{{ $cluster['avg_post_test'] ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-semibold">Gain / Selisih</div>
                                    <div class="text-xs font-bold font-mono {{ ($cluster['avg_score_diff'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $cluster['avg_score_diff'] !== null ? ($cluster['avg_score_diff'] > 0 ? '+'.$cluster['avg_score_diff'] : $cluster['avg_score_diff']) : '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Activity Behavior Indicators --}}
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Buka Bacaan:</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ $cluster['avg_open_material_count'] }}x</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Total Durasi Baca:</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ $cluster['avg_total_reading_duration'] }}s ({{ gmdate("i:s", (int)$cluster['avg_total_reading_duration']) }})</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Connect / Disconnect:</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ $cluster['avg_connect_count'] }} / {{ $cluster['avg_disconnect_count'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Perpindahan Kartu (Move):</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ $cluster['avg_move_count'] }}x</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Durasi Selesai:</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ gmdate("i:s", (int)$cluster['avg_completion_duration']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Visualizations (Chart.js) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Chart 1: Academic Scores Comparison --}}
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-bold text-slate-900">Perbandingan Nilai Map CER vs Pre/Post-Test</h4>
                        <span class="text-xs text-slate-400 font-mono">Bar Chart</span>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">Evaluasi korelasi kemampuan rekonstruksi CER terhadap hasil belajar</p>
                </div>
                <div class="h-64 relative">
                    <canvas id="chart-scores-comparison"></canvas>
                </div>
            </div>

            {{-- Chart 2: Activity Behavior Comparison --}}
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-bold text-slate-900">Distribusi Perilaku Belajar Antar Cluster</h4>
                        <span class="text-xs text-slate-400 font-mono">Radar / Feature Profile</span>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">Pola interaksi (Buka Bacaan, Waktu Baca, Connect, Move, Aksi)</p>
                </div>
                <div class="h-64 relative">
                    <canvas id="chart-behavior-radar"></canvas>
                </div>
            </div>
        </div>

        {{-- Detail Table of Cluster Members --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tabel Detail Anggota per Cluster</h3>
                    <p class="text-xs text-slate-500">Rincian aktivitas 7 dimensi, Nilai Map CER, Pre-test, dan Post-test</p>
                </div>

                {{-- Cluster Filter Tabs --}}
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-lg text-xs font-semibold">
                    <button type="button" onclick="filterClusterTable('all')" class="tab-btn px-3 py-1.5 rounded-md transition bg-white text-indigo-700 shadow-xs" data-target="all">
                        Semua ({{ count($clusteringResult['students']) }})
                    </button>
                    @foreach($clusteringResult['clusters'] as $cl)
                        <button type="button" onclick="filterClusterTable('{{ $cl['cluster_id'] }}')" class="tab-btn px-3 py-1.5 rounded-md transition text-slate-600 hover:text-slate-900" data-target="{{ $cl['cluster_id'] }}">
                            {{ $cl['cluster_label'] }} ({{ $cl['member_count'] }})
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap text-xs" id="students-table">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 font-semibold text-slate-600">
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Mahasiswa</th>
                            <th class="py-3 px-4 text-center">Cluster</th>
                            <th class="py-3 px-4 text-right">Nilai Map CER</th>
                            <th class="py-3 px-4 text-right">Pre-Test</th>
                            <th class="py-3 px-4 text-right">Post-Test</th>
                            <th class="py-3 px-4 text-right">Gain (Selisih)</th>
                            <th class="py-3 px-4 text-right">Buka Bacaan</th>
                            <th class="py-3 px-4 text-right">Durasi Baca</th>
                            <th class="py-3 px-4 text-right">Connect</th>
                            <th class="py-3 px-4 text-right">Disconnect</th>
                            <th class="py-3 px-4 text-right">Move</th>
                            <th class="py-3 px-4 text-right">Durasi Selesai</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($clusteringResult['students'] as $idx => $student)
                            @php
                                $badgeStyle = match ($student['cluster_id'] ?? 0) {
                                    1 => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    2 => 'bg-blue-50 text-blue-700 border-blue-200',
                                    3 => 'bg-amber-50 text-amber-700 border-amber-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition cluster-row" data-cluster="{{ $student['cluster_id'] }}">
                                <td class="py-3 px-4 text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-900">{{ $student['student_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ '@' . $student['username'] }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold border text-[11px] {{ $badgeStyle }}">
                                        {{ $student['cluster_label'] ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    {{ $student['map_score'] !== null ? $student['map_score'].'%' : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    {{ $student['pre_test'] !== null ? $student['pre_test'] : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    {{ $student['post_test'] !== null ? $student['post_test'] : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-semibold {{ ($student['score_diff'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $student['score_diff'] !== null ? ($student['score_diff'] > 0 ? '+'.$student['score_diff'] : $student['score_diff']) : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono">{{ (int)($student['raw_features']['open_material_count'] ?? 0) }}x</td>
                                <td class="py-3 px-4 text-right font-mono">{{ (int)($student['raw_features']['total_reading_duration'] ?? 0) }}s</td>
                                <td class="py-3 px-4 text-right font-mono">{{ (int)($student['raw_features']['connect_count'] ?? 0) }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ (int)($student['raw_features']['disconnect_count'] ?? 0) }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ (int)($student['raw_features']['move_count'] ?? 0) }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ gmdate("i:s", (int)($student['raw_features']['completion_duration'] ?? 0)) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" 
                                            onclick="openQuickEdit({{ $student['user_id'] }}, '{{ addslashes($student['student_name']) }}', '{{ $student['pre_test'] ?? '' }}', '{{ $student['post_test'] ?? '' }}', '{{ $student['delayed_test'] ?? '' }}')" 
                                            class="p-1.5 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition" 
                                            title="Edit Nilai Pre/Post Test">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- =========================================================================
     MODAL KELOLA NILAI PRE-TEST & POST-TEST MAHASISWA
     ========================================================================= --}}
<div id="modal-test-scores" class="fixed inset-0 z-[200] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Kelola Nilai Pre-Test & Post-Test</h3>
                    <p class="text-xs text-slate-500">Materi: <strong>Modul #{{ $selectedMapId }}</strong></p>
                </div>
            </div>
            <button type="button" onclick="closeTestScoreModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- Modal Navigation Tabs --}}
        <div class="px-6 pt-3 bg-slate-50 border-b border-slate-200 flex gap-4 shrink-0">
            <button type="button" id="tab-btn-inline" onclick="switchModalTab('inline')" class="pb-3 text-xs font-semibold border-b-2 border-indigo-600 text-indigo-700 transition">
                Input / Edit Langsung
            </button>
            <button type="button" id="tab-btn-import" onclick="switchModalTab('import')" class="pb-3 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition">
                Import dari File CSV
            </button>
        </div>

        {{-- Tab 1: Inline Table Editor --}}
        <div id="tab-content-inline" class="flex-1 overflow-y-auto p-6 space-y-4">
            <p class="text-xs text-slate-500">
                Masukkan nilai Pre-Test dan Post-Test (skala 0 - 100). Sistem akan secara otomatis menghitung selisih nilai (Gain).
            </p>

            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600">
                            <th class="py-2.5 px-3">Mahasiswa</th>
                            <th class="py-2.5 px-3 text-center">Nilai Map CER</th>
                            <th class="py-2.5 px-3">Pre-Test</th>
                            <th class="py-2.5 px-3">Post-Test</th>
                            <th class="py-2.5 px-3">Delayed-Test (Opsional)</th>
                            <th class="py-2.5 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($studentListForModal as $s)
                            <tr id="row-student-{{ $s['user_id'] }}" class="hover:bg-slate-50">
                                <td class="py-2 px-3">
                                    <div class="font-semibold text-slate-800">{{ $s['namalengkap'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ '@' . $s['username'] }}</div>
                                </td>
                                <td class="py-2 px-3 text-center font-mono font-bold text-slate-700">
                                    {{ $s['map_score'] !== null ? $s['map_score'].'%' : '-' }}
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" step="0.01" min="0" max="100" 
                                           id="input-pre-{{ $s['user_id'] }}" 
                                           value="{{ $s['pre_test'] !== null ? $s['pre_test'] : '' }}" 
                                           placeholder="0-100"
                                           class="w-20 px-2 py-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-indigo-600 focus:outline-none">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" step="0.01" min="0" max="100" 
                                           id="input-post-{{ $s['user_id'] }}" 
                                           value="{{ $s['post_test'] !== null ? $s['post_test'] : '' }}" 
                                           placeholder="0-100"
                                           class="w-20 px-2 py-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-indigo-600 focus:outline-none">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" step="0.01" min="0" max="100" 
                                           id="input-delayed-{{ $s['user_id'] }}" 
                                           value="{{ $s['delayed_test'] !== null ? $s['delayed_test'] : '' }}" 
                                           placeholder="Opsional"
                                           class="w-20 px-2 py-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-indigo-600 focus:outline-none">
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <button type="button" 
                                            onclick="saveSingleTestScore({{ $s['user_id'] }})" 
                                            id="btn-save-{{ $s['user_id'] }}" 
                                            class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white rounded text-xs font-semibold transition">
                                        Simpan
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">Tidak ada data mahasiswa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 2: CSV Import Form --}}
        <div id="tab-content-import" class="flex-1 overflow-y-auto p-6 hidden space-y-6">
            <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-100 flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-xs text-slate-700 leading-relaxed">
                    Unggah file CSV dengan kolom header: <code>username, pre_test, post_test, delayed_test</code>.
                    Sistem akan mencocokkan mahasiswa berdasarkan username dan memperbarui nilainya untuk materi modul ini.
                </div>
            </div>

            <form method="POST" action="{{ route('admin.clustering.test-scores.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="cer_map_id" value="{{ $selectedMapId }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih File CSV:</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition border border-slate-200 rounded-lg p-2 bg-slate-50">
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                    <a href="{{ route('admin.clustering.test-scores.template') }}" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Download Template CSV</span>
                    </a>

                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"></path></svg>
                        <span>Unggah & Impor Nilai</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end shrink-0">
            <button type="button" onclick="closeTestScoreModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition">
                Selesai / Tutup
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const currentMapId = {{ $selectedMapId ?? 'null' }};
    const clustersData = @json($clusteringResult['clusters'] ?? []);

    // ─── Filter Cluster Table Rows ──────────────────────────────────────────────
    function filterClusterTable(clusterId) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if (btn.getAttribute('data-target') === String(clusterId)) {
                btn.className = 'tab-btn px-3 py-1.5 rounded-md transition bg-white text-indigo-700 shadow-xs font-semibold';
            } else {
                btn.className = 'tab-btn px-3 py-1.5 rounded-md transition text-slate-600 hover:text-slate-900 font-semibold';
            }
        });

        const rows = document.querySelectorAll('.cluster-row');
        rows.forEach(row => {
            if (clusterId === 'all' || row.getAttribute('data-cluster') === String(clusterId)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // ─── Modal Tab Switcher ─────────────────────────────────────────────────────
    function switchModalTab(tab) {
        const btnInline = document.getElementById('tab-btn-inline');
        const btnImport = document.getElementById('tab-btn-import');
        const contentInline = document.getElementById('tab-content-inline');
        const contentImport = document.getElementById('tab-content-import');

        if (tab === 'inline') {
            btnInline.className = 'pb-3 text-xs font-semibold border-b-2 border-indigo-600 text-indigo-700 transition';
            btnImport.className = 'pb-3 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition';
            contentInline.classList.remove('hidden');
            contentImport.classList.add('hidden');
        } else {
            btnImport.className = 'pb-3 text-xs font-semibold border-b-2 border-indigo-600 text-indigo-700 transition';
            btnInline.className = 'pb-3 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition';
            contentImport.classList.remove('hidden');
            contentInline.classList.add('hidden');
        }
    }

    function openTestScoreModal() {
        document.getElementById('modal-test-scores')?.classList.remove('hidden');
    }

    function closeTestScoreModal() {
        document.getElementById('modal-test-scores')?.classList.add('hidden');
    }

    function openQuickEdit(userId, name, pre, post, delayed) {
        openTestScoreModal();
        switchModalTab('inline');
        const row = document.getElementById(`row-student-${userId}`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('bg-indigo-50/70');
            setTimeout(() => row.classList.remove('bg-indigo-50/70'), 2000);
            document.getElementById(`input-pre-${userId}`)?.focus();
        }
    }

    // ─── AJAX Single Test Score Update ──────────────────────────────────────────
    async function saveSingleTestScore(userId) {
        if (!currentMapId) return;

        const pre = document.getElementById(`input-pre-${userId}`)?.value;
        const post = document.getElementById(`input-post-${userId}`)?.value;
        const delayed = document.getElementById(`input-delayed-${userId}`)?.value;
        const btn = document.getElementById(`btn-save-${userId}`);

        const originalText = btn ? btn.innerText : 'Simpan';
        if (btn) {
            btn.disabled = true;
            btn.innerText = '...';
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('admin.clustering.test-scores.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    user_id: userId,
                    cer_map_id: currentMapId,
                    pre_test: pre !== '' ? pre : null,
                    post_test: post !== '' ? post : null,
                    delayed_test: delayed !== '' ? delayed : null
                })
            });

            const data = await res.json();
            if (data.success) {
                if (btn) {
                    btn.innerText = 'Tersimpan ✓';
                    btn.classList.add('bg-emerald-600', 'text-white');
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerText = originalText;
                        btn.classList.remove('bg-emerald-600', 'text-white');
                    }, 2000);
                }
                if (window.Toast) {
                    Toast.success("Nilai berhasil disimpan.");
                }
            } else {
                alert("Gagal menyimpan: " + (data.message || 'Error'));
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            }
        } catch(err) {
            console.error("Save error:", err);
            alert("Terjadi kesalahan jaringan.");
            if (btn) {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }
    }

    // ─── Render Chart.js Visualizations ─────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        if (!clustersData || clustersData.length === 0) return;

        const clusterLabels = clustersData.map(c => `${c.cluster_label} (${c.tier_name})`);
        const mapScores = clustersData.map(c => c.avg_map_score);
        const preScores = clustersData.map(c => c.avg_pre_test ?? 0);
        const postScores = clustersData.map(c => c.avg_post_test ?? 0);
        const gainScores = clustersData.map(c => c.avg_score_diff ?? 0);

        // Chart 1: Bar Chart (Academic Scores)
        const ctxBar = document.getElementById('chart-scores-comparison')?.getContext('2d');
        if (ctxBar) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: clusterLabels,
                    datasets: [
                        {
                            label: 'Nilai Map CER (%)',
                            data: mapScores,
                            backgroundColor: 'rgba(79, 70, 229, 0.85)',
                            borderColor: '#4f46e5',
                            borderWidth: 1.5,
                            borderRadius: 6
                        },
                        {
                            label: 'Pre-Test',
                            data: preScores,
                            backgroundColor: 'rgba(148, 163, 184, 0.75)',
                            borderColor: '#94a3b8',
                            borderWidth: 1.5,
                            borderRadius: 6
                        },
                        {
                            label: 'Post-Test',
                            data: postScores,
                            backgroundColor: 'rgba(16, 185, 129, 0.85)',
                            borderColor: '#10b981',
                            borderWidth: 1.5,
                            borderRadius: 6
                        },
                        {
                            label: 'Selisih (Gain)',
                            data: gainScores,
                            backgroundColor: 'rgba(245, 158, 11, 0.85)',
                            borderColor: '#f59e0b',
                            borderWidth: 1.5,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { font: { size: 10 } }
                        },
                        x: {
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        }

        // Chart 2: Radar Chart of Feature Profiles
        const ctxRadar = document.getElementById('chart-behavior-radar')?.getContext('2d');
        if (ctxRadar) {
            // Find max for normalized radar scaling
            const maxDuration = Math.max(...clustersData.map(c => c.avg_total_reading_duration), 1);
            const maxActions = Math.max(...clustersData.map(c => c.avg_total_actions), 1);
            const maxConns = Math.max(...clustersData.map(c => c.avg_connect_count), 1);
            const maxMoves = Math.max(...clustersData.map(c => c.avg_move_count), 1);

            const colors = [
                { bg: 'rgba(16, 185, 129, 0.2)', border: '#10b981' }, // C1
                { bg: 'rgba(59, 130, 246, 0.2)', border: '#3b82f6' }, // C2
                { bg: 'rgba(245, 158, 11, 0.2)', border: '#f59e0b' }  // C3
            ];

            const radarDatasets = clustersData.map((c, idx) => ({
                label: c.cluster_label,
                data: [
                    Math.round((c.avg_total_reading_duration / maxDuration) * 100),
                    Math.round((c.avg_connect_count / maxConns) * 100),
                    c.avg_map_score,
                    Math.round((c.avg_move_count / maxMoves) * 100),
                    Math.round((c.avg_total_actions / maxActions) * 100)
                ],
                backgroundColor: colors[idx % 3].bg,
                borderColor: colors[idx % 3].border,
                borderWidth: 2,
                pointRadius: 3
            }));

            new Chart(ctxRadar, {
                type: 'radar',
                data: {
                    labels: ['Durasi Baca', 'Koneksi CER', 'Nilai Map', 'Perpindahan Kartu', 'Total Aksi'],
                    datasets: radarDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                    },
                    scales: {
                        r: {
                            min: 0,
                            max: 100,
                            ticks: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection

