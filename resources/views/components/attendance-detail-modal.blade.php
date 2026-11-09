{{-- ATTENDANCE DETAIL BOTTOM SHEET COMPONENT (Matching Flutter AttendanceDetailBottomSheet.dart exactly) --}}
<div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen text-center sm:items-center sm:p-4">
        <!-- Backdrop -->
        <div @click="detailModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <!-- Bottom Sheet Modal Container (matching Flutter RoundedRectangleBorder borderRadius vertical 22px, max-h 90vh, max-w 600px) -->
        <div class="relative bg-[#FAF8FF] rounded-t-[22px] sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-[520px] flex flex-col max-h-[92vh]">
            
            <!-- 1. Drag Handle (Flutter width: 38, height: 4, radius: 2) -->
            <div class="flex justify-center pt-2.5 pb-1">
                <div class="w-9 h-1 rounded-full bg-slate-300"></div>
            </div>

            <!-- 2. Header: Title, Slot Badge & Close Button -->
            <div class="px-4 py-2 flex items-center justify-between border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-rounded text-xl text-[#0051D5]">assignment_ind</span>
                    <h2 class="text-base font-bold text-[#131B2E]">Rincian Riwayat Presensi</h2>
                </div>
                <div class="flex items-center space-x-2">
                    <template x-if="detailData.slotTitle">
                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#0051D5]/10 text-[#0051D5]" x-text="detailData.slotTitle"></span>
                    </template>
                    <button type="button" @click="detailModal = false" class="w-7 h-7 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <span class="material-symbols-rounded text-lg">close</span>
                    </button>
                </div>
            </div>

            <!-- Scrollable Detail Body -->
            <div class="p-4 overflow-y-auto space-y-3.5">
                
                <!-- ========================================== -->
                <!-- 1. NAMA, NIK, DIVISI + STATUS TEPAT WAKTU/TERLAMBAT -->
                <!-- ========================================== -->
                <div class="p-3 bg-white rounded-2xl border border-[#DAE2FD]/70 shadow-xs flex items-start space-x-3">
                    <!-- Thumbnail 44x55 px -->
                    <div class="w-11 h-[55px] rounded-lg border border-[#DAE2FD] overflow-hidden bg-purple-50 flex items-center justify-center shrink-0">
                        <template x-if="detailData.fotoUrl">
                            <img :src="detailData.fotoUrl" alt="Thumbnail" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!detailData.fotoUrl">
                            <span class="text-lg font-bold text-[#0051D5]" x-text="(detailData.userName || 'P').charAt(0).toUpperCase()"></span>
                        </template>
                    </div>

                    <!-- User Meta & Status Badge -->
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-[14.5px] text-[#131B2E] truncate" x-text="detailData.userName"></div>
                        <div class="text-[11.5px] text-[#64748B] font-medium mt-0.5">
                            NIK: <span x-text="detailData.nip"></span> • <span x-text="detailData.department"></span>
                        </div>
                        <div class="mt-2 flex flex-col space-y-1.5 w-full">
                            <!-- Status Terlambat vs Tepat Waktu -->
                            <template x-if="detailData.isLate">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#FEF3C7] text-[#D97706] border border-amber-300/50 flex items-center space-x-1.5">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">schedule</span>
                                    <span class="truncate min-w-0" x-text="detailData.status"></span>
                                </div>
                            </template>
                            <template x-if="!detailData.isLate">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#DCFCE7] text-[#16A34A] border border-emerald-300/50 flex items-center space-x-1.5">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">check_circle</span>
                                    <span class="truncate min-w-0">Tepat Waktu</span>
                                </div>
                            </template>

                            <!-- Review Status & Reviewer Info -->
                            <template x-if="!detailData.isApproval && detailData.approval_status === 'approved'">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#DCFCE7] text-[#16A34A] border border-emerald-300/50 flex items-center space-x-1.5">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">check_circle</span>
                                    <span class="truncate min-w-0">Disetujui<span x-show="detailData.reviewed_at" x-text="' pada pukul ' + (detailData.reviewed_at.includes(' ') ? detailData.reviewed_at.split(' ')[1] : detailData.reviewed_at) + ' WIB'"></span> oleh <span x-text="detailData.reviewed_by || 'Direktur'"></span></span>
                                </div>
                            </template>
                            <template x-if="!detailData.isApproval && detailData.approval_status === 'rejected'">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#FEE2E2] text-[#DC2626] border border-rose-300/50 flex items-center space-x-1.5">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">highlight_off</span>
                                    <span class="truncate min-w-0">Ditolak<span x-show="detailData.reviewed_at" x-text="' pada pukul ' + (detailData.reviewed_at.includes(' ') ? detailData.reviewed_at.split(' ')[1] : detailData.reviewed_at) + ' WIB'"></span> oleh <span x-text="detailData.reviewed_by || 'Direktur'"></span></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 2. BUKTI SWAFOTO PRESENSI -->
                <!-- ========================================== -->
                <div>
                    <div class="flex items-center space-x-1.5 mb-1.5">
                        <span class="material-symbols-rounded text-base text-[#0051D5]">photo_camera</span>
                        <span class="text-xs font-bold text-[#131B2E]">Bukti Swafoto Presensi</span>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <!-- Clean Photo Viewport (4:5 Portrait Ratio, Unobstructed Full View) -->
                        <div @click="if (detailData.fotoUrl) detailPhotoModal = true"
                            class="relative w-full aspect-[4/5] bg-slate-950 flex items-center justify-center cursor-pointer group overflow-hidden">
                            
                            <template x-if="detailData.fotoUrl">
                                <img :src="detailData.fotoUrl" alt="Swafoto Pegawai" class="w-full h-full object-contain group-hover:scale-[1.02] transition-transform duration-200">
                            </template>

                            <template x-if="!detailData.fotoUrl">
                                <div class="py-16 text-center text-slate-400 space-y-2">
                                    <span class="material-symbols-rounded text-5xl text-slate-600">photo_camera</span>
                                    <div class="text-xs font-semibold text-slate-300">Swafoto Presensi Pegawai</div>
                                    <div class="text-[10.5px] text-slate-500">Dokumentasi Kehadiran Kamera</div>
                                </div>
                            </template>

                            <!-- Subtle Floating Zoom Pill (Pojok Kanan Atas) -->
                            <template x-if="detailData.fotoUrl">
                                <div class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md border border-white/20 text-white text-[10px] font-semibold flex items-center space-x-1 shadow-sm transition-colors">
                                    <span class="material-symbols-rounded text-xs">zoom_in</span>
                                    <span>Ketuk Perbesar</span>
                                </div>
                            </template>
                        </div>

                        <!-- Caption & Verification Bar (Clean outside of photo) -->
                        <div class="bg-[#FAF8FF] px-3.5 py-2.5 flex items-center justify-between border-t border-slate-100">
                            <div class="flex items-center space-x-2 text-[11.5px] min-w-0">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="font-bold text-[#131B2E] truncate">Swafoto Asli Kamera</span>
                                <span class="text-[#94A3B8]">·</span>
                                <span class="text-[#64748B] font-mono text-[11px] truncate" x-text="detailData.waktu ? (detailData.waktu + ' WIB') : detailData.recordedTimeStr"></span>
                            </div>
                            <template x-if="detailData.fotoUrl">
                                <button type="button" @click="detailPhotoModal = true"
                                    class="inline-flex items-center space-x-1 text-[11.5px] font-bold text-[#0051D5] hover:underline shrink-0 ml-2">
                                    <span class="material-symbols-rounded text-xs">open_in_full</span>
                                    <span>Lihat Penuh</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 3. WAKTU PEREKAMAN PRESENSI -->
                <!-- ========================================== -->
                <div>
                    <div class="flex items-center space-x-1.5 mb-1.5">
                        <span class="material-symbols-rounded text-base text-[#0051D5]">schedule</span>
                        <span class="text-xs font-bold text-[#131B2E]">Waktu Perekaman Presensi</span>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200/70 p-3 flex items-center space-x-3 shadow-xs">
                        <div class="w-8 h-8 rounded-lg bg-[#00288E]/10 flex items-center justify-center text-[#00288E] shrink-0">
                            <span class="material-symbols-rounded text-base">schedule</span>
                        </div>
                        <div>
                            <div class="text-[12.5px] font-bold text-[#131B2E]" x-text="detailData.recordedTimeStr"></div>
                            <div class="text-[10.5px] text-[#64748B] mt-0.5">Metode Presensi: Swafoto Kamera Langsung</div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 4. ALASAN KETERLAMBATAN RESMI (JIKA TERLAMBAT) -->
                <!-- ========================================== -->
                <template x-if="detailData.isLate && detailData.lateReason">
                    <div>
                        <div class="flex items-center space-x-1.5 mb-1.5">
                            <span class="material-symbols-rounded text-base text-[#D97706]">warning</span>
                            <span class="text-xs font-bold text-[#131B2E]">Keterangan Keterlambatan</span>
                        </div>

                        <div class="bg-gradient-to-br from-[#FFFBEB] to-[#FEF3C7] rounded-2xl border border-[#F59E0B]/45 shadow-xs overflow-hidden">
                            <!-- Alert Header -->
                            <div class="px-3.5 pt-3 pb-2 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-md bg-[#D97706]/15 flex items-center justify-center text-[#B45309]">
                                        <span class="material-symbols-rounded text-xs">warning</span>
                                    </div>
                                    <span class="text-[13px] font-bold text-[#92400E]">Alasan Keterlambatan Resmi</span>
                                </div>
                                <div class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-[#FDE68A] border border-[#F59E0B]/50 text-[#92400E] text-[10.5px] font-extrabold">
                                    <span class="material-symbols-rounded text-[11px]">schedule</span>
                                    <span x-text="detailData.lateMinutes ? (detailData.lateMinutes + ' Menit') : 'Terlambat'"></span>
                                </div>
                            </div>

                            <!-- Quote Box -->
                            <div class="mx-3.5 my-1 p-3 bg-white/95 rounded-xl border-l-[3.5px] border-l-[#D97706] border border-[#FDE68A]/80">
                                <div class="flex items-center space-x-1.5 text-[11px] font-semibold text-[#B45309]">
                                    <span class="material-symbols-rounded text-xs text-[#D97706]">format_quote</span>
                                    <span>Pernyataan / Alasan Karyawan:</span>
                                </div>
                                <div class="mt-1 text-[13.5px] font-bold text-[#78350F] leading-snug">
                                    "<span x-text="detailData.lateReason"></span>"
                                </div>
                            </div>

                            <!-- Verification Footer -->
                            <div class="px-3.5 py-2 flex items-center text-[10.5px] text-[#92400E] italic">
                                <span>Pernyataan keterlambatan telah diverifikasi ke sistem audit absensi.</span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========================================== -->
                <!-- 5. KETERANGAN KEPERLUAN PRESENSI -->
                <!-- ========================================== -->
                <div>
                    <div class="flex items-center space-x-1.5 mb-1.5">
                        <span class="material-symbols-rounded text-base text-[#0051D5]">description</span>
                        <span class="text-xs font-bold text-[#131B2E]">Keterangan Keperluan Presensi</span>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200/70 p-3 flex items-start space-x-2.5 shadow-xs">
                        <span class="material-symbols-rounded text-base text-[#0051D5] mt-0.5 shrink-0">work</span>
                        <div class="text-xs font-semibold text-[#131B2E] leading-relaxed" x-text="detailData.cleanPurpose || 'Penugasan operasional Absensi Lapangan'"></div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 6. LOKASI & JARAK KE KANTOR PENEMPATAN -->
                <!-- ========================================== -->
                <div>
                    <div class="flex items-center space-x-1.5 mb-1.5">
                        <span class="material-symbols-rounded text-base text-[#0051D5]">near_me</span>
                        <span class="text-xs font-bold text-[#131B2E]">Lokasi & Jarak ke Kantor Penempatan</span>
                    </div>

                    <!-- Highlight Card Jarak -->
                    <div class="p-3 rounded-xl border transition-all"
                        :class="detailData.isInsideRadius ? 'bg-[#F0FDF4] border-[#86EFAC]' : 'bg-[#FFFBEB] border-[#FCD34D]'">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                :class="detailData.isInsideRadius ? 'bg-[#DCFCE7] text-[#16A34A]' : 'bg-[#FEF3C7] text-[#D97706]'">
                                <span class="material-symbols-rounded text-base"
                                    x-text="detailData.isInsideRadius ? 'apartment' : 'straighten'"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center flex-wrap gap-1.5">
                                    <span class="text-[11.5px] font-semibold"
                                        :class="detailData.isInsideRadius ? 'text-[#16A34A]' : 'text-[#D97706]'">
                                        Jarak ke Kantor:
                                    </span>
                                    <span class="text-[14.5px] font-extrabold"
                                        :class="detailData.isInsideRadius ? 'text-[#16A34A]' : 'text-[#D97706]'"
                                        x-text="detailData.accurateDistance"></span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border"
                                        :class="detailData.isInsideRadius ? 'bg-[#DCFCE7] text-[#16A34A] border-[#86EFAC]' : 'bg-[#FEF3C7] text-[#D97706] border-[#FCD34D]'"
                                        x-text="detailData.isInsideRadius ? ('Di Dalam Radius (' + Math.round(detailData.targetRad) + ' m)') : ('Di Luar Radius (' + Math.round(detailData.targetRad) + ' m)')">
                                    </span>
                                </div>
                                <div class="text-[11px] font-medium truncate mt-0.5"
                                    :class="detailData.isInsideRadius ? 'text-[#16A34A]/90' : 'text-[#D97706]/90'">
                                    <span x-text="detailData.officePlacement"></span> • <span x-text="detailData.locationName"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Leaflet Mini Map -->
                    <div class="mt-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden relative">
                        <!-- Satelit / Peta Switcher -->
                        <div class="absolute top-2.5 right-2.5 z-[1000] flex bg-white/95 backdrop-blur-xs rounded-xl p-0.5 border border-slate-200 shadow-xs">
                            <button type="button" @click="setDetailMapMode('satellite')"
                                :class="(detailMapMode || 'satellite') === 'satellite' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                                class="px-2.5 py-1 text-[11px] rounded-lg flex items-center space-x-1 transition-all">
                                <span class="material-symbols-rounded text-sm">satellite_alt</span>
                                <span>Satelit</span>
                            </button>
                            <button type="button" @click="setDetailMapMode('roadmap')"
                                :class="(detailMapMode || 'satellite') === 'roadmap' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                                class="px-2.5 py-1 text-[11px] rounded-lg flex items-center space-x-1 transition-all">
                                <span class="material-symbols-rounded text-sm">map</span>
                                <span>Peta</span>
                            </button>
                        </div>

                        <!-- Leaflet Map Container -->
                        <div id="attendanceDetailMap" class="w-full h-[230px] bg-slate-100 relative"></div>

                        <!-- Map Action Bar -->
                        <div class="bg-[#FAF8FF] px-3 py-2 flex items-center justify-between border-t border-slate-100 text-xs">
                            <div class="flex items-center space-x-1.5 flex-1 min-w-0 pr-2">
                                <span class="material-symbols-rounded text-sm text-[#0051D5] shrink-0">location_on</span>
                                <span class="text-[11px] font-medium text-[#131B2E] truncate" x-text="detailData.address"></span>
                            </div>
                            <a :href="'https://www.google.com/maps/search/?api=1&query=' + detailData.latitude + ',' + detailData.longitude + '&t=k'"
                                target="_blank"
                                class="inline-flex items-center space-x-1 text-[11px] font-bold text-[#0051D5] hover:underline shrink-0">
                                <span class="material-symbols-rounded text-xs">open_in_new</span>
                                <span>Buka di Maps</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer Actions -->
            <div class="p-3 border-t border-slate-100 bg-[#FAF8FF]">
                <template x-if="detailData.isApproval">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                            @click="detailModal = false; reviewApprovalModal(detailData.id, 'rejected', detailData.userName, detailData.tipe_pengajuan || detailData.slotTitle)"
                            class="py-2.5 px-3 bg-[#EF4444] hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1.5 transition-colors">
                            <span class="material-symbols-rounded text-base">cancel</span>
                            <span>Tolak</span>
                        </button>
                        <button type="button"
                            @click="detailModal = false; reviewApprovalModal(detailData.id, 'approved', detailData.userName, detailData.tipe_pengajuan || detailData.slotTitle)"
                            class="py-2.5 px-3 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1.5 transition-colors">
                            <span class="material-symbols-rounded text-base">check_circle</span>
                            <span>Setujui</span>
                        </button>
                    </div>
                </template>

                <template x-if="!detailData.isApproval">
                    <button type="button" @click="detailModal = false"
                        class="w-full py-2.5 bg-[#00288E] hover:bg-[#001f70] text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center space-x-1">
                        <span class="material-symbols-rounded text-base">close</span>
                        <span>Tutup Rincian</span>
                    </button>
                </template>
            </div>

        </div>
    </div>
</div>

{{-- MODAL LIGHTBOX FOTO PREVIEW (Matching Flutter _showPhotoPreviewDialog) --}}
<div x-show="detailPhotoModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-preview-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div @click="detailPhotoModal = false" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-white rounded-2xl p-4 max-w-md w-full text-left shadow-2xl space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-rounded text-xl text-[#00288E]">photo_camera</span>
                    <h3 class="text-sm font-bold text-[#131B2E]">Swafoto Presensi Pegawai</h3>
                </div>
                <button type="button" @click="detailPhotoModal = false" class="w-7 h-7 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-rounded text-lg">close</span>
                </button>
            </div>

            <!-- Clean 4:5 Portrait Image Viewport without any overlay covering the photo -->
            <div class="relative bg-slate-950 rounded-xl overflow-hidden flex items-center justify-center w-full aspect-[4/5] shadow-inner">
                <img :src="detailData.fotoUrl" alt="Swafoto Pegawai" class="w-full h-full object-contain">
            </div>

            <!-- Footer Details -->
            <div class="p-3 bg-[#FAF8FF] rounded-xl border border-slate-100 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <div class="font-bold text-[#131B2E]" x-text="detailData.userName"></div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">Kamera Asli Terverifikasi</span>
                </div>
                <div class="text-[#64748B]">NIK: <span x-text="detailData.nip"></span> • <span x-text="detailData.department"></span></div>
                <div class="text-[#0051D5] font-semibold">Waktu: <span x-text="detailData.recordedTimeStr"></span></div>
            </div>

            <button type="button" @click="detailPhotoModal = false" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl">
                Tutup Pratinjau
            </button>
        </div>
    </div>
</div>
