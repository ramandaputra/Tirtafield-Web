@extends('layouts.app')

@section('title', 'Manajemen Pegawai')

@section('content')
<div x-data="{ addModal: false, editModal: false, selectedEmp: null }" class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Pegawai</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola data seluruh akun karyawan, direktur, dan staff IT.</p>
        </div>
        <button @click="addModal = true" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-[#00288E] hover:bg-[#001f70] text-white rounded-xl font-semibold text-sm shadow-md transition-all">
            <span class="material-symbols-rounded text-lg">person_add</span>
            <span>Tambah Pegawai Baru</span>
        </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form action="{{ route('web.employees') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2 relative flex items-center">
                <span class="material-symbols-rounded absolute left-3 text-slate-400 text-lg">search</span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, NIP/NIK, bidang, atau seksi..."
                    class="w-full pl-9 pr-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <select name="role" class="w-full py-2 px-3 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-slate-700">
                    <option value="">-- Semua Role --</option>
                    <option value="karyawan" {{ $role === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                    <option value="direktur" {{ $role === 'direktur' ? 'selected' : '' }}>Direktur</option>
                    <option value="staffIT" {{ $role === 'staffIT' ? 'selected' : '' }}>Staff IT (Superadmin)</option>
                </select>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-sm font-semibold transition-colors">
                    Filter
                </button>
                @if (!empty($q) || !empty($role) || !empty($department))
                <a href="{{ route('web.employees') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-medium transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Employee Mobile Cards List (Matching Flutter superadmin_employee_screen) -->
    <div class="space-y-3">
        @forelse ($employees as $emp)
        <div class="bg-white rounded-2xl border border-[#DAE2FD] p-3.5 shadow-xs space-y-2.5 hover:border-brandSecondary transition-all">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#00288E] to-[#316BF3] text-white font-bold flex items-center justify-center text-sm shadow-xs shrink-0">
                        {{ strtoupper(substr($emp->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-xs text-[#131B2E]">{{ $emp->name }}</div>
                        <div class="text-[10px] text-[#64748B] font-mono">{{ $emp->nik }} · {{ $emp->position }}</div>
                    </div>
                </div>
                <div>
                    @if ($emp->role === 'staffIT')
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-teal-50 text-roleStaffIT border border-teal-200 rounded-md">Staff IT</span>
                    @elseif ($emp->role === 'direktur')
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-purple-50 text-roleDirektur border border-purple-200 rounded-md">Direktur</span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-50 text-roleKaryawan border border-blue-200 rounded-md">Karyawan</span>
                    @endif
                </div>
            </div>

            <div class="bg-[#FAF8FF] p-2.5 rounded-xl border border-[#DAE2FD]/60 text-xs space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-[#64748B]">Bidang:</span>
                    <span class="font-semibold text-[#131B2E]">{{ $emp->department }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-[#64748B]">Penempatan:</span>
                    <span class="font-semibold text-[#131B2E] truncate max-w-[200px]">{{ $emp->office?->nama_kantor ?? $emp->office_name }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-[#64748B]">PIN Keamanan:</span>
                    <span class="font-mono font-bold text-[#00288E]">{{ $emp->pin ?: '123456' }}</span>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-1 border-t border-slate-100">
                <button type="button" @click="selectedEmp = {{ json_encode($emp) }}; editModal = true;"
                    class="px-3 py-1 bg-slate-100 hover:bg-blue-50 hover:text-[#0051D5] text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition-colors flex items-center space-x-1.5">
                    <span class="material-symbols-rounded text-sm">edit</span>
                    <span>Edit Data</span>
                </button>
                <button type="button" onclick="deactivateEmployee('{{ $emp->id }}', '{{ $emp->name }}')"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold border border-rose-200 transition-colors flex items-center" title="Nonaktifkan">
                    <span class="material-symbols-rounded text-sm">delete</span>
                </button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-[#DAE2FD] py-10 text-center text-slate-400 shadow-xs flex flex-col items-center">
            <span class="material-symbols-rounded text-5xl mb-2 text-slate-300">person_off</span>
            <p class="text-xs">Data pegawai tidak ditemukan.</p>
        </div>
        @endforelse

        @if ($employees->hasPages())
        <div class="p-3 bg-white rounded-2xl border border-[#DAE2FD]">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Tambah Pegawai -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="addModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Tambah Pegawai Baru</h3>
                    <button @click="addModal = false" class="text-slate-400 hover:text-slate-600 flex items-center">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <form action="{{ route('api.employees.store') }}" method="POST" class="space-y-4" onsubmit="event.preventDefault(); submitAddEmployee(this);">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">NIP / NIK Pegawai *</label>
                        <input type="text" name="nip" required placeholder="Contoh: P242102 atau EMP002"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                        <input type="text" name="name" required placeholder="Nama lengkap pegawai"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Role Akun</label>
                            <select name="role" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                                <option value="karyawan">Karyawan</option>
                                <option value="direktur">Direktur</option>
                                <option value="staffIT">Staff IT</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">PIN Awal</label>
                            <input type="text" name="pin" value="123456" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Bidang Penugasan</label>
                        <select name="department" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                            @foreach ($bidangList as $b)
                                <option value="{{ $b->nama }}">{{ $b->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Seksi Penugasan</label>
                        <input type="text" name="position" placeholder="Contoh: Seksi Pembacaan Meter" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kantor Penempatan</label>
                        <select name="office_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                            @foreach ($offices as $o)
                                <option value="{{ $o->id }}">{{ $o->nama_kantor }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                        <button type="button" @click="addModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-[#00288E] hover:bg-[#001f70] text-white font-semibold text-xs rounded-xl shadow">
                            Simpan Pegawai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pegawai -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="editModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Edit Data Pegawai</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 flex items-center">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <template x-if="selectedEmp">
                    <form class="space-y-4" onsubmit="event.preventDefault(); submitEditEmployee(this);">
                        <input type="hidden" name="id" :value="selectedEmp.id">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">NIP / NIK</label>
                            <input type="text" name="nip" :value="selectedEmp.nik" required
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                            <input type="text" name="name" :value="selectedEmp.name" required
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Role Akun</label>
                                <select name="role" x-model="selectedEmp.role" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                                    <option value="karyawan">Karyawan</option>
                                    <option value="direktur">Direktur</option>
                                    <option value="staffIT">Staff IT</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">PIN / Password</label>
                                <input type="text" name="pin" :value="selectedEmp.pin" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Bidang Penugasan</label>
                            <select name="department" x-model="selectedEmp.department" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                                @foreach ($bidangList as $b)
                                    <option value="{{ $b->nama }}">{{ $b->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Seksi Penugasan</label>
                            <input type="text" name="position" :value="selectedEmp.position" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kantor Penempatan</label>
                            <select name="office_id" x-model="selectedEmp.office_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                                @foreach ($offices as $o)
                                    <option value="{{ $o->id }}">{{ $o->nama_kantor }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                            <button type="button" @click="editModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-[#00288E] hover:bg-[#001f70] text-white font-semibold text-xs rounded-xl shadow">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function submitAddEmployee(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_superadmin = true;
        data.actor_role = 'staffIT';

        try {
            const res = await fetch("{{ route('api.employees.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                alert('Pegawai baru berhasil ditambahkan!');
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch (e) {
            alert('Terjadi kesalahan koneksi.');
        }
    }

    async function submitEditEmployee(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_superadmin = true;
        data.actor_role = 'staffIT';

        try {
            const res = await fetch("/api/employees/" + data.id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                alert('Data pegawai berhasil diperbarui!');
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch (e) {
            alert('Terjadi kesalahan koneksi.');
        }
    }

    async function deactivateEmployee(id, name) {
        if (!confirm('Apakah Anda yakin ingin menonaktifkan akun pegawai ' + name + '?')) {
            return;
        }

        try {
            const res = await fetch("/api/employees/" + id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    is_superadmin: true,
                    actor_role: 'staffIT'
                }),
            });
            const json = await res.json();
            if (json.success) {
                alert('Pegawai berhasil dinonaktifkan.');
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch (e) {
            alert('Terjadi kesalahan koneksi.');
        }
    }
</script>
@endsection
