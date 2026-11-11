@extends('layouts.admin', [
    'pageTitle'    => 'Session Analytics Detail #S-' . $session->id,
    'pageHeader'   => 'Session Analytics Detail',
    'pageSubtitle' => 'Detail aktivitas, analisis pergerakan kartu, dan timeline kronologis pengerjaan siswa.'
])

@section('topbar_actions')
<a href="{{ route('admin.logs') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
    </svg>
    <span>Kembali ke Log Aktivitas</span>
</a>
@endsection

@section('admin_content')
<div class="max-w-6xl mx-auto space-y-8 pb-16">

    {{-- Section 1: Session Information --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h2 class="text-sm font-semibold text-slate-900">1. Informasi Sesi Pembelajaran (Session)</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold {{ $session->is_submitted ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                {{ $session->is_submitted ? 'Telah Dikumpulkan' : 'Sedang Berlangsung' }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 text-xs">
            <div>
                <span class="text-slate-400 font-medium block">Siswa</span>
                <span class="text-sm font-semibold text-slate-900 block mt-1">{{ $analytics['student_name'] ?? '-' }}</span>
                <span class="text-[11px] text-slate-400 font-mono">{{ '@' . ($analytics['username'] ?? '-') }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-medium block">Materi / Modul</span>
                <span class="text-sm font-semibold text-slate-900 block mt-1">{{ $analytics['map_title'] ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-medium block">Waktu Mulai</span>
                <span class="text-sm font-mono text-slate-700 block mt-1">
                    {{ $analytics['start_time'] ? \Carbon\Carbon::parse($analytics['start_time'])->format('d M Y, H:i:s') : '-' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 font-medium block">Waktu Submit</span>
                <span class="text-sm font-mono text-slate-700 block mt-1">
                    {{ $analytics['submitted_at'] ? \Carbon\Carbon::parse($analytics['submitted_at'])->format('d M Y, H:i:s') : '-' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 font-medium block">Durasi Pengerjaan</span>
                <span class="text-sm font-mono font-semibold text-indigo-700 block mt-1">
                    {{ $analytics['completion_time_seconds'] !== null ? gmdate('H:i:s', $analytics['completion_time_seconds']) : '-' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 font-medium block">Skor Akhir</span>
                <span class="text-base font-bold text-indigo-600 block mt-0.5">
                    {{ $analytics['score'] !== null ? $analytics['score'] . '%' : '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Section 2: Activity Summary (Behavioral Metrics) --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">
            2. Ringkasan Metrik Aktivitas & Perilaku
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <div class="text-xl font-bold text-slate-900">{{ $analytics['total_events'] }}</div>
                <div class="text-[10px] font-medium text-slate-500 uppercase mt-0.5">Total Events</div>
            </div>
            <div class="bg-emerald-50/60 border border-emerald-100 rounded-lg p-3">
                <div class="text-xl font-bold text-emerald-700">{{ $analytics['connect_count'] }}</div>
                <div class="text-[10px] font-medium text-emerald-600 uppercase mt-0.5">Connect</div>
            </div>
            <div class="bg-red-50/60 border border-red-100 rounded-lg p-3">
                <div class="text-xl font-bold text-red-700">{{ $analytics['disconnect_count'] }}</div>
                <div class="text-[10px] font-medium text-red-600 uppercase mt-0.5">Disconnect</div>
            </div>
            <div class="bg-blue-50/60 border border-blue-100 rounded-lg p-3">
                <div class="text-xl font-bold text-blue-700">{{ $analytics['move_count'] }}</div>
                <div class="text-[10px] font-medium text-blue-600 uppercase mt-0.5">Move Count</div>
            </div>
            <div class="bg-indigo-50/60 border border-indigo-100 rounded-lg p-3">
                <div class="text-xl font-bold text-indigo-700">{{ $analytics['total_move_distance'] }}</div>
                <div class="text-[10px] font-medium text-indigo-600 uppercase mt-0.5">Move Distance</div>
            </div>
            <div class="bg-purple-50/60 border border-purple-100 rounded-lg p-3">
                <div class="text-xl font-bold text-purple-700">{{ $analytics['unique_moved_cards'] }}</div>
                <div class="text-[10px] font-medium text-purple-600 uppercase mt-0.5">Unique Cards</div>
            </div>
            <div class="bg-amber-50/60 border border-amber-100 rounded-lg p-3">
                <div class="text-xl font-bold text-amber-700">{{ $analytics['reading_count'] }}</div>
                <div class="text-[10px] font-medium text-amber-600 uppercase mt-0.5">Bacaan ({{ $analytics['reading_duration_seconds'] }}s)</div>
            </div>
        </div>

        {{-- Component Movements breakdown --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100 text-xs">
            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                <span class="text-slate-600">Claim Move</span>
                <span class="font-semibold text-slate-900">{{ $analytics['claim_move_count'] }}</span>
            </div>
            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                <span class="text-slate-600">Evidence Move</span>
                <span class="font-semibold text-slate-900">{{ $analytics['evidence_move_count'] }}</span>
            </div>
            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                <span class="text-slate-600">Reasoning Move</span>
                <span class="font-semibold text-slate-900">{{ $analytics['reasoning_move_count'] }}</span>
            </div>
            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                <span class="text-slate-600">Auto Arrange / Feedback</span>
                <span class="font-semibold text-slate-900">{{ $analytics['auto_arrange_count'] }} / {{ $analytics['feedback_count'] }}</span>
            </div>
        </div>
    </div>

    {{-- Section 3: Movement Analysis Table --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">3. Movement Analysis (Analisis Pergeseran Kartu)</h2>
                <p class="text-xs text-slate-500">Rekapitulasi kronologis pemindahan kartu oleh siswa.</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">{{ count($analytics['movements']) }} Pergerakan</span>
        </div>

        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-4 w-28">Waktu</th>
                        <th class="py-2.5 px-4">Kartu</th>
                        <th class="py-2.5 px-4">Jenis Komponen</th>
                        <th class="py-2.5 px-4 text-center">Posisi Awal</th>
                        <th class="py-2.5 px-4 text-center">Posisi Tujuan</th>
                        <th class="py-2.5 px-4 text-center">Jarak Tempuh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($analytics['movements'] as $mv)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-2.5 px-4 font-mono text-slate-500">{{ \Carbon\Carbon::parse($mv['timestamp'])->format('H:i:s') }}</td>
                        <td class="py-2.5 px-4 font-mono font-semibold text-indigo-700">{{ $mv['card'] }}</td>
                        <td class="py-2.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $mv['card_type'] === 'claim' ? 'bg-blue-50 text-blue-700' : ($mv['card_type'] === 'evidence' ? 'bg-amber-50 text-amber-700' : 'bg-purple-50 text-purple-700') }}">
                                {{ ucfirst($mv['card_type']) }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 text-center font-mono">{{ $mv['from'] }}</td>
                        <td class="py-2.5 px-4 text-center font-mono">{{ $mv['to'] }}</td>
                        <td class="py-2.5 px-4 text-center font-semibold text-indigo-700 bg-indigo-50/30">{{ $mv['distance'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            Tidak ada event pergeseran kartu pada sesi ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 4: Activity Timeline --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-6">
            4. Timeline Kronologis Aktivitas (Raw Events Sequence)
        </h2>

        <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            @forelse($analytics['timeline'] as $item)
            <div class="relative flex items-start gap-4">
                <span class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white bg-indigo-600 shadow-2xs"></span>
                <div class="flex-1 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-semibold px-2 py-0.5 rounded text-[10px] uppercase {{ $item['action_type'] === 'CONNECT' ? 'bg-emerald-100 text-emerald-800' : ($item['action_type'] === 'DISCONNECT' ? 'bg-red-100 text-red-800' : ($item['action_type'] === 'MOVE' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800')) }}">
                            {{ $item['action_type'] }}
                        </span>
                        <span class="font-medium text-slate-800">{{ $item['detail'] }}</span>
                    </div>
                    <span class="font-mono text-slate-400 text-[11px] shrink-0">
                        {{ \Carbon\Carbon::parse($item['timestamp'])->format('H:i:s') }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-sm text-slate-400 py-4">Belum ada peristiwa aktivitas yang tercatat.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
