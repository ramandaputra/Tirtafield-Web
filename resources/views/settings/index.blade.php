@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div>
        <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">Pengaturan Sistem Presensi</h1>
        <p class="text-xs text-slate-500 mt-0.5">Konfigurasi 4 slot jam kerja presensi harian dan batas geofencing radius kantor.</p>
    </div>

    @php
        $op = $settings['operational_hours'] ?? [];
        $geo = $settings['geofencing'] ?? [];
    @endphp

    <div class="space-y-4">
        <!-- Card 1: 4 Slot Jam Kerja Presensi -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100 mb-4">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg shrink-0">
                    <span class="material-symbols-rounded text-2xl">schedule</span>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">4 Slot Waktu Presensi Harian</h2>
                    <p class="text-[11px] text-slate-400">Aturan jam mulai dan selesai setiap sesi presensi</p>
                </div>
            </div>

            <form action="{{ route('api.settings.operational') }}" method="POST" onsubmit="event.preventDefault(); saveOperational(this);" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            🟢 Slot 1: Absen Masuk
                        </label>
                        <input type="time" name="masuk" value="{{ $op['masuk'] ?? '08:00' }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            🟠 Slot 2: Istirahat Keluar
                        </label>
                        <input type="time" name="istirahat_keluar" value="{{ $op['istirahat_keluar'] ?? '12:00' }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            🟣 Slot 3: Istirahat Masuk
                        </label>
                        <input type="time" name="istirahat_masuk" value="{{ $op['istirahat_masuk'] ?? '13:00' }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            🔴 Slot 4: Absen Pulang
                        </label>
                        <input type="time" name="pulang" value="{{ $op['pulang'] ?? '17:00' }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Toleransi Terlambat
                        </label>
                        <div class="relative">
                            <input type="number" name="toleransi_menit" value="{{ $op['toleransi_menit'] ?? 15 }}" class="w-full pl-3 pr-14 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 font-mono">
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400 font-medium">menit</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Hari Kerja Harian
                        </label>
                        <input type="text" name="hari_kerja" value="{{ $op['hari_kerja'] ?? 'Senin - Jumat' }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-[#00288E] hover:bg-[#001f70] text-white font-semibold text-xs rounded-xl shadow transition-colors">
                        Simpan Jam Operasional
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Geofencing Radius Kantor -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100 mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                    <span class="material-symbols-rounded text-2xl">location_on</span>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Geofencing & Lokasi Kantor</h2>
                    <p class="text-[11px] text-slate-400">Verifikasi presensi berbasis titik koordinat GPS & radius</p>
                </div>
            </div>

            <!-- List Offices -->
            <div class="space-y-3 mb-5 max-h-72 overflow-y-auto pr-1">
                @foreach ($offices as $o)
                <div class="p-3 rounded-xl border border-slate-200/80 bg-slate-50 flex items-start justify-between">
                    <div>
                        <div class="font-bold text-xs text-slate-800 flex items-center">
                            <span>{{ $o->nama_kantor }}</span>
                            @if ($o->is_primary)
                            <span class="ml-2 px-1.5 py-0.5 text-[9px] font-bold bg-cyan-100 text-cyan-800 rounded">KANTOR PUSAT</span>
                            @endif
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $o->alamat }}</div>
                        <div class="text-[10px] text-slate-400 font-mono mt-1">
                            Lat: {{ $o->latitude }}, Lng: {{ $o->longitude }} • Radius: {{ $o->radius }}m
                        </div>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded">
                        {{ $o->radius }} m
                    </span>
                </div>
                @endforeach
            </div>

            <!-- Add/Update Office Quick Form -->
            <form action="{{ route('api.offices.store') }}" method="POST" onsubmit="event.preventDefault(); saveOffice(this);" class="space-y-3 pt-3 border-t border-slate-100">
                <div class="text-xs font-bold text-slate-700">Tambah Lokasi Kantor / Pos Jaga:</div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="name" required placeholder="Nama Kantor Baru" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg">
                    <input type="text" name="address" placeholder="Alamat Kantor" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg">
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" step="any" name="latitude" required placeholder="Latitude" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg font-mono">
                    <input type="number" step="any" name="longitude" required placeholder="Longitude" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg font-mono">
                    <input type="number" name="radius" value="100" placeholder="Radius (m)" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg font-mono">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs rounded-xl shadow transition-colors">
                        Simpan Lokasi Kantor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function saveOperational(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch("{{ route('api.settings.operational') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                alert('Jadwal operasional presensi berhasil disimpan!');
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch (e) {
            alert('Terjadi kesalahan koneksi.');
        }
    }

    async function saveOffice(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch("{{ route('api.offices.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                alert('Lokasi kantor berhasil disimpan!');
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

