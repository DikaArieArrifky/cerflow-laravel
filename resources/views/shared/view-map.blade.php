@extends('layouts.app', [
    'pageTitle' => 'Hasil Map - ' . ($score->user?->namalengkap ?? 'Siswa'),
    'navContext' => auth()->user()?->role === 'guru' ? 'report' : 'history'
])

@section('content')
<style>
    .node {
        width: 16px;
        height: 16px;
        background: #4f46e5;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        border: 3px solid white;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), 0 0 0 1px #cbd5e1;
    }
    .node.out { right: -8px; }
    .node.in { left: -8px; }
    #svg-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    #view-editor {
        position: relative;
    }
</style>

<div class="px-6 py-4 flex flex-wrap justify-between items-center bg-white border-b border-slate-200 gap-4">
    <div>
        <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                Hasil Map: <span class="text-primary-600">{{ $score->user?->namalengkap }}</span>
            </h2>
            @php
                $scoreVal = (float)$score->score;
                $badgeClass = $scoreVal >= 80 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($scoreVal >= 60 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
            @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                Skor: {{ $scoreVal }}%
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
            Materi: <span class="font-medium text-slate-700">{{ $score->cerMap?->title }}</span> • Sesi: #S-{{ $score->session_id ?? '-' }} • Disubmit: {{ $score->submitted_at ? $score->submitted_at->format('d M Y H:i') : '-' }}
        </p>
    </div>

    <div class="flex items-center gap-2.5">
        <button type="button" id="btn-feedback" onclick="toggleFeedback()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-700 hover:bg-emerald-100 transition shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>Feedback</span>
        </button>
        <button type="button" onclick="autoArrange()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition shadow-sm" title="Rapikan susunan posisi kartu">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            <span>Rapikan Posisi</span>
        </button>
        @if(auth()->user()?->role === 'guru')
            <a href="{{ route('admin.report') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition shadow-sm">
                ← Kembali ke Analytics
            </a>
        @else
            <a href="{{ route('student.history') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition shadow-sm">
                ← Kembali ke Riwayat
            </a>
        @endif
    </div>
</div>

<div id="view-editor" class="p-6 h-[calc(100vh-140px)] min-h-[600px] overflow-auto bg-slate-50">
    <svg id="svg-canvas">
        <g id="line-group"></g>
    </svg>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-[1600px] mx-auto h-full relative z-10">
        <!-- Claims Column -->
        <div class="flex flex-col bg-white rounded-xl border border-slate-200 border-t-4 border-t-primary-600 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50/75 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-primary-900">1. CLAIM</h4>
                </div>
                <span class="text-[11px] text-slate-500 font-medium">Pernyataan</span>
            </div>
            <div id="col-claim" class="space-y-3 bg-slate-50/25 flex-1 p-4 overflow-y-auto"></div>
        </div>
        
        <!-- Evidences Column -->
        <div class="flex flex-col bg-white rounded-xl border border-slate-200 border-t-4 border-t-amber-500 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50/75 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900">2. EVIDENCE</h4>
                </div>
                <span class="text-[11px] text-slate-500 font-medium">Bukti Lapangan</span>
            </div>
            <div id="col-evidence" class="space-y-3 bg-slate-50/25 flex-1 p-4 overflow-y-auto"></div>
        </div>
        
        <!-- Reasonings Column -->
        <div class="flex flex-col bg-white rounded-xl border border-slate-200 border-t-4 border-t-violet-600 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50/75 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-violet-900">3. REASONING</h4>
                </div>
                <span class="text-[11px] text-slate-500 font-medium">Penalaran & Logika</span>
            </div>
            <div id="col-reasoning" class="space-y-3 bg-slate-50/25 flex-1 p-4 overflow-y-auto"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const mapData = @json($map_data);
    let feedbackActive = true;

    function getTripletCardId(type, tripletId) {
        return `${type[0]}-${tripletId}`;
    }

    function getCardTripletId(cardId) {
        const [, tripletId] = String(cardId).split('-');
        return String(tripletId);
    }

    function getTripletByCardId(cardId) {
        const tripletId = parseInt(getCardTripletId(cardId), 10);
        return mapData.triplets.find(t => parseInt(t.id, 10) === tripletId) || null;
    }

    function toggleFeedback() {
        feedbackActive = !feedbackActive;
        const btn = document.getElementById('btn-feedback');
        if (btn) {
            btn.classList.toggle('bg-emerald-50', feedbackActive);
            btn.classList.toggle('border-emerald-300', feedbackActive);
            btn.classList.toggle('text-emerald-700', feedbackActive);
            btn.classList.toggle('bg-slate-100', !feedbackActive);
            btn.classList.toggle('text-slate-600', !feedbackActive);
        }
        redrawLines();
    }
    
    function renderReadonlyCards() {
        if (!mapData || !mapData.triplets) return;

        ['claim', 'evidence', 'reasoning'].forEach(type => {
            const col = document.getElementById(`col-${type}`);
            if(!col) return;

            let orderedIds = mapData.colOrder && mapData.colOrder[type] ? mapData.colOrder[type] : [];
            
            if (orderedIds.length === 0) {
                orderedIds = mapData.triplets.map(t => getTripletCardId(type, t.id));
            }
            
            col.innerHTML = orderedIds.map(id => {
                const t = getTripletByCardId(id);
                if (!t) return '';
                
                return `
                <div id="${id}" class="card-item bg-white border border-slate-200 rounded-xl p-4 shadow-sm relative" style="opacity: 0.98;">
                    <div class="card-handle cursor-move">
                        <p class="text-sm leading-relaxed text-slate-700 font-medium select-none">${t[type]}</p>
                    </div>
                    ${type !== 'reasoning' ? '<div class="node out pointer-events-none"></div>' : ''}
                    ${type !== 'claim' ? '<div class="node in pointer-events-none"></div>' : ''}
                </div>`;
            }).join('');

            new Sortable(col, { 
                animation: 250, 
                handle: '.card-handle',
                onEnd: () => redrawLines()
            });
        });

        setTimeout(redrawLines, 100);
    }

    function getNodePos(node) {
        const editor = document.getElementById('view-editor');
        const editorRect = editor.getBoundingClientRect();
        const rect = node.getBoundingClientRect();
        return {
            x: rect.left - editorRect.left + (rect.width / 2) + editor.scrollLeft,
            y: rect.top - editorRect.top + (rect.height / 2) + editor.scrollTop
        };
    }

    function redrawLines() {
        const lineGroup = document.getElementById('line-group');
        if(!lineGroup) return;
        
        lineGroup.innerHTML = '';
        if(!mapData.connections) return;

        mapData.connections.forEach(c => {
            const elFrom = document.getElementById(c.from);
            const elTo = document.getElementById(c.to);
            
            if (elFrom && elTo) {
                const nOut = elFrom.querySelector('.node.out');
                const nIn = elTo.querySelector('.node.in');
                
                if (nOut && nIn) {
                    const p1 = getNodePos(nOut);
                    const p2 = getNodePos(nIn);
                    
                    const dist = Math.abs(p2.x - p1.x) * 0.5;
                    const d = `M ${p1.x} ${p1.y} C ${p1.x + dist} ${p1.y}, ${p2.x - dist} ${p2.y}, ${p2.x} ${p2.y}`;
                    
                    const isCorrect = getCardTripletId(c.from) === getCardTripletId(c.to);
                    
                    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                    path.setAttribute("d", d);
                    path.setAttribute("fill", "none");
                    
                    if (feedbackActive) {
                        path.setAttribute("stroke", isCorrect ? "#10b981" : "#ef4444");
                        if (!isCorrect) {
                            path.setAttribute("stroke-dasharray", "8,6");
                            path.setAttribute("stroke-width", "3");
                        } else {
                            path.setAttribute("stroke-width", "3.5");
                        }
                    } else {
                        path.setAttribute("stroke", "#4f46e5");
                        path.setAttribute("stroke-width", "3");
                    }

                    path.setAttribute("stroke-linecap", "round");
                    
                    lineGroup.appendChild(path);
                }
            }
        });
    }

    window.addEventListener('resize', redrawLines);
    document.addEventListener('DOMContentLoaded', renderReadonlyCards);

    function autoArrange() {
        if (!mapData.connections || mapData.connections.length === 0) {
            Toast.info("Belum ada koneksi untuk dirapikan.");
            return;
        }

        const colC = document.getElementById('col-claim');
        const colE = document.getElementById('col-evidence');
        const colR = document.getElementById('col-reasoning');

        const claims = Array.from(colC.children);
        const evidences = Array.from(colE.children);
        const reasonings = Array.from(colR.children);

        let newOrderC = [], newOrderE = [], newOrderR = [];
        let processedC = new Set(), processedE = new Set(), processedR = new Set();

        claims.forEach(cEl => {
            const connCE = mapData.connections.find(conn => conn.from === cEl.id);
            if (connCE) {
                newOrderC.push(cEl);
                processedC.add(cEl.id);
                const eEl = evidences.find(e => e.id === connCE.to);
                if (eEl) {
                    newOrderE.push(eEl);
                    processedE.add(eEl.id);
                    const connER = mapData.connections.find(conn => conn.from === eEl.id);
                    if (connER) {
                        const rEl = reasonings.find(r => r.id === connER.to);
                        if (rEl) {
                            newOrderR.push(rEl);
                            processedR.add(rEl.id);
                        }
                    }
                }
            }
        });

        evidences.forEach(eEl => {
            if (processedE.has(eEl.id)) return;
            const connER = mapData.connections.find(conn => conn.from === eEl.id);
            if (connER) {
                newOrderE.push(eEl);
                processedE.add(eEl.id);
                const rEl = reasonings.find(r => r.id === connER.to);
                if (rEl) {
                    newOrderR.push(rEl);
                    processedR.add(rEl.id);
                }
            }
        });

        const maxLen = Math.max(newOrderC.length, newOrderE.length, newOrderR.length);
        const pad = (order, pool, processed) => {
            while (order.length < maxLen) {
                const next = pool.find(el => !processed.has(el.id));
                if (next) {
                    order.push(next);
                    processed.add(next.id);
                } else {
                    break;
                }
            }
        };

        pad(newOrderC, claims, processedC);
        pad(newOrderE, evidences, processedE);
        pad(newOrderR, reasonings, processedR);

        claims.forEach(el => { if (!processedC.has(el.id)) newOrderC.push(el); });
        evidences.forEach(el => { if (!processedE.has(el.id)) newOrderE.push(el); });
        reasonings.forEach(el => { if (!processedR.has(el.id)) newOrderR.push(el); });

        [
            {col: colC, order: newOrderC},
            {col: colE, order: newOrderE},
            {col: colR, order: newOrderR}
        ].forEach(conf => {
            conf.col.innerHTML = '';
            conf.order.forEach(el => conf.col.appendChild(el));
        });

        redrawLines();
    }
</script>
@endsection
