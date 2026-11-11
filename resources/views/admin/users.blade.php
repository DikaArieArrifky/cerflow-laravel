@extends('layouts.admin', [
    'pageTitle'    => 'User Management - CERFlow',
    'pageHeader'   => 'User Management',
    'pageSubtitle' => 'Kelola akun guru, dosen, dan siswa untuk akses sistem CERFlow.'
])

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.users.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        <span>Template CSV</span>
    </a>

    <button type="button" onclick="document.getElementById('csv-input').click()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
        </svg>
        <span>Import CSV</span>
    </button>
    <input type="file" id="csv-input" accept=".csv" class="hidden" onchange="importCSV(this)">

    <button type="button" onclick="openUserModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>+ Tambah User</span>
    </button>
</div>
@endsection

@section('admin_content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Toolbar: Search & Role Filter --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex flex-1 items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <input type="text" id="search-input" oninput="renderUsers()" placeholder="Cari nama atau username..." 
                       class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <select id="role-filter" onchange="renderUsers()" 
                    class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors font-medium text-slate-700">
                <option value="all">Semua Role</option>
                <option value="guru">Guru (Admin)</option>
                <option value="siswa">Siswa</option>
            </select>
        </div>

        <div class="text-xs text-slate-500 font-medium">
            Total: <span id="user-count" class="font-semibold text-slate-900">{{ count($users ?? []) }}</span> Pengguna
        </div>
    </div>

    {{-- Users Table --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-16">ID</th>
                        <th class="py-3.5 px-4">Nama Lengkap</th>
                        <th class="py-3.5 px-4">Username</th>
                        <th class="py-3.5 px-4 text-center w-28">Role</th>
                        <th class="py-3.5 px-4">Gemini API Key</th>
                        <th class="py-3.5 px-4 text-right w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody id="user-table-body" class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($users ?? [] as $u)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 text-slate-400 font-mono">#{{ $u->id }}</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $u->namalengkap }}</td>
                            <td class="py-3.5 px-4 font-mono text-indigo-600">{{ $u->username }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $u->role === 'guru' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-400">
                                {{ $u->gemini_api_key ? substr($u->gemini_api_key, 0, 8) . '••••••••' : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button type="button" onclick="editUser({{ $u->id }})" class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded hover:bg-slate-100 transition-colors mr-1.5">
                                    Edit
                                </button>
                                <button type="button" onclick="deleteUser({{ $u->id }})" class="px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded hover:bg-red-50 transition-colors">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Tidak ada data pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- User Modal (Create & Edit) --}}
<div id="user-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 id="modal-title" class="text-sm font-semibold text-slate-900">Tambah Pengguna Baru</h3>
            <button type="button" onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <form id="user-form" onsubmit="saveUser(event)" class="p-6 space-y-4">
            <input type="hidden" id="user-id">

            <div>
                <label for="user-username" class="block text-xs font-semibold text-slate-700 mb-1">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" id="user-username" required placeholder="Contoh: siswa1" 
                       class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
            </div>

            <div>
                <label for="user-namalengkap" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" id="user-namalengkap" required placeholder="Contoh: Budi Santoso" 
                       class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
            </div>

            <div>
                <label for="user-role" class="block text-xs font-semibold text-slate-700 mb-1">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="user-role" required 
                        class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
                    <option value="siswa">Siswa (Mahasiswa)</option>
                    <option value="guru">Guru (Dosen / Admin)</option>
                </select>
            </div>

            <div>
                <label for="user-password" class="block text-xs font-semibold text-slate-700 mb-1">
                    Password <span id="password-hint" class="text-slate-400 font-normal">(wajib untuk baru)</span>
                </label>
                <input type="password" id="user-password" placeholder="••••••••" 
                       class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
            </div>

            <div id="gemini-key-group">
                <label for="user-gemini-key" class="block text-xs font-semibold text-slate-700 mb-1">
                    Gemini API Key (Opsional)
                </label>
                <input type="password" id="user-gemini-key" placeholder="AIzaSy..." 
                       class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-800">
                    Batal
                </button>
                <button type="submit" id="btn-save-user" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let allUsers = @json($users ?? []);

    function renderUsers() {
        const query = document.getElementById('search-input').value.toLowerCase().trim();
        const role = document.getElementById('role-filter').value;
        const tbody = document.getElementById('user-table-body');

        const filtered = allUsers.filter(u => {
            const matchQuery = (u.username && u.username.toLowerCase().includes(query)) ||
                               (u.namalengkap && u.namalengkap.toLowerCase().includes(query));
            const matchRole = (role === 'all') || (u.role === role);
            return matchQuery && matchRole;
        });

        document.getElementById('user-count').textContent = filtered.length;

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Tidak ada pengguna yang cocok dengan kriteria pencarian.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(u => `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="py-3.5 px-4 text-slate-400 font-mono">#${u.id}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-900">${escapeHtml(u.namalengkap)}</td>
                <td class="py-3.5 px-4 font-mono text-indigo-600">${escapeHtml(u.username)}</td>
                <td class="py-3.5 px-4 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${u.role === 'guru' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-700'}">
                        ${u.role}
                    </span>
                </td>
                <td class="py-3.5 px-4 font-mono text-slate-400">
                    ${u.gemini_api_key ? escapeHtml(u.gemini_api_key.substring(0, 8)) + '••••••••' : '-'}
                </td>
                <td class="py-3.5 px-4 text-right">
                    <button type="button" onclick="editUser(${u.id})" class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded hover:bg-slate-100 transition-colors mr-1.5">
                        Edit
                    </button>
                    <button type="button" onclick="deleteUser(${u.id})" class="px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded hover:bg-red-50 transition-colors">
                        Hapus
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function openUserModal() {
        document.getElementById('modal-title').textContent = 'Tambah Pengguna Baru';
        document.getElementById('user-id').value = '';
        document.getElementById('user-username').value = '';
        document.getElementById('user-namalengkap').value = '';
        document.getElementById('user-role').value = 'siswa';
        document.getElementById('user-password').value = '';
        document.getElementById('user-password').required = true;
        document.getElementById('password-hint').textContent = '(wajib untuk pengguna baru)';
        document.getElementById('user-gemini-key').value = '';
        document.getElementById('user-modal').classList.remove('hidden');
    }

    function closeUserModal() {
        document.getElementById('user-modal').classList.add('hidden');
    }

    function editUser(id) {
        const u = allUsers.find(item => item.id === id);
        if (!u) return;

        document.getElementById('modal-title').textContent = 'Edit Pengguna';
        document.getElementById('user-id').value = u.id;
        document.getElementById('user-username').value = u.username;
        document.getElementById('user-namalengkap').value = u.namalengkap;
        document.getElementById('user-role').value = u.role;
        document.getElementById('user-password').value = '';
        document.getElementById('user-password').required = false;
        document.getElementById('password-hint').textContent = '(kosongkan jika tidak diubah)';
        document.getElementById('user-gemini-key').value = u.gemini_api_key || '';
        document.getElementById('user-modal').classList.remove('hidden');
    }

    async function saveUser(e) {
        e.preventDefault();
        const id = document.getElementById('user-id').value;
        const payload = {
            id: id ? parseInt(id) : null,
            username: document.getElementById('user-username').value.trim(),
            namalengkap: document.getElementById('user-namalengkap').value.trim(),
            role: document.getElementById('user-role').value,
            password: document.getElementById('user-password').value,
            gemini_api_key: document.getElementById('user-gemini-key').value.trim()
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const btn = document.getElementById('btn-save-user');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        try {
            const res = await fetch('{{ route("admin.users.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && (data.success || data.status === 'success')) {
                if (id) {
                    const idx = allUsers.findIndex(item => item.id === parseInt(id));
                    if (idx !== -1) allUsers[idx] = data.data;
                } else {
                    allUsers.unshift(data.data);
                }
                closeUserModal();
                renderUsers();
                if (window.Toast) window.Toast('Data pengguna berhasil disimpan.', 'success');
            } else {
                const msg = data.message || Object.values(data.errors || {}).flat().join('\n') || 'Gagal menyimpan user.';
                if (window.Toast) window.Toast(msg, 'error');
                else alert(msg);
            }
        } catch (err) {
            console.error('Save user error:', err);
            if (window.Toast) window.Toast('Terjadi kesalahan jaringan.', 'error');
            else alert('Terjadi kesalahan jaringan.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan Pengguna';
        }
    }

    async function deleteUser(id) {
        if (!await CustomConfirm('Apakah Anda yakin ingin menghapus pengguna ini?')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const res = await fetch(`/admin/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await res.json();
            if (res.ok && (data.success || data.status === 'success')) {
                allUsers = allUsers.filter(item => item.id !== id);
                renderUsers();
                if (window.Toast) window.Toast('Pengguna berhasil dihapus.', 'success');
            } else {
                if (window.Toast) window.Toast(data.message || 'Gagal menghapus pengguna.', 'error');
                else alert(data.message || 'Gagal menghapus pengguna.');
            }
        } catch (err) {
            console.error('Delete user error:', err);
            if (window.Toast) window.Toast('Terjadi kesalahan jaringan.', 'error');
            else alert('Terjadi kesalahan jaringan.');
        }
    }

    async function importCSV(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        const formData = new FormData();
        formData.append('file', file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const res = await fetch('{{ route("admin.users.import") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await res.json();
            if (res.ok && (data.success || data.status === 'success')) {
                if (window.Toast) window.Toast(data.message || 'Import CSV selesai!', 'success');
                else await CustomAlert(data.message || 'Import CSV selesai!');
                window.location.reload();
            } else {
                if (window.Toast) window.Toast(data.message || 'Gagal mengimpor CSV.', 'error');
                else alert(data.message || 'Gagal mengimpor CSV.');
            }
        } catch (err) {
            console.error('Import error:', err);
            if (window.Toast) window.Toast('Terjadi kesalahan saat mengunggah file.', 'error');
            else alert('Terjadi kesalahan saat mengunggah file.');
        } finally {
            input.value = '';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }
</script>
@endsection
