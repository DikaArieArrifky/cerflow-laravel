<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($pageTitle ?? null) ? (str_ends_with($pageTitle, 'CER Flow') ? $pageTitle : $pageTitle . ' - CER Flow') : 'CER Flow' }}</title>
    
    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
    
    {{-- CERFlow Academic Styles --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    @stack('styles')
</head>
<body class="bg-slate-50 min-h-screen flex flex-col font-sans text-slate-800 antialiased {{ $bodyClass ?? '' }}">

    {{-- Main View Content --}}
    @yield('content')

    {{-- Toast Notification Container --}}
    <div id="toast-container" aria-live="polite"></div>

    {{-- Global Confirmation Modal (Accessible & Modern) --}}
    <div id="global-confirm-modal" class="fixed inset-0 z-[300] bg-slate-900/60 backdrop-blur-sm items-center justify-center p-4 hidden transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="global-confirm-title">
        <div class="bg-white rounded-xl max-w-md w-full shadow-xl border border-slate-200 overflow-hidden transform transition-all duration-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 id="global-confirm-title" class="text-base font-semibold text-slate-900">Konfirmasi Tindakan</h3>
            </div>
            <p id="global-confirm-message" class="text-sm text-slate-600 mb-6 leading-relaxed whitespace-pre-wrap"></p>
            <div class="flex gap-3 justify-end">
                <button id="global-confirm-cancel" type="button" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Batal</button>
                <button id="global-confirm-ok" type="button" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Lanjutkan</button>
            </div>
        </div>
    </div>

    {{-- Global Alert Modal (Accessible & Modern) --}}
    <div id="global-alert-modal" class="fixed inset-0 z-[300] bg-slate-900/60 backdrop-blur-sm items-center justify-center p-4 hidden transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="global-alert-title">
        <div class="bg-white rounded-xl max-w-md w-full shadow-xl border border-slate-200 overflow-hidden transform transition-all duration-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div id="global-alert-icon" class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 id="global-alert-title" class="text-base font-semibold text-slate-900">Pemberitahuan</h3>
            </div>
            <p id="global-alert-message" class="text-sm text-slate-600 mb-6 leading-relaxed whitespace-pre-wrap"></p>
            <div class="flex justify-end">
                <button id="global-alert-ok" type="button" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Mengerti</button>
            </div>
        </div>
    </div>

    {{-- Dialog & Interaction Helpers --}}
    <script src="{{ asset('js/dialog-utils.js') }}"></script>
    <script>
        // Modern Toast Notification Helper
        window.Toast = function(message, type = 'info') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'toast-item';

            let bgClass = 'bg-white border border-slate-200 text-slate-800';
            let iconSvg = '';

            if (type === 'success') {
                bgClass = 'bg-emerald-50 border border-emerald-200 text-emerald-800';
                iconSvg = '<svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else if (type === 'error') {
                bgClass = 'bg-red-50 border border-red-200 text-red-800';
                iconSvg = '<svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            } else if (type === 'warning') {
                bgClass = 'bg-amber-50 border border-amber-200 text-amber-800';
                iconSvg = '<svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
            } else {
                iconSvg = '<svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            }

            toast.className += ' ' + bgClass;
            toast.innerHTML = iconSvg + `<span class="flex-1">${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                setTimeout(() => toast.remove(), 250);
            }, 3500);
        };

        // CustomConfirm Promise Modal
        window.CustomConfirm = function(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('global-confirm-modal');
                const msgEl = document.getElementById('global-confirm-message');
                const btnOk = document.getElementById('global-confirm-ok');
                const btnCancel = document.getElementById('global-confirm-cancel');

                msgEl.innerText = message;
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                const cleanup = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    btnOk.removeEventListener('click', onOk);
                    btnCancel.removeEventListener('click', onCancel);
                };

                const onOk = () => { cleanup(); resolve(true); };
                const onCancel = () => { cleanup(); resolve(false); };

                btnOk.addEventListener('click', onOk);
                btnCancel.addEventListener('click', onCancel);
            });
        };

        // CustomAlert Promise Modal
        window.CustomAlert = function(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('global-alert-modal');
                const msgEl = document.getElementById('global-alert-message');
                const btnOk = document.getElementById('global-alert-ok');

                msgEl.innerText = message;
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                const cleanup = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    btnOk.removeEventListener('click', onOk);
                };

                const onOk = () => { cleanup(); resolve(); };
                btnOk.addEventListener('click', onOk);
            });
        };
    </script>

    @stack('scripts')
</body>
</html>
