@extends('layouts.app')

@section('title', 'Approval Presensi')

@section('content')
<div x-data="tirtafieldApprovalApp()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#131B2E] tracking-tight">Manajemen Approval Presensi</h1>
            <p class="text-xs text-[#64748B] mt-1">Review dan verifikasi permohonan presensi di luar radius, izin, cuti, dan koreksi absen.</p>
        </div>
    </div>

    <!-- Status Tabs Filter -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-3">
        <a href="{{ route('web.approvals', ['status' => 'all']) }}"
            class="px-4 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'all' ? 'bg-[#00288E] text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
            Semua
        </a>
        <a href="{{ route('web.approvals', ['status' => 'pending']) }}"
            class="px-4 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'pending' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
            Menunggu Review (Pending)
        </a>
        <a href="{{ route('web.approvals', ['status' => 'approved']) }}"
            class="px-4 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'approved' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
            Disetujui (Approved)
        </a>
        <a href="{{ route('web.approvals', ['status' => 'rejected']) }}"
            class="px-4 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'rejected' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
            Ditolak (Rejected)
        </a>
    </div>

    <!-- Approval Cards List (Matching Flutter _buildApprovalCard) -->
    <div class="space-y-3">
        @forelse ($approvals as $item)
        <div class="bg-white rounded-2xl border border-[#DAE2FD] p-3.5 shadow-xs space-y-2.5 hover:border-brandSecondary transition-all cursor-pointer"
            @click="openAttendanceDetail({{ json_encode($item->toDetailArray($userOffice ?? null)) }})">
            
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#00288E] to-[#316BF3] text-white font-bold flex items-center justify-center text-xs shadow-xs shrink-0">
                        {{ strtoupper(substr($item->pgw_nama, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-xs text-[#131B2E]">{{ $item->pgw_nama }}</div>
                        <div class="text-[10px] text-[#64748B] font-mono">{{ $item->nik }} · {{ $item->tanggal ? $item->tanggal->format('d/m/Y') : '-' }} {{ $item->waktu }}</div>
                    </div>
                </div>
                <div>
                    @if ($item->approval_status === 'pending')
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-purple-100 text-[#7C3AED] rounded-md">Menunggu</span>
                    @elseif ($item->approval_status === 'approved')
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded-md">Disetujui</span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 rounded-md">Ditolak</span>
                    @endif
                </div>
            </div>

            <div class="bg-[#FAF8FF] p-2.5 rounded-xl border border-[#DAE2FD]/60 text-xs">
                <div class="flex items-center justify-between mb-1">
                    <span class="px-2 py-0.5 text-[9.5px] font-bold rounded bg-blue-50 text-[#0051D5] border border-blue-200">
                        {{ $item->formatted_tipe_pengajuan ?: $item->tipe_pengajuan }}
                    </span>
                    @if ($item->slot_type)
                    <span class="text-[10px] text-[#64748B]">Slot: {{ $item->slot_type }}</span>
                    @endif
                </div>
                <p class="text-[11px] text-[#131B2E] line-clamp-2 mt-1">{{ $item->catatan ?: 'Tanpa keterangan tambahan.' }}</p>
                @if ($item->review_notes)
                <div class="text-[10.5px] text-purple-700 italic mt-1 pt-1 border-t border-purple-100">
                    Catatan Review: "{{ $item->review_notes }}"
                </div>
                @endif
            </div>

            <div class="flex items-center justify-between pt-1">
                <button type="button" @click.stop="openAttendanceDetail({{ json_encode($item->toDetailArray($userOffice ?? null)) }})"
                    class="inline-flex items-center space-x-1.5 px-3 py-1 bg-white hover:bg-blue-50 text-[#0051D5] rounded-lg text-xs font-bold border border-[#DAE2FD] shadow-2xs transition-colors">
                    <span class="material-symbols-rounded text-sm">visibility</span>
                    <span>Lihat Rincian</span>
                </button>

                @if ($item->approval_status === 'pending')
                <div class="flex items-center space-x-2">
                    <button type="button" @click.stop="reviewApprovalModal({{ $item->id }}, 'rejected', '{{ $item->pgw_nama }}', '{{ $item->tipe_pengajuan }}')"
                        class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 rounded-lg text-xs font-semibold border border-rose-200 transition-colors">
                        Tolak
                    </button>
                    <button type="button" @click.stop="reviewApprovalModal({{ $item->id }}, 'approved', '{{ $item->pgw_nama }}', '{{ $item->tipe_pengajuan }}')"
                        class="px-3.5 py-1 bg-[#10B981] hover:bg-emerald-600 text-white rounded-lg text-xs font-bold shadow-xs transition-colors">
                        Setujui
                    </button>
                </div>
                @else
                <span class="text-[10.5px] text-slate-400">Selesai ({{ $item->reviewed_by ?: 'Direktur' }})</span>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-[#DAE2FD] py-10 text-center text-slate-400 shadow-xs flex flex-col items-center">
            <span class="material-symbols-rounded text-5xl mb-2 text-slate-300">assignment_turned_in</span>
            <p class="text-xs">Tidak ada data approval yang sesuai.</p>
        </div>
        @endforelse

        @if ($approvals->hasPages())
        <div class="p-3 bg-white rounded-2xl border border-[#DAE2FD]">
            {{ $approvals->links() }}
        </div>
        @endif
    </div>

    <!-- Review Confirmation Modal -->
    <div x-show="reviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="reviewModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-xl space-y-3">
                <h3 class="text-sm font-bold text-[#131B2E]" x-text="reviewData.action === 'approved' ? 'Setujui Pengajuan' : 'Tolak Pengajuan'"></h3>
                <p class="text-xs text-[#64748B]">Apakah Anda yakin ingin memproses permohonan dari <strong x-text="reviewData.name"></strong>?</p>
                <div>
                    <label class="block text-[11px] text-[#64748B] mb-1">Catatan Direktur (opsional)</label>
                    <textarea x-model="reviewNotes" rows="2" class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-xl outline-none" placeholder="Catatan..."></textarea>
                </div>
                <div class="flex justify-end space-x-2 pt-1">
                    <button type="button" @click="reviewModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="button" @click="submitApprovalReview()" class="px-4 py-1.5 text-white text-xs font-bold rounded-xl shadow-xs"
                        :class="reviewData.action === 'approved' ? 'bg-[#10B981]' : 'bg-[#EF4444]'"
                        x-text="reviewData.action === 'approved' ? 'Ya, Setujui' : 'Ya, Tolak'"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Detail Bottom Sheet -->
    <x-attendance-detail-modal />

</div>
@endsection

@section('scripts')
<script>
    function tirtafieldApprovalApp() {
        return {
            reviewModal: false,
            reviewData: {},
            reviewNotes: '',
            detailModal: false,
            detailPhotoModal: false,
            detailData: {
                id: null,
                userName: '',
                nip: '',
                department: '',
                recordedTimeStr: '',
                waktu: '',
                status: 'Tepat Waktu',
                isLate: false,
                lateReason: '',
                lateMinutes: null,
                cleanPurpose: '',
                locationName: '',
                address: '',
                latitude: 0.92025,
                longitude: 104.4607,
                distancePlacement: '0 m',
                officePlacement: '',
                targetLat: 0.92025,
                targetLng: 104.4607,
                targetRad: 100,
                fotoUrl: '',
                slotTitle: 'Presensi Masuk',
                isApproval: false,
                isInsideRadius: true,
                accurateDistance: '0 m'
            },
            detailMap: null,
            detailTileLayer: null,
            detailMapMode: 'satellite',

            reviewApprovalModal(id, action, name, type) {
                this.reviewData = { id, action, name, type };
                this.reviewNotes = '';
                this.reviewModal = true;
            },

            async submitApprovalReview() {
                try {
                    const res = await fetch("{{ route('api.approvals.review') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            approval_id: this.reviewData.id,
                            status: this.reviewData.action,
                            review_notes: this.reviewNotes,
                            reviewer_name: "{{ auth()->user()?->name ?? 'Direktur' }}"
                        })
                    });
                    alert('Persetujuan berhasil diproses!');
                    this.reviewModal = false;
                    window.location.reload();
                } catch(e) {
                    alert('Gagal memproses review persetujuan.');
                }
            },

            openAttendanceDetail(record, isApprovalOverride = null) {
                if (!record) return;

                const rawPurpose = (record.absenUntukApa || record.catatan || '').trim();
                const rawStatus = (record.status || '').trim();

                let isLate = record.isLate || 
                             rawStatus.toLowerCase().includes('terlambat') || 
                             rawPurpose.toLowerCase().includes('terlambat');

                let lateReason = '';
                let lateMinutes = null;
                let cleanPurpose = rawPurpose;

                const bracketMatch = /\[TERLAMBAT(?:\s*\(?(\d+)\s*(?:mnt|menit|m)?\)?)?(?:\s*[-–:]\s*[^\]:]*)?[:\-–]?\s*([^\]]*)\]/i.exec(rawPurpose);
                if (bracketMatch) {
                    if (bracketMatch[1]) lateMinutes = parseInt(bracketMatch[1]);
                    if (bracketMatch[2] && bracketMatch[2].trim()) lateReason = bracketMatch[2].trim();
                }
                if (!lateReason) {
                    const simpleMatch = /\[TERLAMBAT[^\]]*\]\s*([^\[\n]+)/i.exec(rawPurpose);
                    if (simpleMatch && simpleMatch[1] && simpleMatch[1].trim() && !simpleMatch[1].toLowerCase().includes('presensi lapangan')) {
                        lateReason = simpleMatch[1].trim();
                    }
                }
                if (lateMinutes === null) {
                    const minsMatch = /\(?(\d+)\s*(?:mnt|menit)\)?/i.exec(rawPurpose) || /\(?(\d+)\s*(?:mnt|menit)\)?/i.exec(rawStatus);
                    if (minsMatch) lateMinutes = parseInt(minsMatch[1]);
                }

                cleanPurpose = rawPurpose
                    .replace(/\[TERLAMBAT[^\]]*\]\s*/gi, '')
                    .replace(/\[(?:Presensi\s+(?:Lapangan|Kantor)[^\]]*)\]\s*/gi, '')
                    .trim();

                if (!lateReason && cleanPurpose && !cleanPurpose.toLowerCase().includes('presensi kehadiran operasional') && !cleanPurpose.toLowerCase().includes('presensi lapangan')) {
                    lateReason = cleanPurpose;
                }
                if (!cleanPurpose) {
                    cleanPurpose = 'Presensi kehadiran operasional lapangan PERUMDA Tirta Kepri';
                }

                const empLat = parseFloat(record.latitude || record.targetLat || 0.92025);
                const empLng = parseFloat(record.longitude || record.targetLng || 104.4607);
                const officeLat = parseFloat(record.targetLat || 0.92025);
                const officeLng = parseFloat(record.targetLng || 104.4607);
                const targetRad = parseFloat(record.targetRad || 100);

                const R = 6371000;
                const dLat = (officeLat - empLat) * Math.PI / 180;
                const dLng = (officeLng - empLng) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                          Math.cos(empLat * Math.PI / 180) * Math.cos(officeLat * Math.PI / 180) *
                          Math.sin(dLng / 2) * Math.sin(dLng / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                const distMeters = Math.round(R * c);

                let accurateDistance = record.distancePlacement || (distMeters + ' m');
                if (!record.distancePlacement || record.distancePlacement === '0 m' || record.distancePlacement.includes('m')) {
                    if (distMeters >= 1000) {
                        accurateDistance = (distMeters / 1000).toFixed(1) + ' km';
                    } else {
                        accurateDistance = distMeters + ' m';
                    }
                }

                const isInsideRadius = distMeters <= targetRad;

                const isApproval = (isApprovalOverride !== null)
                    ? isApprovalOverride
                    : (record.isApproval || record.approval_status === 'pending');

                this.detailData = {
                    id: record.id,
                    userName: record.userName || record.pgw_nama || 'Pegawai',
                    nip: record.nip || record.nik || '-',
                    department: record.department || 'Operasional Lapangan',
                    recordedTimeStr: record.recordedTimeStr || (record.waktu ? record.waktu + ' WIB' : ''),
                    waktu: record.waktu || '',
                    status: isLate ? (lateMinutes ? `Terlambat (${lateMinutes} Menit)` : 'Terlambat') : 'Tepat Waktu',
                    isLate: isLate,
                    lateReason: lateReason,
                    lateMinutes: lateMinutes,
                    cleanPurpose: cleanPurpose,
                    locationName: record.locationName || record.lokasi || 'Lokasi Tugas Lapangan',
                    address: record.address || record.alamat || (record.lokasi || `${empLat.toFixed(6)}, ${empLng.toFixed(6)}`),
                    latitude: empLat,
                    longitude: empLng,
                    distancePlacement: record.distancePlacement || '0 m',
                    officePlacement: record.officePlacement || 'Kantor PERUMDA Tirta Kepri',
                    targetLat: officeLat,
                    targetLng: officeLng,
                    targetRad: targetRad,
                    fotoUrl: record.fotoUrl || record.foto_url || '',
                    slotTitle: record.slotTitle || 'Presensi Lapangan',
                    isApproval: isApproval,
                    isInsideRadius: isInsideRadius,
                    accurateDistance: accurateDistance
                };

                this.detailModal = true;

                this.$nextTick(() => {
                    setTimeout(() => this.initDetailMap(), 300);
                });
            },

            setDetailMapMode(mode) {
                this.detailMapMode = mode;
                if (!this.detailMap) return;
                if (this.detailTileLayer) {
                    this.detailMap.removeLayer(this.detailTileLayer);
                    this.detailTileLayer = null;
                }
                const gKey = window.GOOGLE_MAPS_API_KEY || '{{ config("services.google.maps_api_key") }}' || 'AIzaSyDpm1SPneUjheEMnk3TDOP38js6TQLmXps';
                if (mode === 'satellite') {
                    const tileUrl = gKey 
                        ? `https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&key=${gKey}`
                        : 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
                    const tileOpts = gKey
                        ? { subdomains: ['0', '1', '2', '3'], maxZoom: 20, attribution: '&copy; Google Maps' }
                        : { maxZoom: 19, attribution: '&copy; Esri World Imagery' };
                    this.detailTileLayer = L.tileLayer(tileUrl, tileOpts).addTo(this.detailMap);
                } else {
                    const tileUrl = gKey 
                        ? `https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&key=${gKey}`
                        : 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                    const tileOpts = gKey
                        ? { subdomains: ['0', '1', '2', '3'], maxZoom: 20, attribution: '&copy; Google Maps' }
                        : { maxZoom: 19, attribution: '&copy; CartoDB' };
                    this.detailTileLayer = L.tileLayer(tileUrl, tileOpts).addTo(this.detailMap);
                }
            },

            initDetailMap() {
                if (this.detailMap) {
                    this.detailMap.remove();
                    this.detailMap = null;
                    this.detailTileLayer = null;
                }
                const mapEl = document.getElementById('attendanceDetailMap');
                if (!mapEl) return;

                const lat = this.detailData.latitude;
                const lng = this.detailData.longitude;
                const tLat = this.detailData.targetLat;
                const tLng = this.detailData.targetLng;
                const tRad = this.detailData.targetRad;

                this.detailMap = L.map('attendanceDetailMap', {
                    zoomControl: true,
                    attributionControl: false
                }).setView([lat, lng], 15);

                // Tampilkan mode satelit secara default atau sesuai pilihan
                this.setDetailMapMode(this.detailMapMode || 'satellite');

                const officeIcon = L.divIcon({
                    className: 'custom-office-pin',
                    html: '<div style="background:#059669;color:#ffffff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(5,150,105,0.45);border:2.5px solid #ffffff;"><span class="material-symbols-rounded" style="font-size:16px;line-height:1;">apartment</span></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                const mOffice = L.marker([tLat, tLng], { icon: officeIcon }).addTo(this.detailMap);
                mOffice.bindPopup(`<b>${this.detailData.officePlacement}</b><br><span style="color:#059669;font-weight:600;font-size:11px;">Kantor Penempatan</span>`);

                L.circle([tLat, tLng], {
                    color: '#059669',
                    fillColor: '#10B981',
                    fillOpacity: 0.16,
                    radius: tRad,
                    weight: 2,
                    dashArray: '4, 4'
                }).addTo(this.detailMap);

                const userIcon = L.divIcon({
                    className: 'custom-user-detail-pin',
                    html: '<div style="background:#2563eb;color:#ffffff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 10px rgba(37,99,235,0.55);border:2.5px solid #ffffff;"><span class="material-symbols-rounded" style="font-size:14px;line-height:1;">my_location</span></div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                const mUser = L.marker([lat, lng], { icon: userIcon }).addTo(this.detailMap);
                const popupHtml = `<b>${this.detailData.locationName}</b><br>Pegawai: ${this.detailData.userName}<br>Waktu: ${this.detailData.recordedTimeStr}<br>Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                mUser.bindPopup(popupHtml);

                const line = L.polyline([[lat, lng], [tLat, tLng]], {
                    color: '#2563eb',
                    weight: 2.5,
                    dashArray: '5, 5',
                    opacity: 0.85
                }).addTo(this.detailMap);

                line.bindTooltip(`<b>${this.detailData.accurateDistance}</b>`, {
                    permanent: true,
                    direction: 'center',
                    className: 'distance-tooltip'
                });

                try {
                    const bounds = L.latLngBounds([[lat, lng], [tLat, tLng]]);
                    this.detailMap.fitBounds(bounds, { padding: [35, 35] });
                } catch (e) {}

                setTimeout(() => {
                    if (this.detailMap) this.detailMap.invalidateSize();
                }, 200);
            }
        };
    }
</script>
@endsection
