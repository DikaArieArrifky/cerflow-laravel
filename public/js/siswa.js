/**
 * CERFlow - Student Interactive Learning Logic (Redesigned UI/UX)
 * Handles interactive CER Canvas, Drag-and-drop reorder, connections,
 * Auto Arrange, Feedback, Submission modal, and Research Activity Logging.
 * 
 * Strict Research Compliance:
 * - NO fake interaction logs on render, resize, or auto-arrange.
 * - Move is logged ONLY when user manually changes card order (oldIndex !== newIndex).
 * - Reading drawer open -> view_reading, close -> reading_close.
 * - Feedback toggle -> feedback.
 * - Auto arrange -> auto_arrange.
 * - Connect / Disconnect -> connect / disconnect.
 */

let currentMap = null;
let currentMapIndex = null;
let connections = [];
let isDrawing = false;
let startNode = null;
let currentSessionId = null;
let feedbackActive = false;
let isLocked = false;       // True after submit — locks workspace into review mode
let isSubmitting = false;   // Double-submit protection flag
let isReadingModalOpen = false;
let readingStartTime = null;
let readingTimerInterval = null;

// ─── Session Storage Keys ────────────────────────────────────────────────────
const SK_MAP_INDEX   = 'cer_map_index_v2';
const SK_SESSION_ID  = 'cer_session_id_v2';
const SK_CONNECTIONS = 'cer_connections_v2';
const SK_COL_ORDER   = 'cer_col_order_v2';

function getTripletCardId(type, tripletId) {
    return `${type[0]}-${tripletId}`;
}

function getCardTripletId(cardId) {
    const [, tripletId] = String(cardId).split('-');
    return String(tripletId);
}

function getTripletByCardId(cardId, map = currentMap) {
    if (!map || !map.triplets) return null;
    const tripletId = parseInt(getCardTripletId(cardId), 10);
    return map.triplets.find(t => parseInt(t.id, 10) === tripletId) || null;
}

function isCorrectConnection(connection) {
    return getCardTripletId(connection.from) === getCardTripletId(connection.to);
}

/**
 * Persist current state to sessionStorage
 */
function saveState() {
    if (currentMapIndex === null) return;
    sessionStorage.setItem(SK_MAP_INDEX,   currentMapIndex);
    sessionStorage.setItem(SK_SESSION_ID,  currentSessionId || '');
    sessionStorage.setItem(SK_CONNECTIONS, JSON.stringify(connections));

    // Capture card order per column
    const order = {};
    ['claim', 'evidence', 'reasoning'].forEach(type => {
        const col = document.getElementById(`col-${type}`);
        if (col) {
            order[type] = Array.from(col.children).map(el => el.id);
        }
    });
    sessionStorage.setItem(SK_COL_ORDER, JSON.stringify(order));
}

/**
 * Clear persisted state
 */
function clearState() {
    [SK_MAP_INDEX, SK_SESSION_ID, SK_CONNECTIONS, SK_COL_ORDER].forEach(k => sessionStorage.removeItem(k));
}

/**
 * Log student interaction to Laravel API
 */
function logAction(type, data = {}) {
    if (!currentSessionId) return;

    if (window.ApiClient) {
        ApiClient.post('/api/logs', {
            session_id: currentSessionId,
            action_type: type,
            action_data: data,
            type: type,
            data: data
        }).catch(err => console.error("Logging error:", err));
    }
}

/**
 * Update the bottom action bar connection counter
 */
function updateConnectionCounter() {
    const counterEl = document.getElementById('connection-counter');
    if (!counterEl || !currentMap || !currentMap.triplets) return;

    const totalRequired = currentMap.triplets.length * 2;
    counterEl.innerText = `${connections.length} / ${totalRequired} koneksi dibuat`;
}

/**
 * Start learning a specific module
 * @param {number} i Index in cerMaps array
 */
async function startLearning(i) {
    if (typeof cerMaps === 'undefined') return;
    currentMapIndex = i;
    currentMap = cerMaps[i];
    isLocked = false;
    isSubmitting = false;
    feedbackActive = false;
    connections = [];
    
    try {
        const data = await ApiClient.post('/api/sessions', { map_id: currentMap.id });
        if (data.status === 'success' || data.success) {
            currentSessionId = data.session_id || (data.data && data.data.session_id);
            saveState();
        }
    } catch(err) {
        console.error("Start session error:", err);
    }

    // UI Updates
    const activeTitle = document.getElementById('active-title');
    if (activeTitle) activeTitle.innerText = currentMap.title;
    
    const activeSubtitle = document.getElementById('active-subtitle');
    if (activeSubtitle) activeSubtitle.innerText = 'Hubungkan Claim → Evidence → Reasoning';

    const activeContainer = document.getElementById('active-title-container');
    if (activeContainer) activeContainer.classList.remove('hidden');

    const actionsEl = document.getElementById('editor-actions');
    if (actionsEl) actionsEl.classList.remove('hidden');
    
    // Close reading drawer if open
    closeReadingDrawerSilently();
    
    restoreEditorActions();
    updateConnectionCounter();
    
    document.getElementById('view-selector')?.classList.add('hidden');
    document.getElementById('view-editor')?.classList.remove('hidden');
    
    renderCards();
}

/**
 * Restore a previously active session from sessionStorage
 */
function restoreSession(i, sid, savedConnections, colOrder) {
    currentMapIndex = i;
    currentMap = cerMaps[i];
    currentSessionId = sid;
    connections = savedConnections || [];
    isLocked = false;
    isSubmitting = false;

    const activeTitle = document.getElementById('active-title');
    if (activeTitle) activeTitle.innerText = currentMap.title;

    const activeSubtitle = document.getElementById('active-subtitle');
    if (activeSubtitle) activeSubtitle.innerText = 'Hubungkan Claim → Evidence → Reasoning';

    const activeContainer = document.getElementById('active-title-container');
    if (activeContainer) activeContainer.classList.remove('hidden');

    const actionsEl = document.getElementById('editor-actions');
    if (actionsEl) actionsEl.classList.remove('hidden');

    restoreEditorActions();
    updateConnectionCounter();
    
    document.getElementById('view-selector')?.classList.add('hidden');
    document.getElementById('view-editor')?.classList.remove('hidden');

    renderCards(colOrder);
}

/**
 * Go back to the module selector with unsaved changes confirmation
 */
async function backToSelector() {
    if (connections.length > 0 && !isLocked) {
        let confirmed = false;
        if (window.CustomConfirm) {
            confirmed = await CustomConfirm("Anda belum mengirim jawaban. Yakin ingin keluar? Progres yang belum dikirim dapat hilang.");
        } else {
            confirmed = confirm("Anda belum mengirim jawaban. Yakin ingin keluar?");
        }
        if (!confirmed) return;
    }
    
    goToSelector();
}

function goToSelector() {
    connections = [];
    currentMap = null;
    currentMapIndex = null;
    isLocked = false;
    isSubmitting = false;
    feedbackActive = false;
    restoreEditorActions();
    clearState();
    closeReadingDrawerSilently();
    
    const lg = document.getElementById('line-group');
    if (lg) lg.innerHTML = '';
    
    document.getElementById('active-title-container')?.classList.add('hidden');
    document.getElementById('editor-actions')?.classList.add('hidden');
    document.getElementById('view-result')?.classList.add('hidden');
    
    document.getElementById('view-selector')?.classList.remove('hidden');
    document.getElementById('view-editor')?.classList.add('hidden');
}

/**
 * Render cards in columns, optionally restoring a saved order.
 */
function renderCards(colOrder = null) {
    if (!currentMap || !currentMap.triplets) return;

    ['claim', 'evidence', 'reasoning'].forEach(type => {
        const col = document.getElementById(`col-${type}`);
        if(!col) return;

        let ordered;
        if (colOrder && colOrder[type] && colOrder[type].length > 0) {
            const savedIds = colOrder[type];
            ordered = savedIds
                .map(id => {
                    const triplet = getTripletByCardId(id);
                    return triplet ? { triplet } : null;
                })
                .filter(Boolean);

            currentMap.triplets.forEach(t => {
                if (!savedIds.includes(getTripletCardId(type, t.id))) {
                    ordered.push({ triplet: t });
                }
            });
        } else {
            const shuffled = [...currentMap.triplets.map(t => ({ triplet: t }))].sort(() => Math.random() - 0.5);
            ordered = shuffled;
        }
        
        col.innerHTML = ordered.map(({ triplet: t }) => {
            return `
            <div id="${getTripletCardId(type, t.id)}" class="card-item bg-white border border-slate-200 rounded-xl p-4 shadow-sm relative transition-all group hover:border-primary-400 hover:shadow" style="opacity: 0.98;">
                <div class="card-handle cursor-grab active:cursor-grabbing">
                    <p class="text-sm leading-relaxed text-slate-700 select-none">${escapeHtml(t[type])}</p>
                </div>
                ${type !== 'reasoning' ? '<div class="node out" title="Tarik koneksi dari sini"></div>' : ''}
                ${type !== 'claim' ? '<div class="node in" title="Hubungkan koneksi ke sini"></div>' : ''}
            </div>`;
        }).join('');

        // Initialize SortableJS
        new Sortable(col, { 
            animation: 250, 
            easing: "cubic-bezier(0.34, 1.56, 0.64, 1)",
            handle: '.card-handle',
            onMove: () => !isLocked && !isReadingModalOpen,
            onEnd: (evt) => {
                redrawLines();
                // Research Logging Rule: Only log move when from !== to
                if (evt.oldIndex !== evt.newIndex) {
                    let cardType = 'reasoning';
                    if (evt.item.id.startsWith('c-')) cardType = 'claim';
                    else if (evt.item.id.startsWith('e-')) cardType = 'evidence';

                    logAction('move', { 
                        card: evt.item.id,
                        card_type: cardType,
                        from: evt.oldIndex, 
                        to: evt.newIndex 
                    });
                    saveState();
                }
            }
        });
    });

    if (connections.length > 0) {
        requestAnimationFrame(() => redrawLines());
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.innerText = str;
    return div.innerHTML;
}

/**
 * Get the center position of a node relative to view-editor
 */
function getNodePos(node) {
    const editor = document.getElementById('view-editor');
    const editorRect = editor ? editor.getBoundingClientRect() : { left: 0, top: 0 };
    const rect = node.getBoundingClientRect();
    return {
        x: rect.left - editorRect.left + (rect.width / 2) + (editor ? editor.scrollLeft : 0),
        y: rect.top - editorRect.top + (rect.height / 2) + (editor ? editor.scrollTop : 0)
    };
}

/**
 * Reset the drawing line position
 */
function resetDrawingLine() {
    const dl = document.getElementById('drawing-line');
    if(!dl) return;
    dl.setAttribute('x1', -1000);
    dl.setAttribute('y1', -1000);
    dl.setAttribute('x2', -1000);
    dl.setAttribute('y2', -1000);
}

/**
 * Redraw all connection lines
 */
function redrawLines() {
    const lg = document.getElementById('line-group');
    if(!lg) return;
    lg.innerHTML = '';
    
    connections.forEach(c => {
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
                
                const isCorrect = isCorrectConnection(c);
                const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                path.setAttribute("d", d);
                path.setAttribute("fill", "none");
                
                if (feedbackActive) {
                    if (isCorrect) {
                        path.setAttribute("stroke", "#10b981"); // emerald-500
                        path.setAttribute("stroke-width", "3.5");
                    } else {
                        path.setAttribute("stroke", "#ef4444"); // red-500
                        path.setAttribute("stroke-width", "3");
                        path.setAttribute("stroke-dasharray", "8,6");
                    }
                } else {
                    path.setAttribute("stroke", "#4f46e5"); // indigo-600 / primary
                    path.setAttribute("stroke-width", "3");
                }
                
                path.setAttribute("stroke-linecap", "round");
                path.classList.add('line-path');
                lg.appendChild(path);
            }
        }
    });
}

/**
 * Restore the editor action buttons in the bottom sticky bar
 */
function restoreEditorActions() {
    const readingBtn = document.getElementById('btn-reading');
    if (readingBtn) {
        if (currentMap && currentMap.allow_reading == 1 && currentMap.reading_text) {
            readingBtn.classList.remove('hidden');
        } else {
            readingBtn.classList.add('hidden');
        }
    }

    const feedbackBtn = document.getElementById('btn-feedback');
    if (feedbackBtn) {
        if (currentMap && currentMap.allow_feedback == 1) {
            feedbackBtn.classList.remove('hidden');
        } else {
            feedbackBtn.classList.add('hidden');
        }
    }

    updateFeedbackUI();

    const submitBtn = document.getElementById('btn-submit');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `
            <span>Kirim Jawaban</span>
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        `;
    }
}

/**
 * Format reading duration seconds into MM:SS display
 */
function formatReadingTimer(totalSeconds) {
    const mins = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

/**
 * Open Blocking Modal for Reading Material
 * - Locks canvas interaction (backdrop: static, keyboard: false)
 * - Records start timestamp & sends open_material log
 * - Displays active live timer
 */
function openReadingModal() {
    const dialog = document.getElementById('dialog-reading');
    const content = document.getElementById('dialog-reading-content');
    const timerEl = document.getElementById('reading-timer');
    const btn = document.getElementById('btn-reading');

    if (!dialog || !currentMap) return;

    isReadingModalOpen = true;
    readingStartTime = Date.now();

    if (content) {
        content.innerText = currentMap.reading_text || 'Tidak ada teks bacaan untuk materi ini.';
    }
    if (timerEl) {
        timerEl.innerText = '00:00';
    }

    if (readingTimerInterval) {
        clearInterval(readingTimerInterval);
    }
    readingTimerInterval = setInterval(() => {
        if (!readingStartTime) return;
        const elapsedSecs = Math.floor((Date.now() - readingStartTime) / 1000);
        if (timerEl) {
            timerEl.innerText = formatReadingTimer(elapsedSecs);
        }
    }, 1000);

    dialog.classList.remove('hidden');
    if (btn) {
        btn.classList.add('bg-primary-50', 'text-primary-700', 'border-primary-300');
    }

    // Log research activity: open_material + view_reading
    logAction('open_material', { 
        map_id: currentMap.id, 
        start_time: new Date().toISOString() 
    });
    logAction('view_reading', { map_id: currentMap.id });
}

/**
 * Close Blocking Modal for Reading Material
 * - Calculates reading duration in seconds
 * - Sends close_material log with reading_duration_seconds
 * - Restores canvas interactions
 */
function closeReadingModal() {
    const dialog = document.getElementById('dialog-reading');
    const btn = document.getElementById('btn-reading');

    if (!dialog || !isReadingModalOpen) return;

    let durationSeconds = 0;
    if (readingStartTime) {
        durationSeconds = Math.max(1, Math.round((Date.now() - readingStartTime) / 1000));
    }

    if (readingTimerInterval) {
        clearInterval(readingTimerInterval);
        readingTimerInterval = null;
    }

    isReadingModalOpen = false;
    readingStartTime = null;

    dialog.classList.add('hidden');
    if (btn) {
        btn.classList.remove('bg-primary-50', 'text-primary-700', 'border-primary-300');
    }

    // Log research activity: close_material + reading_close with duration metadata
    logAction('close_material', {
        map_id: currentMap.id,
        reading_duration_seconds: durationSeconds
    });
    logAction('reading_close', {
        map_id: currentMap.id,
        reading_duration_seconds: durationSeconds
    });
}

/**
 * Toggle reading modal (calls open or close)
 */
function toggleReading() {
    if (isReadingModalOpen) {
        closeReadingModal();
    } else {
        openReadingModal();
    }
}

/**
 * Close reading modal silently without logging (when transitioning views)
 */
function closeReadingDrawerSilently() {
    const dialog = document.getElementById('dialog-reading');
    const btn = document.getElementById('btn-reading');

    if (readingTimerInterval) {
        clearInterval(readingTimerInterval);
        readingTimerInterval = null;
    }
    isReadingModalOpen = false;
    readingStartTime = null;

    if (dialog) {
        dialog.classList.add('hidden');
    }
    if (btn) {
        btn.classList.remove('bg-primary-50', 'text-primary-700', 'border-primary-300');
    }
}

/**
 * Update the Feedback button UI state
 */
function updateFeedbackUI() {
    const btn = document.getElementById('btn-feedback');
    if (btn) {
        if (feedbackActive) {
            btn.innerHTML = `
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                <span>Sembunyikan Feedback</span>
            `;
            btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
        } else {
            btn.innerHTML = `
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <span>Periksa Hubungan</span>
            `;
            btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
        }
    }
}

/**
 * Toggle feedback mode
 */
function toggleFeedback() {
    feedbackActive = !feedbackActive;
    logAction('feedback', { active: feedbackActive });
    updateFeedbackUI();
    redrawLines();
}

/**
 * Lock editor after final submission into review mode
 */
function lockEditor() {
    isLocked = true;
    feedbackActive = true;

    document.querySelectorAll('.node').forEach(n => {
        n.style.cursor = 'default';
        n.style.opacity = '0.4';
        n.style.pointerEvents = 'none';
    });

    updateFeedbackUI();
    redrawLines();

    const submitBtn = document.getElementById('btn-submit');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span>Terkirim ✓</span>
        `;
        submitBtn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
        submitBtn.classList.add('bg-slate-300', 'cursor-not-allowed', 'text-slate-600');
    }
}

/**
 * Show Submit Confirmation Modal with detailed connection breakdown
 */
function openSubmitModal() {
    if (!currentMap || isReadingModalOpen) return;
    if (connections.length === 0) {
        if (window.Toast) {
            Toast.warning("Kerjakan modul terlebih dahulu sebelum mengirim.");
        } else {
            alert("Kerjakan modul terlebih dahulu sebelum mengirim.");
        }
        return;
    }

    if (isSubmitting || isLocked) return;

    // Calculate component stats
    const totalRequired = currentMap.triplets.length * 2;
    const currentConnCount = connections.length;

    const connectedClaims = new Set(connections.filter(c => c.from.startsWith('c-')).map(c => c.from)).size;
    const connectedEvidences = new Set(
        connections.filter(c => c.to.startsWith('e-')).map(c => c.to)
    ).size;
    const connectedReasonings = new Set(connections.filter(c => c.to.startsWith('r-')).map(c => c.to)).size;

    const totalTriplets = currentMap.triplets.length;

    // Populate confirmation modal stats
    const statTotalEl = document.getElementById('confirm-total-conns');
    const statClaimEl = document.getElementById('confirm-claim-conns');
    const statEvidenceEl = document.getElementById('confirm-evidence-conns');
    const statReasoningEl = document.getElementById('confirm-reasoning-conns');
    const warningEl = document.getElementById('confirm-warning-incomplete');

    if (statTotalEl) statTotalEl.innerText = `${currentConnCount} / ${totalRequired}`;
    if (statClaimEl) statClaimEl.innerText = `${connectedClaims} dari ${totalTriplets} terhubung`;
    if (statEvidenceEl) statEvidenceEl.innerText = `${connectedEvidences} dari ${totalTriplets} terhubung`;
    if (statReasoningEl) statReasoningEl.innerText = `${connectedReasonings} dari ${totalTriplets} terhubung`;

    if (warningEl) {
        if (currentConnCount < totalRequired || connectedClaims < totalTriplets || connectedReasonings < totalTriplets) {
            warningEl.classList.remove('hidden');
        } else {
            warningEl.classList.add('hidden');
        }
    }

    const modal = document.getElementById('modal-submit-confirm');
    if (modal) modal.classList.remove('hidden');
}

/**
 * Close submit confirmation modal
 */
function closeSubmitModal() {
    const modal = document.getElementById('modal-submit-confirm');
    if (modal) modal.classList.add('hidden');
}

/**
 * Confirm and execute final submission (With Double Submit Protection)
 */
async function confirmSubmit() {
    if (isSubmitting || isLocked || !currentMap) return;
    isSubmitting = true;

    const confirmBtn = document.getElementById('btn-confirm-submit');
    const originalText = confirmBtn ? confirmBtn.innerHTML : '';
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Mengirim...</span>
        `;
    }

    let correct = 0;
    connections.forEach(c => {
        if (isCorrectConnection(c)) correct++;
    });

    const totalShouldBe = currentMap.triplets.length * 2;
    const clientScore = totalShouldBe > 0 ? (correct / totalShouldBe) * 100 : 0;

    const colOrder = {};
    ['claim', 'evidence', 'reasoning'].forEach(type => {
        const col = document.getElementById(`col-${type}`);
        if (col) {
            colOrder[type] = Array.from(col.children).map(el => el.id);
        }
    });

    const mapData = {
        triplets: currentMap.triplets,
        colOrder: colOrder,
        connections: connections
    };

    try {
        const data = await ApiClient.post('/api/scores', {
            map_id: currentMap.id,
            score: clientScore.toFixed(2),
            session_id: currentSessionId,
            map_data: mapData
        });

        if (data.status === 'success' || data.success) {
            clearState();
            closeSubmitModal();
            
            const finalScore = data.data && typeof data.data.score !== 'undefined' ? data.data.score : clientScore.toFixed(2);
            const scoreDisplay = (parseFloat(finalScore)).toFixed(2) + "%";
            
            const resultScoreEl = document.getElementById('result-score');
            if (resultScoreEl) resultScoreEl.innerText = scoreDisplay;

            // Show celebratory result modal
            document.getElementById('view-result')?.classList.remove('hidden');

            if (window.Toast) {
                Toast.success("Jawaban Anda berhasil dikirim!");
            }
        } else {
            if (window.Toast) {
                Toast.error("Gagal: " + (data.message || 'Terjadi kesalahan'));
            } else {
                alert("Gagal: " + (data.message || 'Error'));
            }
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
            isSubmitting = false;
        }
    } catch(err) {
        console.error("Gagal menyimpan nilai:", err);
        if (window.Toast) {
            Toast.error("Terjadi kesalahan jaringan: " + err.message);
        } else {
            alert("Terjadi kesalahan saat menyimpan nilai: " + err.message);
        }
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
        isSubmitting = false;
    }
}

/**
 * Backward compatibility alias for submitScore
 */
function submitScore() {
    openSubmitModal();
}

function stayOnPage() {
    document.getElementById('view-result')?.classList.add('hidden');
    lockEditor();
}

function redoActivity() {
    const idx = currentMapIndex;
    clearState();
    connections = [];
    isLocked = false;
    isSubmitting = false;
    restoreEditorActions();
    const lg = document.getElementById('line-group');
    if (lg) lg.innerHTML = '';
    document.getElementById('view-result')?.classList.add('hidden');
    startLearning(idx);
}

function handleBackdropClick(event) {
    stayOnPage();
}

/**
 * Auto-arrange connected cards to be parallel with smooth animation
 */
function autoArrange() {
    if (isReadingModalOpen || isLocked) return;
    if (connections.length === 0) {
        if (window.Toast) {
            Toast.info("Belum ada koneksi untuk dirapikan.");
        } else {
            alert("Belum ada koneksi untuk diatur!");
        }
        return;
    }

    logAction('auto_arrange');

    const colC = document.getElementById('col-claim');
    const colE = document.getElementById('col-evidence');
    const colR = document.getElementById('col-reasoning');

    const claims = Array.from(colC.children);
    const evidences = Array.from(colE.children);
    const reasonings = Array.from(colR.children);

    const initialPositions = new Map();
    [...claims, ...evidences, ...reasonings].forEach(el => {
        initialPositions.set(el.id, el.getBoundingClientRect());
    });

    let newOrderC = [], newOrderE = [], newOrderR = [];
    let processedC = new Set(), processedE = new Set(), processedR = new Set();

    claims.forEach(cEl => {
        const connCE = connections.find(conn => conn.from === cEl.id);
        if (connCE) {
            newOrderC.push(cEl);
            processedC.add(cEl.id);
            
            const eEl = evidences.find(e => e.id === connCE.to);
            if (eEl) {
                newOrderE.push(eEl);
                processedE.add(eEl.id);
                
                const connER = connections.find(conn => conn.from === eEl.id);
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
        const connER = connections.find(conn => conn.from === eEl.id);
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

    requestAnimationFrame(() => {
        [...newOrderC, ...newOrderE, ...newOrderR].forEach(el => {
            const oldPos = initialPositions.get(el.id);
            const newPos = el.getBoundingClientRect();
            
            const dx = oldPos.left - newPos.left;
            const dy = oldPos.top - newPos.top;
            
            if (dx || dy) {
                el.style.transition = 'none';
                el.style.transform = `translate(${dx}px, ${dy}px)`;
                
                requestAnimationFrame(() => {
                    el.style.transition = 'transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
                    el.style.transform = 'none';
                });
            }
        });
        
        let start = null;
        function step(timestamp) {
            if (!start) start = timestamp;
            redrawLines();
            if (timestamp - start < 600) {
                requestAnimationFrame(step);
            }
        }
        requestAnimationFrame(step);
    });

    saveState();
}

// ─── Event Listeners ──────────────────────────────────────────────────────────
document.addEventListener('mousedown', (e) => {
    if (isLocked || isReadingModalOpen) return;
    if (e.target.classList.contains('node')) {
        isDrawing = true;
        startNode = e.target;
        const pos = getNodePos(startNode);
        const dl = document.getElementById('drawing-line');
        if (dl) {
            dl.setAttribute('x1', pos.x);
            dl.setAttribute('y1', pos.y);
            dl.setAttribute('x2', pos.x);
            dl.setAttribute('y2', pos.y);
        }
    }
});

document.addEventListener('mousemove', (e) => {
    if (isDrawing) {
        const dl = document.getElementById('drawing-line');
        const editor = document.getElementById('view-editor');
        const editorRect = editor ? editor.getBoundingClientRect() : { left: 0, top: 0 };
        if (dl) {
            dl.setAttribute('x2', e.clientX - editorRect.left + (editor ? editor.scrollLeft : 0));
            dl.setAttribute('y2', e.clientY - editorRect.top + (editor ? editor.scrollTop : 0));
        }
    }
});

document.addEventListener('mouseup', (e) => {
    if (!isDrawing) return;
    
    if (e.target.classList.contains('node')) {
        const sCard = startNode.closest('.card-item');
        const tCard = e.target.closest('.card-item');
        
        let fromCard, toCard;

        if (startNode.classList.contains('out') && e.target.classList.contains('in')) {
            fromCard = sCard;
            toCard = tCard;
        } else if (startNode.classList.contains('in') && e.target.classList.contains('out')) {
            fromCard = tCard;
            toCard = sCard;
        }
        
        if (fromCard && toCard) {
            const fType = fromCard.id[0], tType = toCard.id[0];
            if ((fType === 'c' && tType === 'e') || (fType === 'e' && tType === 'r')) {
                const existingFrom = connections.find(c => c.from === fromCard.id);
                const existingTo = connections.find(c => c.to === toCard.id);
                
                if (existingFrom) logAction('disconnect', { from: existingFrom.from, to: existingFrom.to });
                if (existingTo && existingTo !== existingFrom) logAction('disconnect', { from: existingTo.from, to: existingTo.to });

                connections = connections.filter(c => c.from !== fromCard.id && c.to !== toCard.id);
                connections.push({ from: fromCard.id, to: toCard.id });
                logAction('connect', { from: fromCard.id, to: toCard.id });
                saveState();
                updateConnectionCounter();
            }
        }
    } else {
        const sCard = startNode.closest('.card-item');
        if (sCard) {
            let existingConn;
            if (startNode.classList.contains('out')) {
                existingConn = connections.find(c => c.from === sCard.id);
            } else if (startNode.classList.contains('in')) {
                existingConn = connections.find(c => c.to === sCard.id);
            }

            if (existingConn) {
                logAction('disconnect', { from: existingConn.from, to: existingConn.to });
                connections = connections.filter(c => c !== existingConn);
                saveState();
                updateConnectionCounter();
            }
        }
    }
    
    if (feedbackActive) {
        feedbackActive = false;
        updateFeedbackUI();
    }
    
    isDrawing = false;
    startNode = null;
    resetDrawingLine();
    redrawLines();
});

document.addEventListener('scroll', () => {
    requestAnimationFrame(redrawLines);
}, true);

window.addEventListener('resize', redrawLines);

// Block keyboard ESC from dismissing the blocking reading modal
document.addEventListener('keydown', (e) => {
    if (isReadingModalOpen && (e.key === 'Escape' || e.keyCode === 27)) {
        e.preventDefault();
        e.stopPropagation();
    }
});

// Accurate reading duration tracking on tab close / reload
window.addEventListener('beforeunload', () => {
    if (isReadingModalOpen && readingStartTime && currentSessionId && currentMap) {
        const durationSeconds = Math.max(1, Math.round((Date.now() - readingStartTime) / 1000));
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const payload = JSON.stringify({
            session_id: currentSessionId,
            action_type: 'close_material',
            action_data: {
                map_id: currentMap.id,
                reading_duration_seconds: durationSeconds
            },
            type: 'close_material',
            data: {
                map_id: currentMap.id,
                reading_duration_seconds: durationSeconds
            }
        });

        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon('/api/logs', blob);
            } else {
                fetch('/api/logs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                        'Accept': 'application/json'
                    },
                    body: payload,
                    keepalive: true
                });
            }
        } catch(e) {
            // Ignore unload network errors
        }
    }
});

// ─── Restore State on Page Load ───────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    const savedIndex      = sessionStorage.getItem(SK_MAP_INDEX);
    const savedSessionId  = sessionStorage.getItem(SK_SESSION_ID);
    const savedConns      = sessionStorage.getItem(SK_CONNECTIONS);
    const savedColOrder   = sessionStorage.getItem(SK_COL_ORDER);

    if (savedIndex !== null && typeof cerMaps !== 'undefined' && cerMaps[savedIndex]) {
        const i    = parseInt(savedIndex);
        const sid  = savedSessionId || null;
        const conns = savedConns ? JSON.parse(savedConns) : [];
        const order = savedColOrder ? JSON.parse(savedColOrder) : null;
        restoreSession(i, sid, conns, order);
    }
});
