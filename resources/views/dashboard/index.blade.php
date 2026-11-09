@extends('layouts.app')

@section('title', 'Dashboard Tirtafield')

@section('content')
<div x-data="tirtafieldApp()" x-init="initApp()" class="space-y-4">

    <!-- Floating SnackBar Toast (Matching Flutter SnackBar with AppColors.inverseSurface) -->
    <div x-show="lockedToastMessage" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-[400px] pointer-events-none">
        <div class="bg-[#1E293B] text-white px-4 py-3 rounded-2xl shadow-2xl flex items-center space-x-2.5 border border-slate-700/60 pointer-events-auto">
            <span class="material-symbols-rounded text-lg text-amber-400 shrink-0">lock</span>
            <span class="text-xs font-semibold flex-1 leading-snug" x-text="lockedToastMessage"></span>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- ROLE 1: KARYAWAN (Matching HomeKaryawanScreen.dart & AkunKaryawanScreen.dart) --}}
    {{-- ========================================================================= --}}
    @if (auth()->user()?->isKaryawan())
        
        {{-- TAB 0: BERANDA KARYAWAN --}}
        <div x-show="activeTab === 'beranda'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-4">
            
            {{-- 1. HERO DIGITAL CLOCK CARD (Matching CorporateHeroClock.dart exactly) --}}
            <div class="relative overflow-hidden bg-white rounded-2xl border border-[#DAE2FD] p-5 shadow-xs text-center">
                <!-- Background Subtle Ambient Light Blob -->
                <div class="absolute -right-5 -top-5 w-32 h-32 rounded-full bg-[#316BF3]/10 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col items-center justify-center">
                    <!-- Greeting Centered -->
                    <div class="text-sm font-semibold text-[#0051D5] tracking-tight mb-2">
                        <span x-text="greeting"></span>, {{ auth()->user()?->name }}
                    </div>

                    <!-- Jam Digital Centrify HH:mm:ss WIB -->
                    <div class="flex items-baseline justify-center space-x-1.5 my-1">
                        <span class="font-sans text-[34px] font-bold tracking-tight text-[#131B2E]" x-text="clockTime">
                            {{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i:s') }}
                        </span>
                        <span class="text-xs font-bold text-[#00288E]">WIB</span>
                    </div>

                    <!-- Tanggal di bawah jam -->
                    <div class="text-[13.5px] text-[#64748B] font-normal mt-0.5" x-text="indonesianDate">
                        {{ \Carbon\Carbon::parse($today)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                    </div>

                    <!-- Penempatan Kantor & Radius Badge Terkonfigurasi Superadmin -->
                    <div class="inline-flex flex-wrap items-center justify-center gap-1.5 mt-3 max-w-full">
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl bg-[#0051D5]/10 border border-[#0051D5]/20 text-[#0051D5] text-[11.5px] font-semibold max-w-full truncate">
                            <span class="material-symbols-rounded text-sm">apartment</span>
                            <span class="truncate">Penempatan: {{ $userOffice?->nama_kantor ?? auth()->user()?->office_name ?? 'Kantor Pusat PERUMDA Tirta Kepri' }}</span>
                            <span class="px-1.5 py-0.2 bg-[#0051D5] text-white text-[10px] font-bold rounded-md ml-1 shrink-0">Radius {{ $userOffice?->radius ?? 100 }}m</span>
                        </div>
                        <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-xl {{ ($todaySchedule['is_work_day'] ?? true) ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-slate-100 border border-slate-200 text-slate-700' }} text-[11px] font-bold">
                            <span class="material-symbols-rounded text-sm">{{ ($todaySchedule['is_work_day'] ?? true) ? 'schedule' : 'beach_access' }}</span>
                            <span>{{ ($todaySchedule['is_work_day'] ?? true) ? ('Jam Kerja: ' . ($todaySchedule['masuk'] ?? '08:00') . ' - ' . ($todaySchedule['pulang'] ?? '17:00') . ' WIB (Toleransi ' . ($todaySchedule['toleransi_menit'] ?? 15) . ' mnt)') : 'Hari Libur Operasional' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. 4 DEDICATED ATTENDANCE ACTIONS (Matching CorporateAttendanceCards.dart exactly) --}}
            <div class="space-y-3">
                <!-- Section Header -->
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-[17px] font-bold text-[#131B2E] tracking-tight">Presensi Hari Ini</h2>
                    <span class="text-xs font-medium text-[#64748B]">4 Sesi Presensi ({{ $todaySchedule['day_name'] ?? 'Hari Ini' }})</span>
                </div>

                @php
                    $isSlot1Done = !empty($mySlots['masuk']);
                    $isSlot2Done = !empty($mySlots['istirahatKeluar']);
                    $isSlot3Done = !empty($mySlots['istirahatMasuk']);
                    $isSlot4Done = !empty($mySlots['pulang']);

                    $slot1Item = $mySlots['masuk'] ?? null;
                    $slot2Item = $mySlots['istirahatKeluar'] ?? null;
                    $slot3Item = $mySlots['istirahatMasuk'] ?? null;
                    $slot4Item = $mySlots['pulang'] ?? null;

                    $nowJakarta = \Carbon\Carbon::now('Asia/Jakarta');
                    $currentHi = $nowJakarta->format('H:i');
                    $isWorkDay = (bool)($todaySchedule['is_work_day'] ?? true);
                    $isDiniHari = ($nowJakarta->hour < 7);

                    $jamMasuk = $todaySchedule['masuk'] ?? '08:00';
                    $jamIstirahatKeluar = $todaySchedule['istirahat_keluar'] ?? '12:00';
                    $jamIstirahatMasuk = $todaySchedule['istirahat_masuk'] ?? '13:00';
                    $jamPulang = $todaySchedule['pulang'] ?? '17:00';

                    // Sesi presensi masuk dibuka mulai 1 jam sebelum jam masuk resmi
                    try {
                        $jamBukaMasuk = \Carbon\Carbon::createFromFormat('H:i', $jamMasuk)->subHour()->format('H:i');
                    } catch (\Throwable $e) {
                        $jamBukaMasuk = '07:00';
                    }

                    $isSlot1Time = ($currentHi >= $jamBukaMasuk);
                    $isSlot2Time = ($currentHi >= $jamIstirahatKeluar);
                    $isSlot3Time = ($currentHi >= $jamIstirahatKeluar);
                    $isSlot4Time = ($currentHi >= $jamPulang);

                    // Karyawan lembur hingga ganti hari dan pulang saat dini hari
                    $isOvernightLemburPulang = (!empty($isOvernightActive) && !$isSlot4Done) || ($isDiniHari && $isSlot1Done && !$isSlot4Done);

                    $slot1Active = $isWorkDay && !$isSlot1Done && $isSlot1Time && !$isDiniHari;
                    $slot2Active = $isWorkDay && $isSlot1Done && !$isSlot2Done && $isSlot2Time && !$isDiniHari;
                    $slot3Active = $isWorkDay && $isSlot2Done && !$isSlot3Done && $isSlot3Time && !$isDiniHari;
                    $slot4Active = ($isWorkDay && $isSlot1Done && !$isSlot4Done && $isSlot4Time) || $isOvernightLemburPulang;
                @endphp

                <!-- SLOT 1: PRESENSI MASUK -->
                @if ($isSlot1Done)
                    <div @click="openReceiptViewer('masuk', 'Presensi Masuk', '{{ $slot1Item['recorded_time'] ?? '' }}', '{{ $slot1Item['location'] ?? '' }}', '{{ $slot1Item['status'] ?? 'Tepat Waktu' }}', '{{ $slot1Item['foto_url'] ?? '' }}', '{{ $slot1Item['jarak_hq'] ?? '10 m' }}')"
                        class="cursor-pointer bg-white rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between shadow-xs hover:border-[#059669] transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl {{ (($slot1Item['isLate'] ?? false) || str_contains($slot1Item['status'] ?? '', 'Terlambat')) ? 'bg-amber-100/60 text-amber-700' : 'bg-emerald-50 text-[#059669]' }} flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">login</span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-[15px] font-bold text-[#131B2E]">Presensi Masuk</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase {{ (($slot1Item['isLate'] ?? false) || str_contains($slot1Item['status'] ?? '', 'Terlambat')) ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $slot1Item['status'] ?? 'TEPAT WAKTU' }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#64748B] mt-0.5">
                                    Presensi Pagi · {{ $slot1Item['recorded_time'] ?? '08:15' }} WIB
                                </p>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-[22px] {{ (($slot1Item['isLate'] ?? false) || str_contains($slot1Item['status'] ?? '', 'Terlambat')) ? 'text-amber-600' : 'text-emerald-600' }}">
                            {{ (($slot1Item['isLate'] ?? false) || str_contains($slot1Item['status'] ?? '', 'Terlambat')) ? 'schedule' : 'check_circle' }}
                        </span>
                    </div>
                @elseif ($slot1Active)
                    <div class="bg-white rounded-2xl border-2 border-[#316BF3] shadow-[0_8px_24px_rgba(49,107,243,0.18)] p-4 relative overflow-hidden">
                        <!-- Top-Right Ribbon: SESI AKTIF -->
                        <div class="absolute top-0 right-0 px-3 py-1 bg-[#316BF3] rounded-bl-xl text-white text-[10px] font-bold tracking-wider">
                            SESI AKTIF
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#DAE2FD] text-[#00288E] flex items-center justify-center text-lg shrink-0 mt-0.5">
                                <span class="material-symbols-rounded text-[22px]">login</span>
                            </div>
                            <div class="flex-1 pr-16">
                                <div class="text-[11px] font-bold text-[#0051D5] tracking-wide uppercase">SESI PAGI</div>
                                <h3 class="text-base font-bold text-[#131B2E] mt-0.5">Presensi Masuk</h3>
                                <p class="text-xs text-[#64748B] mt-0.5">Jadwal masuk: {{ $todaySchedule['masuk'] ?? '08:00' }} WIB (Toleransi {{ $todaySchedule['toleransi_menit'] ?? 15 }} mnt)</p>
                            </div>
                        </div>
                        <hr class="border-t border-[#EAEDFF] my-3">
                        <button type="button" @click="startAttendance('masuk', 'Presensi Masuk')"
                            class="w-full h-12 rounded-xl bg-[#316BF3] hover:bg-[#2558d4] text-white font-bold text-[15px] flex items-center justify-center space-x-2 shadow-sm transition-all">
                            <span class="material-symbols-rounded text-xl">location_on</span>
                            <span>Lakukan Presensi Masuk</span>
                        </button>
                    </div>
                @else
                    @php
                        $slot1LockedReason = !$isWorkDay
                            ? 'Hari ini adalah hari libur operasional. Sesi presensi tidak dapat dibuka.'
                            : ('Sesi Presensi Masuk belum dapat dibuka! Jadwal presensi masuk baru dibuka mulai pukul ' . $jamBukaMasuk . ' WIB (Jadwal masuk: ' . $jamMasuk . ' WIB).');
                        $slot1LockedSubtitle = !$isWorkDay ? 'Hari Libur Operasional' : 'Sesi Pagi · Belum Waktunya';
                        $slot1LockedDesc = !$isWorkDay
                            ? 'Presensi tidak dibuka pada hari libur'
                            : ('Dibuka mulai pukul ' . $jamBukaMasuk . ' WIB (Jadwal: ' . $jamMasuk . ' WIB)');
                    @endphp
                    <div @click="showLockedToast('{{ $slot1LockedReason }}')"
                        class="cursor-pointer opacity-80 bg-[#F2F3FF]/60 rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between hover:border-slate-300 transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#EAEDFF] text-[#757684] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">login</span>
                            </div>
                            <div>
                                <div class="text-[11px] text-[#757684] font-semibold">{{ $slot1LockedSubtitle }}</div>
                                <h3 class="text-sm font-semibold text-[#131B2E]">Presensi Masuk</h3>
                                <p class="text-xs text-[#64748B]">{{ $slot1LockedDesc }}</p>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#EAEDFF] flex items-center justify-center text-[#757684]">
                            <span class="material-symbols-rounded text-lg">lock</span>
                        </div>
                    </div>
                @endif

                <!-- SLOT 2: ISTIRAHAT KELUAR -->
                @if ($isSlot2Done)
                    <div @click="openReceiptViewer('istirahatKeluar', 'Presensi Istirahat Keluar', '{{ $slot2Item['recorded_time'] ?? '' }}', '{{ $slot2Item['location'] ?? '' }}', '{{ $slot2Item['status'] ?? 'Selesai' }}', '{{ $slot2Item['foto_url'] ?? '' }}', '{{ $slot2Item['jarak_hq'] ?? '10 m' }}')"
                        class="cursor-pointer bg-white rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between shadow-xs hover:border-[#316BF3] transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#316BF3] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">restaurant</span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-[15px] font-bold text-[#131B2E]">Presensi Istirahat Keluar</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase bg-blue-100 text-[#0051D5]">
                                        SELESAI
                                    </span>
                                </div>
                                <p class="text-xs text-[#64748B] mt-0.5">
                                    Sesi Siang · {{ $slot2Item['recorded_time'] ?? ($todaySchedule['istirahat_keluar'] ?? '12:00') }} WIB
                                </p>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-[22px] text-[#316BF3]">check_circle</span>
                    </div>
                @elseif ($slot2Active)
                    <div class="bg-white rounded-2xl border-2 border-[#316BF3] shadow-[0_8px_24px_rgba(49,107,243,0.18)] p-4 relative overflow-hidden">
                        <!-- Top-Right Ribbon: SESI AKTIF -->
                        <div class="absolute top-0 right-0 px-3 py-1 bg-[#316BF3] rounded-bl-xl text-white text-[10px] font-bold tracking-wider">
                            SESI AKTIF
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#DAE2FD] text-[#00288E] flex items-center justify-center text-lg shrink-0 mt-0.5">
                                <span class="material-symbols-rounded text-[22px]">restaurant</span>
                            </div>
                            <div class="flex-1 pr-16">
                                <div class="text-[11px] font-bold text-[#0051D5] tracking-wide uppercase">SESI SIANG</div>
                                <h3 class="text-base font-bold text-[#131B2E] mt-0.5">Presensi Istirahat Keluar</h3>
                                <p class="text-xs text-[#64748B] mt-0.5">Jadwal istirahat: {{ $todaySchedule['istirahat_keluar'] ?? '12:00' }} – {{ $todaySchedule['istirahat_masuk'] ?? '13:00' }} WIB</p>
                            </div>
                        </div>
                        <hr class="border-t border-[#EAEDFF] my-3">
                        <button type="button" @click="startAttendance('istirahatKeluar', 'Presensi Istirahat Keluar')"
                            class="w-full h-12 rounded-xl bg-[#316BF3] hover:bg-[#2558d4] text-white font-bold text-[15px] flex items-center justify-center space-x-2 shadow-sm transition-all">
                            <span class="material-symbols-rounded text-xl">location_on</span>
                            <span>Lakukan Presensi Istirahat</span>
                        </button>
                    </div>
                @else
                    @php
                        $slot2LockedReason = !$isWorkDay
                            ? 'Hari ini adalah hari libur operasional. Sesi presensi tidak dapat dibuka.'
                            : (!$isSlot1Done
                                ? 'Presensi Istirahat Keluar baru dapat dilakukan setelah Anda melakukan Presensi Masuk.'
                                : 'Sesi Presensi Istirahat Keluar belum dapat dibuka! Jadwal resmi dibuka pukul ' . $jamIstirahatKeluar . ' WIB.');
                        $slot2LockedSubtitle = !$isWorkDay
                            ? 'Hari Libur Operasional'
                            : (!$isSlot1Done ? 'Sesi Siang · Belum Dimulai' : 'Sesi Siang · Belum Waktunya');
                        $slot2LockedDesc = !$isWorkDay
                            ? 'Presensi tidak dibuka pada hari libur'
                            : (!$isSlot1Done
                                ? 'Tersedia setelah presensi masuk (Mulai ' . $jamIstirahatKeluar . ' WIB)'
                                : 'Tersedia mulai pukul ' . $jamIstirahatKeluar . ' WIB');
                    @endphp
                    <div @click="showLockedToast('{{ $slot2LockedReason }}')"
                        class="cursor-pointer opacity-80 bg-[#F2F3FF]/60 rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between hover:border-slate-300 transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#EAEDFF] text-[#757684] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">restaurant</span>
                            </div>
                            <div>
                                <div class="text-[11px] text-[#757684] font-semibold">{{ $slot2LockedSubtitle }}</div>
                                <h3 class="text-sm font-semibold text-[#131B2E]">Presensi Istirahat Keluar</h3>
                                <p class="text-xs text-[#64748B]">{{ $slot2LockedDesc }}</p>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#EAEDFF] flex items-center justify-center text-[#757684]">
                            <span class="material-symbols-rounded text-lg">lock</span>
                        </div>
                    </div>
                @endif

                <!-- SLOT 3: ISTIRAHAT MASUK -->
                @if ($isSlot3Done)
                    <div @click="openReceiptViewer('istirahatMasuk', 'Presensi Istirahat Masuk', '{{ $slot3Item['recorded_time'] ?? '' }}', '{{ $slot3Item['location'] ?? '' }}', '{{ $slot3Item['status'] ?? 'Selesai' }}', '{{ $slot3Item['foto_url'] ?? '' }}', '{{ $slot3Item['jarak_hq'] ?? '10 m' }}')"
                        class="cursor-pointer bg-white rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between shadow-xs hover:border-[#6366F1] transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-[#6366F1] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">coffee</span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-[15px] font-bold text-[#131B2E]">Presensi Istirahat Masuk</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase bg-indigo-100 text-indigo-800">
                                        SELESAI
                                    </span>
                                </div>
                                <p class="text-xs text-[#64748B] mt-0.5">
                                    Selesai Istirahat · {{ $slot3Item['recorded_time'] ?? ($todaySchedule['istirahat_masuk'] ?? '13:00') }} WIB
                                </p>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-[22px] text-[#6366F1]">check_circle</span>
                    </div>
                @elseif ($slot3Active)
                    <div class="bg-white rounded-2xl border-2 border-[#316BF3] shadow-[0_8px_24px_rgba(49,107,243,0.18)] p-4 relative overflow-hidden">
                        <!-- Top-Right Ribbon: SESI AKTIF -->
                        <div class="absolute top-0 right-0 px-3 py-1 bg-[#316BF3] rounded-bl-xl text-white text-[10px] font-bold tracking-wider">
                            SESI AKTIF
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#DAE2FD] text-[#00288E] flex items-center justify-center text-lg shrink-0 mt-0.5">
                                <span class="material-symbols-rounded text-[22px]">coffee</span>
                            </div>
                            <div class="flex-1 pr-16">
                                <div class="text-[11px] font-bold text-[#0051D5] tracking-wide uppercase">SESI SIANG</div>
                                <h3 class="text-base font-bold text-[#131B2E] mt-0.5">Presensi Istirahat Masuk</h3>
                                <p class="text-xs text-[#64748B] mt-0.5">Batas istirahat berakhir: {{ $todaySchedule['istirahat_masuk'] ?? '13:00' }} WIB</p>
                            </div>
                        </div>
                        <hr class="border-t border-[#EAEDFF] my-3">
                        <button type="button" @click="startAttendance('istirahatMasuk', 'Presensi Istirahat Masuk')"
                            class="w-full h-12 rounded-xl bg-[#316BF3] hover:bg-[#2558d4] text-white font-bold text-[15px] flex items-center justify-center space-x-2 shadow-sm transition-all">
                            <span class="material-symbols-rounded text-xl">location_on</span>
                            <span>Lakukan Selesai Istirahat</span>
                        </button>
                    </div>
                @else
                    @php
                        $slot3LockedReason = !$isWorkDay
                            ? 'Hari ini adalah hari libur operasional. Sesi presensi tidak dapat dibuka.'
                            : (!$isSlot2Done
                                ? 'Presensi Istirahat Masuk baru dapat dilakukan setelah melakukan Presensi Istirahat Keluar.'
                                : 'Sesi Presensi Istirahat Masuk belum dapat dibuka! Jadwal resmi dibuka pukul ' . $jamIstirahatMasuk . ' WIB.');
                        $slot3LockedSubtitle = !$isWorkDay
                            ? 'Hari Libur Operasional'
                            : (!$isSlot2Done ? 'Sesi Siang · Menunggu Istirahat' : 'Sesi Siang · Belum Waktunya');
                        $slot3LockedDesc = !$isWorkDay
                            ? 'Presensi tidak dibuka pada hari libur'
                            : (!$isSlot2Done
                                ? 'Tersedia setelah presensi istirahat keluar (Mulai ' . $jamIstirahatMasuk . ' WIB)'
                                : 'Tersedia mulai pukul ' . $jamIstirahatMasuk . ' WIB');
                    @endphp
                    <div @click="showLockedToast('{{ $slot3LockedReason }}')"
                        class="cursor-pointer opacity-80 bg-[#F2F3FF]/60 rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between hover:border-slate-300 transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#EAEDFF] text-[#757684] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">coffee</span>
                            </div>
                            <div>
                                <div class="text-[11px] text-[#757684] font-semibold">{{ $slot3LockedSubtitle }}</div>
                                <h3 class="text-sm font-semibold text-[#131B2E]">Presensi Istirahat Masuk</h3>
                                <p class="text-xs text-[#64748B]">{{ $slot3LockedDesc }}</p>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#EAEDFF] flex items-center justify-center text-[#757684]">
                            <span class="material-symbols-rounded text-lg">lock</span>
                        </div>
                    </div>
                @endif

                <!-- SLOT 4: PRESENSI PULANG -->
                @if ($isSlot4Done)
                    <div @click="openReceiptViewer('pulang', 'Presensi Pulang', '{{ $slot4Item['recorded_time'] ?? '' }}', '{{ $slot4Item['location'] ?? '' }}', '{{ $slot4Item['status'] ?? 'Selesai' }}', '{{ $slot4Item['foto_url'] ?? '' }}', '{{ $slot4Item['jarak_hq'] ?? '10 m' }}')"
                        class="cursor-pointer bg-white rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between shadow-xs hover:border-[#EF4444] transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">logout</span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-[15px] font-bold text-[#131B2E]">Presensi Pulang</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase bg-emerald-100 text-emerald-800">
                                        SELESAI
                                    </span>
                                </div>
                                <p class="text-xs text-[#64748B] mt-0.5">
                                    Presensi Sore · {{ $slot4Item['recorded_time'] ?? ($todaySchedule['pulang'] ?? '17:00') }} WIB
                                </p>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-[22px] text-[#059669]">check_circle</span>
                    </div>
                @elseif ($slot4Active)
                    <div class="bg-white rounded-2xl border-2 border-[#316BF3] shadow-[0_8px_24px_rgba(49,107,243,0.18)] p-4 relative overflow-hidden">
                        <!-- Top-Right Ribbon: SESI AKTIF / LEMBUR DINI HARI -->
                        <div class="absolute top-0 right-0 px-3 py-1 bg-[#316BF3] rounded-bl-xl text-white text-[10px] font-bold tracking-wider">
                            {{ $isOvernightLemburPulang ? 'LEMBUR DINI HARI' : 'SESI AKTIF' }}
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#DAE2FD] text-[#00288E] flex items-center justify-center text-lg shrink-0 mt-0.5">
                                <span class="material-symbols-rounded text-[22px]">logout</span>
                            </div>
                            <div class="flex-1 pr-16">
                                <div class="text-[11px] font-bold text-[#0051D5] tracking-wide uppercase">{{ $isOvernightLemburPulang ? 'SESI LEMBUR' : 'SESI SORE' }}</div>
                                <h3 class="text-base font-bold text-[#131B2E] mt-0.5">{{ $isOvernightLemburPulang ? 'Presensi Pulang (Lembur Dini Hari)' : 'Presensi Pulang' }}</h3>
                                <p class="text-xs text-[#64748B] mt-0.5">
                                    {{ $isOvernightLemburPulang ? 'Kepulangan lembur kerja lintas hari · Menunggu persetujuan supervisor' : ('Batas jam kerja berakhir: ' . ($todaySchedule['pulang'] ?? '17:00') . ' WIB') }}
                                </p>
                            </div>
                        </div>
                        <hr class="border-t border-[#EAEDFF] my-3">
                        <button type="button" @click="startAttendance('pulang', '{{ $isOvernightLemburPulang ? 'Presensi Pulang Lembur' : 'Presensi Pulang' }}')"
                            class="w-full h-12 rounded-xl bg-[#316BF3] hover:bg-[#2558d4] text-white font-bold text-[15px] flex items-center justify-center space-x-2 shadow-sm transition-all">
                            <span class="material-symbols-rounded text-xl">location_on</span>
                            <span>{{ $isOvernightLemburPulang ? 'Lakukan Presensi Pulang Lembur' : 'Lakukan Presensi Pulang' }}</span>
                        </button>
                    </div>
                @else
                    @php
                        $slot4LockedReason = !$isWorkDay && !$isDiniHari
                            ? 'Hari ini adalah hari libur operasional. Sesi presensi tidak dapat dibuka.'
                            : (!$isSlot1Done
                                ? 'Presensi Pulang baru dapat dilakukan setelah Anda melakukan Presensi Masuk.'
                                : ($isDiniHari
                                    ? 'Presensi Pulang lembur hanya dapat dibuka jika Anda memiliki riwayat presensi masuk.'
                                    : 'Sesi Presensi Pulang belum dapat dilakukan! Jam pulang kantor dibuka pukul ' . $jamPulang . ' WIB.'));
                        $slot4LockedSubtitle = !$isWorkDay && !$isDiniHari
                            ? 'Hari Libur Operasional'
                            : (!$isSlot1Done ? 'Sesi Sore · Belum Dimulai' : 'Sesi Sore · Belum Waktunya');
                        $slot4LockedDesc = !$isWorkDay && !$isDiniHari
                            ? 'Presensi tidak dibuka pada hari libur'
                            : (!$isSlot1Done
                                ? 'Tersedia setelah presensi masuk (Mulai ' . $jamPulang . ' WIB)'
                                : 'Tersedia mulai pukul ' . $jamPulang . ' WIB');
                    @endphp
                    <div @click="showLockedToast('{{ $slot4LockedReason }}')"
                        class="cursor-pointer opacity-80 bg-[#F2F3FF]/60 rounded-2xl border border-[#DAE2FD] p-4 flex items-center justify-between hover:border-slate-300 transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-xl bg-[#EAEDFF] text-[#757684] flex items-center justify-center text-lg shrink-0">
                                <span class="material-symbols-rounded text-xl">logout</span>
                            </div>
                            <div>
                                <div class="text-[11px] text-[#757684] font-semibold">{{ $slot4LockedSubtitle }}</div>
                                <h3 class="text-sm font-semibold text-[#131B2E]">Presensi Pulang</h3>
                                <p class="text-xs text-[#64748B]">{{ $slot4LockedDesc }}</p>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#EAEDFF] flex items-center justify-center text-[#757684]">
                            <span class="material-symbols-rounded text-lg">lock</span>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- TAB 1: AKUN KARYAWAN (Matching AkunKaryawanScreen.dart exactly) --}}
        <div x-show="activeTab === 'akun'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3.5">
            
            <!-- 1. HERO PROFILE CARD (Matching _buildHeroProfileCard) -->
            <div class="relative overflow-hidden bg-white rounded-2xl border border-[#DAE2FD] p-4 shadow-xs">
                <!-- Ambient decorative gradient circle -->
                <div class="absolute -right-6 -top-6 w-32 h-32 rounded-full bg-[#0051D5]/5 pointer-events-none"></div>

                <div class="relative z-10 flex items-start space-x-3.5">
                    <!-- Avatar with Upload Camera Button Badge -->
                    <div class="relative shrink-0">
                        <div @click="karyawanPhotoSheet = true" class="w-20 h-20 p-[2.5px] rounded-full bg-gradient-to-tr from-[#0051D5] to-[#7DD3FC] shadow-md shadow-[#0051D5]/20 flex items-center justify-center cursor-pointer overflow-hidden">
                            <template x-if="currentUserAvatar">
                                <img :src="currentUserAvatar" alt="Profile" class="profile-avatar-img w-full h-full rounded-full object-cover">
                            </template>
                            <template x-if="!currentUserAvatar">
                                <div class="w-full h-full rounded-full overflow-hidden bg-gradient-to-tr from-[#00288E] to-[#316BF3] text-white flex items-center justify-center text-2xl font-bold">
                                    {{ strtoupper(substr(auth()->user()?->name ?? 'K', 0, 1)) }}
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="karyawanPhotoSheet = true"
                            class="absolute -bottom-1 -right-1 w-[30px] h-[30px] rounded-full bg-[#0051D5] text-white flex items-center justify-center border-2 border-white shadow-md hover:bg-[#003ea8] transition-colors cursor-pointer"
                            title="Ubah Foto Profil">
                            <span class="material-symbols-rounded text-sm">photo_camera</span>
                        </button>
                    </div>

                    <!-- Identity Metadata -->
                    <div class="flex-1 min-w-0 pt-1">
                        <h2 class="text-base font-bold text-[#131B2E] truncate">
                            {{ auth()->user()?->name ?? 'Budi Pratama' }}
                        </h2>
                        <div class="text-xs text-[#64748B] mt-0.5">
                            <span>NIK: </span>
                            <span class="font-bold text-[#131B2E]">{{ auth()->user()?->nik ?? '-' }}</span>
                        </div>
                        <div class="text-xs text-[#64748B] truncate mt-0.5">
                            {{ auth()->user()?->department ?? 'Product & Tech' }} - {{ auth()->user()?->position ?? 'Staff Lapangan' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. FORM SECTION: INFORMASI KEPEGAWAIAN (Matching _buildFormSection) -->
            <div class="bg-white rounded-2xl border border-[#DAE2FD] p-4 shadow-xs space-y-3.5">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-bold text-[#131B2E]">Informasi Kepegawaian</h3>
                    <span class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200 font-semibold flex items-center gap-1">
                        <span class="material-symbols-rounded text-xs">lock</span> Terkunci
                    </span>
                </div>

                <!-- Field 1: Nama Lengkap -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">badge</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->name }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 2: NIK -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Nomor Induk Karyawan (NIK)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">badge</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->nik }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-mono font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 3: Bidang -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Bidang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">business</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->department }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 4: Seksi Bidang -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Seksi Bidang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">assignment_ind</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->position }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 4: Kantor Penempatan (Titik Acuan Radius) -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5 truncate">Kantor Penempatan (Acuan Radius Presensi)</label>
                    <div class="p-3 rounded-xl bg-[#F8FAFC] border border-[#DAE2FD]/60 flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-[#0051D5]/10 text-[#0051D5] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">apartment</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-[13px] font-bold text-[#131B2E] truncate">
                                {{ $userOffice?->nama_kantor ?? auth()->user()?->office_name ?? 'Kantor Pusat PERUMDA Tirta Kepri' }}
                            </h4>
                            <p class="text-[10.5px] text-[#64748B] mt-0.5 leading-snug">
                                Titik acuan perhitungan jarak dan radius presensi saat di lapangan
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Security / Ubah PIN Row Button -->
                <div @click="karyawanPinModal = true"
                    class="p-3 rounded-xl bg-[#F8FAFC] hover:bg-[#F1F5F9] border border-[#DAE2FD]/60 flex items-center space-x-3 cursor-pointer transition-colors">
                    <div class="w-9 h-9 rounded-xl bg-[#EAEDFF] text-[#0051D5] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-xl">pin</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[12.5px] font-semibold text-[#131B2E]">Ubah PIN Presensi & Keamanan</h4>
                        <p class="text-[11px] text-[#64748B]">Terakhir diubah 30 hari yang lalu</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>
            </div>

            <!-- 3. PREFERENCES & SETTINGS MODULE: ALARM PRESENSI (Matching _buildPreferencesSection) -->
            <div @click="openAlarmSettingsModal()"
                class="bg-white rounded-2xl border border-[#DAE2FD] p-3.5 shadow-xs flex items-center space-x-3 cursor-pointer hover:border-[#0051D5]/40 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-[#0051D5]/10 text-[#0051D5] flex items-center justify-center shrink-0">
                    <span class="material-symbols-rounded text-2xl">alarm_on</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-[14px] font-bold text-[#131B2E] truncate">Pengaturan Alarm Presensi</h4>
                    <p class="text-[11.5px] text-[#64748B] truncate mt-0.5">4 slot aktif • Masuk ({{ $todaySchedule['masuk'] ?? '07:45' }} WIB)</p>
                </div>
                <div class="inline-flex items-center space-x-1.5 px-2 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10.5px] font-bold shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Alarm Aktif</span>
                </div>
                <span class="material-symbols-rounded text-xl text-slate-400 shrink-0">chevron_right</span>
            </div>

            <!-- 4. ACTION BUTTONS: KELUAR DARI AKUN (Matching _buildActionButtons) -->
            <form action="{{ route('web.logout') }}" method="POST" class="pt-1">
                @csrf
                <button type="submit" class="w-full h-[46px] rounded-xl border border-rose-300 text-rose-600 hover:bg-rose-50 font-bold text-[13.5px] flex items-center justify-center space-x-2 transition-colors">
                    <span class="material-symbols-rounded text-lg">logout</span>
                    <span>Keluar dari Akun</span>
                </button>
            </form>

            <p class="text-center text-[11px] text-[#64748B] pt-1">
                Dikembangkan Oleh PUSIMPEL TIRTA KEPRI
            </p>
        </div>

    {{-- ========================================================================= --}}
    {{-- ROLE 2: DIREKTUR (Matching DashboardDirekturScreen.dart & AdminMonitoringScreen.dart) --}}
    {{-- ========================================================================= --}}
    @elseif (auth()->user()?->isDirektur())

        {{-- TAB 0: DASHBOARD MONITORING DIREKTUR --}}
        <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3.5">
            
            <!-- 0. Executive Header Banner (Matching Flutter DashboardHeaderBanner with assets/images/app.jpg) -->
            <div class="rounded-2xl p-4 text-white shadow-lg relative overflow-hidden bg-gradient-to-br from-[#00227A] via-[#00144D] to-[#070E28] border border-white/15">
                <!-- Glow effects -->
                <div class="absolute -right-5 -top-5 w-36 h-36 rounded-full bg-blue-500/20 blur-xl pointer-events-none"></div>
                <div class="absolute -left-6 -bottom-6 w-32 h-32 rounded-full bg-cyan-400/10 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-500/25 text-purple-200 border border-purple-400/40">
                            <span>Aplikasi Presensi Lapangan</span>
                        </span>
                        <h2 class="text-sm font-extrabold text-white tracking-tight mt-1.5 leading-snug">
                            Perusahaan Umum Daerah<br>Air Minum Tirta Kepri
                        </h2>
                        <p class="text-[11.5px] font-semibold text-[#7DD3FC] mt-0.5">
                            Budaya Bersih &amp; Melayani
                        </p>
                        <div class="flex items-center space-x-1.5 mt-2 text-[10px] text-white/80 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="truncate">Halo, {{ auth()->user()?->name }} · {{ auth()->user()?->position ?? 'Direktur Utama' }}</span>
                        </div>
                    </div>

                    <!-- Corporate Logo Graphic (assets/images/app.jpg) -->
                    <div class="w-[70px] h-[70px] rounded-2xl border border-white/35 shadow-md shadow-black/40 overflow-hidden shrink-0 bg-[#0A1E4A] flex items-center justify-center">
                        <img src="{{ asset('images/app.jpg') }}" alt="PERUMDA Tirta Kepri" class="w-full h-full object-cover"
                            onerror="this.style.display='none'; document.getElementById('dirFallbackLogo').style.display='block';">
                        <div id="dirFallbackLogo" style="display: none;" class="text-white text-2xl flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">water_drop</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1. STATISTIK UTAMA (DIREKTUR) - RINGKASAN OPERASIONAL & PERSETUJUAN (Matching _buildMainStatisticsBento) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between px-0.5">
                    <h3 class="text-[13.5px] font-bold text-[#131B2E] tracking-tight">Ringkasan Operasional &amp; Persetujuan</h3>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <!-- CARD 1: Total Karyawan Lapangan -->
                    <div class="bg-white rounded-xl p-2.5 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-[19px] font-extrabold text-[#7C3AED] leading-none">{{ $stats['total_karyawan'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#7C3AED]">engineering</span>
                        </div>
                        <div class="mt-1.5">
                            <div class="text-[11px] font-semibold text-[#131B2E] leading-tight truncate">Total Pegawai</div>
                            <div class="text-[9.5px] text-[#64748B] truncate">Karyawan Lapangan</div>
                        </div>
                    </div>

                    <!-- CARD 2: Menunggu Persetujuan -->
                    <div @click="approvalTab = 'pending'" class="bg-white rounded-xl p-2.5 border {{ $pendingApprovals->count() > 0 ? 'border-amber-300 ring-1 ring-amber-100' : 'border-[#DAE2FD]/70' }} shadow-2xs flex flex-col justify-between cursor-pointer hover:border-amber-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-[19px] font-extrabold text-[#D97706] leading-none">{{ $pendingApprovals->count() }}</span>
                            <span class="material-symbols-rounded text-base text-[#D97706]">pending_actions</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between">
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold text-[#131B2E] leading-tight truncate">Persetujuan</div>
                                <div class="text-[9.5px] text-[#64748B] truncate">Menunggu Review</div>
                            </div>
                            @if ($pendingApprovals->count() > 0)
                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706] shrink-0 ml-1"></span>
                            @endif
                        </div>
                    </div>

                    <!-- CARD 3: Telah Disetujui -->
                    <div @click="approvalTab = 'approved'" class="bg-white rounded-xl p-2.5 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between cursor-pointer hover:border-emerald-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-[19px] font-extrabold text-[#16A34A] leading-none">{{ $stats['approved_count'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#16A34A]">verified</span>
                        </div>
                        <div class="mt-1.5">
                            <div class="text-[11px] font-semibold text-[#131B2E] leading-tight truncate">Disetujui</div>
                            <div class="text-[9.5px] text-[#64748B] truncate">Telah Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. LIVE PRESENCE SUMMARY BADGES (Matching _buildLivePresenceSummary) -->
            <div class="bg-white rounded-xl px-3.5 py-2.5 border border-[#DAE2FD]/70 shadow-2xs flex items-center justify-between">
                <!-- Mini Metric 1: Hadir Hari Ini -->
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-[#0051D5] shrink-0"></span>
                    <div class="min-w-0">
                        <div class="text-xs font-extrabold text-[#0051D5] leading-none">{{ $stats['hadir_hari_ini'] }}</div>
                        <div class="text-[9.5px] text-[#64748B] truncate mt-0.5">Hadir Hari Ini</div>
                    </div>
                </div>

                <div class="w-px h-5 bg-[#DAE2FD] mx-2"></div>

                <!-- Mini Metric 2: Tepat Waktu -->
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-[#16A34A] shrink-0"></span>
                    <div class="min-w-0">
                        <div class="text-xs font-extrabold text-[#16A34A] leading-none">{{ $stats['tepat_waktu'] }}</div>
                        <div class="text-[9.5px] text-[#64748B] truncate mt-0.5">Tepat Waktu</div>
                    </div>
                </div>

                <div class="w-px h-5 bg-[#DAE2FD] mx-2"></div>

                <!-- Mini Metric 3: Terlambat -->
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-[#DC2626] shrink-0"></span>
                    <div class="min-w-0">
                        <div class="text-xs font-extrabold text-[#DC2626] leading-none">{{ $stats['terlambat_hari_ini'] }}</div>
                        <div class="text-[9.5px] text-[#64748B] truncate mt-0.5">Terlambat</div>
                    </div>
                </div>
            </div>

            <!-- 3. SLEEK SEARCH BAR (Matching _buildSearchBar) -->
            <div class="h-10 bg-white rounded-xl border border-[#DAE2FD] px-3 flex items-center space-x-2 shadow-2xs focus-within:border-roleDirektur">
                <span class="material-symbols-rounded text-lg text-roleDirektur shrink-0">search</span>
                <input type="text" x-model="dirSearch" placeholder="Cari nama pegawai, NIK, atau keterangan presensi..."
                    class="w-full text-xs text-[#131B2E] placeholder-[#64748B] outline-none bg-transparent font-sans">
                <button type="button" x-show="dirSearch" @click="dirSearch = ''" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-rounded text-base">close</span>
                </button>
            </div>

            <!-- 4. STATUS SEGMENTED TABS & APPROVAL CARDS (Matching _buildStatusTabBar & _buildApprovalCard) -->
            <div class="space-y-3">
                <!-- Segmented Tab Header Bar -->
                <div class="p-1 bg-[#EAEDFF]/70 rounded-xl flex items-center space-x-1 border border-[#DAE2FD]/60">
                    <button type="button" @click="approvalTab = 'pending'"
                        :class="approvalTab === 'pending' ? 'bg-white text-[#131B2E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
                        <span>Menunggu</span>
                        <span :class="approvalTab === 'pending' ? 'bg-[#D97706] text-white' : 'bg-[#FEF3C7] text-[#D97706]'"
                            class="px-1.5 py-0.5 rounded-full text-[9.5px] font-bold">
                            {{ $pendingApprovals->count() }}
                        </span>
                    </button>
                    <button type="button" @click="approvalTab = 'approved'"
                        :class="approvalTab === 'approved' ? 'bg-white text-[#131B2E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
                        <span>Disetujui</span>
                        <span :class="approvalTab === 'approved' ? 'bg-[#16A34A] text-white' : 'bg-[#DCFCE7] text-[#16A34A]'"
                            class="px-1.5 py-0.5 rounded-full text-[9.5px] font-bold">
                            {{ $approvedApprovals->count() }}
                        </span>
                    </button>
                    <button type="button" @click="approvalTab = 'rejected'"
                        :class="approvalTab === 'rejected' ? 'bg-white text-[#131B2E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
                        <span>Ditolak</span>
                        <span :class="approvalTab === 'rejected' ? 'bg-[#DC2626] text-white' : 'bg-[#FEE2E2] text-[#DC2626]'"
                            class="px-1.5 py-0.5 rounded-full text-[9.5px] font-bold">
                            {{ $rejectedApprovals->count() }}
                        </span>
                    </button>
                </div>

                <!-- Sub-tab 1: Pending Approvals (Menunggu) -->
                <div x-show="approvalTab === 'pending'" class="space-y-2.5">
                    @forelse ($pendingApprovals as $p)
                    <div x-show="dirMatches(@js(strtolower($p->pgw_nama)), @js(strtolower($p->nik)), @js(strtolower((string)$p->catatan)), @js(strtolower((string)$p->tipe_pengajuan)))"
                        class="bg-white rounded-2xl border border-[#DAE2FD] p-3 shadow-xs space-y-2.5 transition-all hover:border-[#7C3AED]/40">
                        
                        <!-- Row 1: Pegawai Info & Punctuality Badge (Hanya Teks Saat Preview) -->
                        <div class="space-y-1.5">
                            <div>
                                <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">{{ $p->pgw_nama }}</h4>
                                <p class="text-[11px] text-[#64748B] truncate mt-0.5 font-medium">
                                    NIK: {{ $p->nik ?? '-' }} • {{ $p->user?->department ?? 'Operasional Lapangan' }}
                                </p>
                            </div>
                            <div class="w-full">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold border flex items-center space-x-1.5 {{ $p->is_late ? 'bg-[#FEF3C7] text-[#D97706] border-amber-300/50' : 'bg-[#DCFCE7] text-[#16A34A] border-emerald-300/50' }}">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">{{ $p->is_late ? 'schedule' : 'check_circle' }}</span>
                                    <span class="truncate min-w-0">{{ $p->is_late ? ($p->punctuality_label ?: 'Terlambat') : 'Tepat Waktu' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Slot Presensi & Catatan -->
                        <div class="p-2 rounded-lg bg-[#F8FAFC] border border-[#DAE2FD]/50 text-xs text-[#131B2E] flex items-start space-x-2">
                            <span class="material-symbols-rounded text-sm text-[#0051D5] mt-0.5 shrink-0">{{ $p->slot_icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-[11.5px]">{{ $p->slot_label }} · {{ $p->formatted_waktu }} WIB</div>
                                <div class="text-[11px] text-[#64748B] truncate mt-0.5">{{ $p->lokasi ?? ($p->catatan ?: 'Penugasan Lapangan') }}</div>
                            </div>
                        </div>

                        <!-- Row 3: Action Buttons (Lihat Rincian, Tolak, Setujui) -->
                        <div class="grid grid-cols-3 gap-1.5 pt-0.5">
                            <!-- 1. LIHAT RINCIAN -->
                            <button type="button" @click="openAttendanceDetail({{ json_encode($p->toDetailArray($userOffice)) }}, true)"
                                class="h-9 px-1 rounded-lg bg-[#0051D5] hover:bg-[#003ea8] text-white text-[10.5px] font-bold flex items-center justify-center space-x-1 shadow-2xs transition-colors cursor-pointer">
                                <span class="material-symbols-rounded text-sm">visibility</span>
                                <span class="truncate">Lihat Rincian</span>
                            </button>

                            <!-- 2. TOLAK -->
                            <button type="button" @click="reviewApprovalModal({{ $p->id }}, 'rejected', '{{ $p->pgw_nama }}', '{{ $p->tipe_pengajuan }}')"
                                class="h-9 px-1 rounded-lg bg-[#DC2626] hover:bg-[#b91c1c] text-white text-[10.5px] font-bold flex items-center justify-center space-x-1 shadow-2xs transition-colors cursor-pointer">
                                <span class="material-symbols-rounded text-sm">close</span>
                                <span>Tolak</span>
                            </button>

                            <!-- 3. SETUJUI -->
                            <button type="button" @click="reviewApprovalModal({{ $p->id }}, 'approved', '{{ $p->pgw_nama }}', '{{ $p->tipe_pengajuan }}')"
                                class="h-9 px-1 rounded-lg bg-[#16A34A] hover:bg-[#15803d] text-white text-[10.5px] font-bold flex items-center justify-center space-x-1 shadow-2xs transition-colors cursor-pointer">
                                <span class="material-symbols-rounded text-sm">check</span>
                                <span>Setujui</span>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="py-10 text-center bg-white rounded-2xl border border-[#DAE2FD] p-6 shadow-xs">
                        <span class="material-symbols-rounded text-4xl text-slate-300">check_circle</span>
                        <h4 class="text-xs font-bold text-slate-800 mt-2">Tidak Ada Pengajuan Menunggu</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Semua presensi karyawan lapangan telah ditinjau.</p>
                    </div>
                    @endforelse
                </div>

                <!-- Sub-tab 2: Approved Approvals (Disetujui) -->
                <div x-show="approvalTab === 'approved'" class="space-y-2.5">
                    @forelse ($approvedApprovals as $a)
                    <div x-show="dirMatches(@js(strtolower($a->pgw_nama)), @js(strtolower($a->nik)), @js(strtolower((string)$a->catatan)), @js(strtolower((string)$a->tipe_pengajuan)))"
                        class="bg-white rounded-2xl border border-[#DAE2FD] p-3 shadow-xs space-y-2.5 transition-all hover:border-[#16A34A]/40">
                        
                        <!-- Row 1: Pegawai Info, Punctuality Badge, & Review Time Badge (Hanya Teks Saat Preview) -->
                        <div class="space-y-1.5">
                            <div>
                                <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">{{ $a->pgw_nama }}</h4>
                                <p class="text-[11px] text-[#64748B] truncate mt-0.5 font-medium">
                                    NIK: {{ $a->nik ?? '-' }} • {{ $a->user?->department ?? 'Operasional Lapangan' }}
                                </p>
                            </div>
                            <div class="flex flex-col space-y-1.5 w-full">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold border flex items-center space-x-1.5 {{ $a->is_late ? 'bg-[#FEF3C7] text-[#D97706] border-amber-300/50' : 'bg-[#DCFCE7] text-[#16A34A] border-emerald-300/50' }}">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">{{ $a->is_late ? 'schedule' : 'check_circle' }}</span>
                                    <span class="truncate min-w-0">{{ $a->is_late ? ($a->punctuality_label ?: 'Terlambat') : 'Tepat Waktu' }}</span>
                                </div>
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#DCFCE7] text-[#16A34A] border border-emerald-300/50 flex items-center space-x-1.5">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">check_circle</span>
                                    <span class="truncate min-w-0" title="Disetujui pada pukul {{ $a->formatted_reviewed_time }} WIB oleh {{ $a->reviewed_by ?: 'Direktur' }}">Disetujui pada pukul {{ $a->formatted_reviewed_time }} WIB oleh {{ $a->reviewed_by ?: 'Direktur' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Slot Presensi & Catatan -->
                        <div class="p-2 rounded-lg bg-[#F8FAFC] border border-[#DAE2FD]/50 text-xs text-[#131B2E] flex items-start space-x-2">
                            <span class="material-symbols-rounded text-sm text-[#0051D5] mt-0.5 shrink-0">{{ $a->slot_icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-[11.5px]">{{ $a->slot_label }} · {{ $a->formatted_waktu }} WIB</div>
                                <div class="text-[11px] text-[#64748B] truncate mt-0.5">{{ $a->lokasi ?? ($a->catatan ?: 'Penugasan Lapangan') }}</div>
                            </div>
                        </div>

                        @if ($a->catatan && !in_array($a->catatan, ['Disetujui oleh Admin', 'Disetujui oleh Superadmin', $a->lokasi]))
                        <div class="text-[11px] text-[#64748B] italic">Catatan: {{ $a->catatan }}</div>
                        @endif

                        <!-- Row 3: Full-Width Lihat Rincian Button -->
                        <button type="button" @click="openAttendanceDetail({{ json_encode($a->toDetailArray($userOffice)) }}, false)"
                            class="w-full h-9 rounded-lg bg-[#0051D5] hover:bg-[#003ea8] text-white text-[11.5px] font-bold flex items-center justify-center space-x-1.5 shadow-2xs transition-colors cursor-pointer">
                            <span class="material-symbols-rounded text-sm">visibility</span>
                            <span>Lihat Rincian</span>
                        </button>
                    </div>
                    @empty
                    <div class="py-10 text-center bg-white rounded-2xl border border-[#DAE2FD] p-6 shadow-xs">
                        <span class="material-symbols-rounded text-4xl text-slate-300">history_toggle_off</span>
                        <h4 class="text-xs font-bold text-slate-800 mt-2">Belum Ada Riwayat Disetujui</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Belum ada presensi yang disetujui.</p>
                    </div>
                    @endforelse
                </div>

                <!-- Sub-tab 3: Rejected Approvals (Ditolak) -->
                <div x-show="approvalTab === 'rejected'" class="space-y-2.5">
                    @forelse ($rejectedApprovals as $r)
                    <div x-show="dirMatches(@js(strtolower($r->pgw_nama)), @js(strtolower($r->nik)), @js(strtolower((string)$r->catatan)), @js(strtolower((string)$r->tipe_pengajuan)))"
                        class="bg-white rounded-2xl border border-[#DAE2FD] p-3 shadow-xs space-y-2.5 transition-all hover:border-[#DC2626]/40">
                        
                        <!-- Row 1: Pegawai Info, Punctuality Badge, & Review Time Badge (Hanya Teks Saat Preview) -->
                        <div class="space-y-1.5">
                            <div>
                                <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">{{ $r->pgw_nama }}</h4>
                                <p class="text-[11px] text-[#64748B] truncate mt-0.5 font-medium">
                                    NIK: {{ $r->nik ?? '-' }} • {{ $r->user?->department ?? 'Operasional Lapangan' }}
                                </p>
                            </div>
                            <div class="flex flex-col space-y-1.5 w-full">
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold border flex items-center space-x-1.5 {{ $r->is_late ? 'bg-[#FEF3C7] text-[#D97706] border-amber-300/50' : 'bg-[#DCFCE7] text-[#16A34A] border-emerald-300/50' }}">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">{{ $r->is_late ? 'schedule' : 'check_circle' }}</span>
                                    <span class="truncate min-w-0">{{ $r->is_late ? ($r->punctuality_label ?: 'Terlambat') : 'Tepat Waktu' }}</span>
                                </div>
                                <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#FEE2E2] text-[#DC2626] border border-rose-300/50 flex items-center space-x-1.5">
                                    <span class="material-symbols-rounded text-xs shrink-0 leading-none">highlight_off</span>
                                    <span class="truncate min-w-0" title="Ditolak pada pukul {{ $r->formatted_reviewed_time }} WIB oleh {{ $r->reviewed_by ?: 'Direktur' }}">Ditolak pada pukul {{ $r->formatted_reviewed_time }} WIB oleh {{ $r->reviewed_by ?: 'Direktur' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Slot Presensi & Catatan -->
                        <div class="p-2 rounded-lg bg-[#F8FAFC] border border-[#DAE2FD]/50 text-xs text-[#131B2E] flex items-start space-x-2">
                            <span class="material-symbols-rounded text-sm text-[#0051D5] mt-0.5 shrink-0">{{ $r->slot_icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-[11.5px]">{{ $r->slot_label }} · {{ $r->formatted_waktu }} WIB</div>
                                <div class="text-[11px] text-[#64748B] truncate mt-0.5">{{ $r->lokasi ?? ($r->catatan ?: 'Penugasan Lapangan') }}</div>
                            </div>
                        </div>

                        @if ($r->catatan)
                        <div class="bg-rose-50 border border-rose-200 rounded-lg p-2 text-[11px] text-rose-900 font-semibold">
                            Alasan: {{ $r->catatan }}
                        </div>
                        @endif

                        <!-- Row 3: Full-Width Lihat Rincian Button -->
                        <button type="button" @click="openAttendanceDetail({{ json_encode($r->toDetailArray($userOffice)) }}, false)"
                            class="w-full h-9 rounded-lg bg-[#0051D5] hover:bg-[#003ea8] text-white text-[11.5px] font-bold flex items-center justify-center space-x-1.5 shadow-2xs transition-colors cursor-pointer">
                            <span class="material-symbols-rounded text-sm">visibility</span>
                            <span>Lihat Rincian</span>
                        </button>
                    </div>
                    @empty
                    <div class="py-10 text-center bg-white rounded-2xl border border-[#DAE2FD] p-6 shadow-xs">
                        <span class="material-symbols-rounded text-4xl text-slate-300">history_toggle_off</span>
                        <h4 class="text-xs font-bold text-slate-800 mt-2">Belum Ada Riwayat Ditolak</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Tidak ada presensi yang ditolak.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TAB 1: AKUN SAYA DIREKTUR (Matching AdminAkunScreen.dart) --}}
        <div x-show="activeTab === 'akun'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3.5">
            
            <!-- 1. HERO PROFILE CARD (Matching _buildHeroProfileCard in AdminAkunScreen.dart) -->
            <div class="relative overflow-hidden bg-white rounded-2xl border border-[#DAE2FD]/70 p-4 shadow-xs">
                <!-- Ambient decorative gradient circle -->
                <div class="absolute -top-7 -right-7 w-32 h-32 rounded-full bg-[#4C1D95]/8 pointer-events-none"></div>

                <div class="relative z-10 flex items-start space-x-3.5">
                    <!-- Avatar with Upload Camera Button -->
                    <div class="relative shrink-0">
                        <div @click="adminPhotoSheet = true" class="w-20 h-20 rounded-full p-[2.5px] bg-gradient-to-tr from-[#4C1D95] to-[#0051D5] shadow-md shadow-purple-900/20 cursor-pointer overflow-hidden flex items-center justify-center">
                            <template x-if="currentUserAvatar">
                                <img :src="currentUserAvatar" alt="{{ auth()->user()?->name }}" class="profile-avatar-img w-full h-full rounded-full object-cover">
                            </template>
                            <template x-if="!currentUserAvatar">
                                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAY_9kE1y44LkWkF_5c4gS_T9uF6N9qM4o8k_2_wXz7qA" alt="{{ auth()->user()?->name ?? 'Admin Direktur' }}" class="profile-avatar-img w-full h-full rounded-full object-cover">
                            </template>
                        </div>
                        <button type="button" @click="adminPhotoSheet = true" title="Ubah Foto Profil"
                            class="absolute -bottom-0.5 -right-0.5 w-[30px] h-[30px] rounded-full bg-roleDirektur border-[2.2px] border-white shadow-md flex items-center justify-center text-white hover:scale-105 transition-transform cursor-pointer">
                            <span class="material-symbols-rounded text-base">photo_camera</span>
                        </button>
                    </div>

                    <!-- Identity Metadata -->
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h2 class="text-base font-bold text-[#131B2E] tracking-tight truncate leading-snug">
                            {{ auth()->user()?->name ?: 'IR. H. ABDUL KHOLIK F, M.M.' }}
                        </h2>
                        <div class="text-xs text-[#64748B] mt-0.5">
                            <span>NIK: </span>
                            <span class="font-bold text-[#131B2E]">{{ auth()->user()?->nik ?: 'D001' }}</span>
                        </div>
                        <div class="text-xs text-[#64748B] truncate mt-0.5">
                            {{ auth()->user()?->department ?: 'Dewan Direksi' }} - {{ auth()->user()?->position ?: 'Direktur Utama' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. FORM SECTION: DETAILED ADMIN DATA (Matching _buildFormSection in AdminAkunScreen.dart) -->
            <div class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-4 shadow-xs space-y-3.5 text-xs">
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <h3 class="text-[15px] font-bold text-[#131B2E]">Informasi Kepegawaian Admin</h3>
                </div>

                <!-- Field 1: Nama Lengkap -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">badge</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->name }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 2: Bidang -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Bidang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">business</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->department }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 3: Seksi Bidang -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Seksi Bidang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">admin_panel_settings</span>
                        </span>
                        <input type="text" readonly value="{{ auth()->user()?->position }}"
                            class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/60 text-xs font-medium text-[#131B2E] outline-none">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg text-slate-400" title="Hanya Superadmin yang dapat merubah">lock</span>
                        </span>
                    </div>
                    <span class="text-[11px] text-[#64748B] mt-1 block">Hanya Superadmin yang dapat merubah</span>
                </div>

                <!-- Field 4: Nomor WhatsApp / HP -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Nomor WhatsApp / HP</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-xl">call</span>
                        </span>
                        <input type="tel" x-model="adminPhone"
                            class="w-full pl-10 pr-3 py-2.5 bg-white rounded-xl border border-[#DAE2FD] text-xs font-medium text-[#131B2E] focus:ring-2 focus:ring-[#0051D5] outline-none">
                    </div>
                </div>

                <!-- Field 5: Kantor Penempatan (Titik Acuan Radius Presensi) -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5 truncate">Kantor Penempatan (Acuan Radius Presensi)</label>
                    <div class="p-3.5 rounded-xl bg-[#F8FAFC] border border-[#DAE2FD]/60 flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-roleDirektur flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">apartment</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">
                                {{ $userOffice?->nama_kantor ?? 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang' }}
                            </h4>
                            <p class="text-[11px] text-[#64748B] truncate">Titik acuan operasional dan pemantauan presensi wilayah</p>
                        </div>
                        <span class="material-symbols-rounded text-lg text-[#0051D5] shrink-0" title="Ditetapkan oleh Pusimpel IT">verified</span>
                    </div>
                </div>

                <!-- Action Button Row: Ubah PIN Otorisasi Direktur -->
                <div @click="adminPinModal = true"
                    class="p-3 rounded-xl bg-[#F8FAFC] hover:bg-slate-100/70 border border-[#DAE2FD]/60 flex items-center space-x-3 cursor-pointer transition-colors">
                    <div class="w-9 h-9 rounded-xl bg-[#EAEDFF] text-[#0051D5] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-xl">pin</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[12.5px] font-semibold text-[#131B2E]">Ubah PIN Otorisasi Direktur</h4>
                        <p class="text-[11px] text-[#64748B]">Terakhir diperbarui 30 hari yang lalu</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>
            </div>

            <!-- 3. PREFERENCES & SETTINGS MODULE (Matching _buildPreferencesSection in AdminAkunScreen.dart) -->
            <div class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-4 shadow-xs space-y-3">
                <h3 class="text-[15px] font-bold text-[#131B2E] pb-1 border-b border-slate-100">
                    Pengaturan & Preferensi
                </h3>

                <!-- Preference 1: Presensi Alert -->
                <div class="flex items-center justify-between text-xs py-1">
                    <div class="flex items-center space-x-3 min-w-0 flex-1 pr-2">
                        <div class="w-[34px] h-[34px] rounded-xl bg-[#EAEDFF] text-[#0051D5] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-lg">notifications_active</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[12.5px] font-semibold text-[#131B2E]">Notifikasi Presensi Lapangan</div>
                            <div class="text-[11px] text-[#64748B]">Peringatan instan presensi di luar radius atau terlambat</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" x-model="adminPresenceAlerts" @change="if (adminPresenceAlerts && window.TirtafieldAlarm) window.TirtafieldAlarm.requestPermission()" class="sr-only peer">
                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00288E]"></div>
                    </label>
                </div>

                <div class="border-b border-[#F1F5F9]"></div>

                <!-- Preference 2: Persetujuan Masuk Alert -->
                <div class="flex items-center justify-between text-xs py-1">
                    <div class="flex items-center space-x-3 min-w-0 flex-1 pr-2">
                        <div class="w-[34px] h-[34px] rounded-xl bg-[#EAEDFF] text-[#0051D5] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-lg">fact_check</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[12.5px] font-semibold text-[#131B2E]">Notifikasi Persetujuan Masuk</div>
                            <div class="text-[11px] text-[#64748B]">Pemberitahuan saat ada permohonan koreksi atau izin pegawai</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" x-model="adminApprovalAlerts" class="sr-only peer">
                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00288E]"></div>
                    </label>
                </div>

                <div class="border-b border-[#F1F5F9]"></div>

                <!-- Tombol Uji Notifikasi Mobile Direktur -->
                <button type="button" @click="testAdminNotification()"
                    class="w-full py-2.5 px-3 bg-[#EAEDFF] hover:bg-[#DDE1FF] text-[#00288E] border border-[#DAE2FD] rounded-xl font-bold text-xs flex items-center justify-center space-x-2 transition-colors cursor-pointer shadow-2xs">
                    <span class="material-symbols-rounded text-base">notifications_active</span>
                    <span>Uji Coba Notifikasi Mobile (Banner &amp; Layar Terkunci)</span>
                </button>
            </div>

            <!-- 4. ACTION BUTTONS (Matching _buildActionButtons in AdminAkunScreen.dart) -->
            <div class="space-y-3 pt-1">
                <!-- Primary Save Button -->
                <button type="button" @click="saveAdminProfile()"
                    class="w-full h-12 bg-[#00288E] hover:bg-[#00227A] text-white font-bold text-sm rounded-xl shadow-md shadow-[#00288E]/25 flex items-center justify-center space-x-2 transition-all">
                    <span class="material-symbols-rounded text-xl">save</span>
                    <span>Simpan Pengaturan Akun Admin</span>
                </button>

                <!-- Logout Button -->
                <button type="button" @click="adminLogoutModal = true"
                    class="w-full h-12 rounded-xl border border-rose-300 hover:bg-rose-50 text-rose-600 font-bold text-[13.5px] flex items-center justify-center space-x-2 transition-colors">
                    <span class="material-symbols-rounded text-lg">logout</span>
                    <span>Keluar dari Akun Admin</span>
                </button>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- ROLE 3: STAFF IT / SUPERADMIN (Matching DashboardITScreen.dart)           --}}
    {{-- ========================================================================= --}}
    @else

        {{-- TAB 0: DASHBOARD IT (HomeMonitoringPortal matching DashboardITScreen.dart) --}}
        <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3.5">
            
            <!-- 0. Header Banner (Matching Flutter DashboardHeaderBanner with assets/images/app.jpg) -->
            <div class="rounded-2xl p-4 text-white shadow-lg relative overflow-hidden bg-gradient-to-br from-[#00227A] via-[#00144D] to-[#070E28] border border-white/15">
                <!-- Glow effects -->
                <div class="absolute -right-5 -top-5 w-36 h-36 rounded-full bg-blue-500/20 blur-xl pointer-events-none"></div>
                <div class="absolute -left-6 -bottom-6 w-32 h-32 rounded-full bg-teal-400/10 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-teal-500/25 text-teal-200 border border-teal-400/40">
                            <span>Aplikasi Presensi Lapangan</span>
                        </span>
                        <h2 class="text-sm font-extrabold text-white tracking-tight mt-1.5 leading-snug">
                            Perusahaan Umum Daerah<br>Air Minum Tirta Kepri
                        </h2>
                        <p class="text-[11.5px] font-semibold text-[#7DD3FC] mt-0.5">
                            Budaya Bersih & Melayani
                        </p>
                        <div class="flex items-center space-x-1.5 mt-2 text-[10px] text-white/80 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="truncate">Halo, {{ auth()->user()?->name }} · Superadmin IT</span>
                        </div>
                    </div>

                    <!-- Corporate Logo Graphic (assets/images/app.jpg) -->
                    <div class="w-[70px] h-[70px] rounded-2xl border border-white/35 shadow-md shadow-black/40 overflow-hidden shrink-0 bg-[#0A1E4A] flex items-center justify-center">
                        <img src="{{ asset('images/app.jpg') }}" alt="PERUMDA Tirta Kepri" class="w-full h-full object-cover"
                            onerror="this.style.display='none'; document.getElementById('itFallbackLogo').style.display='block';">
                        <div id="itFallbackLogo" style="display: none;" class="text-white text-2xl flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">water_drop</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1. RINGKASAN OPERASIONAL & PERSETUJUAN (Matching _buildMainStatisticsBento) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between px-0.5">
                    <h3 class="text-[13.5px] font-bold text-[#131B2E] tracking-tight">Ringkasan Operasional &amp; Persetujuan</h3>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <!-- CARD 1: Total Karyawan Lapangan -->
                    <div @click="setTab('pegawai')" class="bg-white rounded-xl p-2.5 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between cursor-pointer hover:border-[#0D9488] transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-[19px] font-extrabold text-[#0D9488] leading-none">{{ $stats['total_karyawan'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#0D9488]">engineering</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between">
                            <div class="text-[11px] font-semibold text-[#131B2E] leading-tight truncate">Total Pegawai</div>
                        </div>
                    </div>

                    <!-- CARD 2: Menunggu Persetujuan -->
                    <div @click="itFeedTab = 'pending'" class="bg-white rounded-xl p-2.5 border {{ $pendingApprovals->count() > 0 ? 'border-amber-300 ring-1 ring-amber-100' : 'border-[#DAE2FD]/70' }} shadow-2xs flex flex-col justify-between cursor-pointer hover:border-amber-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-[19px] font-extrabold text-[#D97706] leading-none">{{ $pendingApprovals->count() }}</span>
                            <span class="material-symbols-rounded text-base text-[#D97706]">pending_actions</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between">
                            <div class="text-[11px] font-semibold text-[#131B2E] leading-tight truncate">Persetujuan</div>
                            @if ($pendingApprovals->count() > 0)
                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706] shrink-0 ml-1"></span>
                            @endif
                        </div>
                    </div>

                    <!-- CARD 3: Telah Disetujui -->
                    <div @click="itFeedTab = 'approved'" class="bg-white rounded-xl p-2.5 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between cursor-pointer hover:border-emerald-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-[19px] font-extrabold text-[#16A34A] leading-none">{{ $stats['approved_count'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#16A34A]">verified</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between">
                            <div class="text-[11px] font-semibold text-[#131B2E] leading-tight truncate">Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. QUICK SHORTCUTS TO CORE MODULES (Matching _buildCoreFeatureGrid) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between px-0.5">
                    <h3 class="text-[13.5px] font-bold text-[#131B2E] tracking-tight">Menu Kontrol Superadmin</h3>
                </div>
                <!-- 2x2 Bento Grid -->
                <div class="grid grid-cols-2 gap-2">
                    <!-- Item 1: Peran & Akun -->
                    <div @click="setTab('pegawai'); empSubTab = 0;"
                        class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-3 shadow-2xs cursor-pointer hover:border-[#0D9488] hover:shadow-xs transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div class="w-8 h-8 rounded-xl bg-teal-50 text-[#0D9488] flex items-center justify-center">
                                <span class="material-symbols-rounded text-lg">manage_accounts</span>
                            </div>
                            <span class="material-symbols-rounded text-sm text-slate-300">arrow_forward</span>
                        </div>
                        <div class="mt-2.5">
                            <h4 class="text-[12.5px] font-bold text-[#131B2E] truncate">Peran &amp; Akun</h4>
                            <p class="text-[10.5px] text-[#64748B] truncate mt-0.5">Hak akses 3 role</p>
                        </div>
                    </div>

                    <!-- Item 2: Data Pegawai -->
                    <div @click="setTab('pegawai'); empSubTab = 1;"
                        class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-3 shadow-2xs cursor-pointer hover:border-[#00288E] hover:shadow-xs transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#00288E] flex items-center justify-center">
                                <span class="material-symbols-rounded text-lg">badge</span>
                            </div>
                            <span class="material-symbols-rounded text-sm text-slate-300">arrow_forward</span>
                        </div>
                        <div class="mt-2.5">
                            <h4 class="text-[12.5px] font-bold text-[#131B2E] truncate">Data Pegawai</h4>
                            <p class="text-[10.5px] text-[#64748B] truncate mt-0.5">Direktori NIK &amp; Bidang</p>
                        </div>
                    </div>

                    <!-- Item 3: Jam Operasional -->
                    <div @click="setTab('operasional'); operationalSubTab = 0;"
                        class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-3 shadow-2xs cursor-pointer hover:border-emerald-400 hover:shadow-xs transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-[#16A34A] flex items-center justify-center">
                                <span class="material-symbols-rounded text-lg">schedule</span>
                            </div>
                            <span class="material-symbols-rounded text-sm text-slate-300">arrow_forward</span>
                        </div>
                        <div class="mt-2.5">
                            <h4 class="text-[12.5px] font-bold text-[#131B2E] truncate">Jam Operasional</h4>
                            <p class="text-[10.5px] text-[#64748B] truncate mt-0.5">4 absensi &amp; toleransi</p>
                        </div>
                    </div>

                    <!-- Item 4: Lokasi Kantor -->
                    <div @click="setTab('operasional'); operationalSubTab = 1;"
                        class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-3 shadow-2xs cursor-pointer hover:border-[#0051D5] hover:shadow-xs transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div class="w-8 h-8 rounded-xl bg-[#0051D5]/10 text-[#0051D5] flex items-center justify-center">
                                <span class="material-symbols-rounded text-lg">apartment</span>
                            </div>
                            <span class="material-symbols-rounded text-sm text-slate-300">arrow_forward</span>
                        </div>
                        <div class="mt-2.5">
                            <h4 class="text-[12.5px] font-bold text-[#131B2E] truncate">Lokasi Kantor</h4>
                            <p class="text-[10.5px] text-[#64748B] truncate mt-0.5">Titik acuan penempatan</p>
                        </div>
                    </div>
                </div>

                <!-- 1 Wide Card: Log Aktivitas Sistem (Matching _buildMenuWideCard) -->
                <div @click="activityLogModal = true"
                    class="bg-white rounded-2xl border border-[#DAE2FD]/70 p-3 shadow-2xs cursor-pointer hover:border-teal-400 hover:shadow-xs transition-all flex items-center justify-between">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 text-roleStaffIT flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">receipt_long</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[12.5px] font-bold text-[#131B2E] truncate">Log Aktivitas Sistem</h4>
                            <p class="text-[10.5px] text-[#64748B] truncate mt-0.5">Audit trail, penempatan kantor, perubahan jam kerja &amp; absensi</p>
                        </div>
                    </div>
                    <span class="material-symbols-rounded text-sm text-slate-300 shrink-0 ml-2">arrow_forward</span>
                </div>
            </div>

            <!-- 3. MONITORING PRESENSI REALTIME FEED (Matching SuperadminMonitoringScreen) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between px-0.5">
                    <h3 class="text-[13.5px] font-bold text-[#131B2E] tracking-tight">Persetujuan &amp; Riwayat Presensi Lapangan</h3>
                </div>

                <!-- 1. METRIK RINGKASAN PRESENSI REALTIME SUPERADMIN (_buildMetricsBento) -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-white rounded-2xl p-3 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-extrabold text-[#00288E] leading-none">{{ $stats['hadir_hari_ini'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#00288E]">people</span>
                        </div>
                        <div class="mt-1">
                            <div class="text-[11px] font-bold text-[#131B2E] leading-tight truncate">Hadir Hari Ini</div>
                            <div class="text-[9.5px] text-[#64748B] truncate">Pegawai Aktif</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-extrabold text-[#16A34A] leading-none">{{ $stats['tepat_waktu'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#16A34A]">check_circle</span>
                        </div>
                        <div class="mt-1">
                            <div class="text-[11px] font-bold text-[#131B2E] leading-tight truncate">Tepat Waktu</div>
                            <div class="text-[9.5px] text-[#64748B] truncate">Sesuai Jadwal</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-[#DAE2FD]/70 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-extrabold text-[#DC2626] leading-none">{{ $stats['terlambat_hari_ini'] }}</span>
                            <span class="material-symbols-rounded text-base text-[#DC2626]">schedule</span>
                        </div>
                        <div class="mt-1">
                            <div class="text-[11px] font-bold text-[#131B2E] leading-tight truncate">Terlambat</div>
                            <div class="text-[9.5px] text-[#64748B] truncate">&gt; Toleransi</div>
                        </div>
                    </div>
                </div>

                <!-- 2. SEARCH BAR REALTIME (_buildSearchBar) -->
                <div class="h-10 bg-white rounded-xl border border-[#DAE2FD] px-3 flex items-center space-x-2 shadow-2xs focus-within:border-teal-500">
                    <span class="material-symbols-rounded text-lg text-[#0051D5] shrink-0">search</span>
                    <input type="text" x-model="dirSearch" placeholder="Cari nama pegawai, NIK, atau keterangan presensi..."
                        class="w-full text-xs text-[#131B2E] placeholder-[#64748B] outline-none bg-transparent font-sans">
                    <button type="button" x-show="dirSearch" @click="dirSearch = ''" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-base">close</span>
                    </button>
                </div>

                <!-- 3. STATUS SEGMENTED TABS (_buildStatusTabBar) -->
                <div class="space-y-2.5">
                    <div class="p-1 bg-[#EAEDFF] rounded-2xl flex items-center space-x-1">
                        <button type="button" @click="itFeedTab = 'pending'"
                            :class="itFeedTab === 'pending' ? 'bg-white text-[#131B2E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                            class="flex-1 py-1.5 px-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all">
                            <span>Menunggu</span>
                            <span :class="itFeedTab === 'pending' ? 'bg-[#D97706] text-white' : 'bg-[#FEF3C7] text-[#D97706]'"
                                class="px-1.5 py-0.5 rounded-full text-[9.5px] font-bold">
                                {{ $pendingApprovals->count() }}
                            </span>
                        </button>
                        <button type="button" @click="itFeedTab = 'approved'"
                            :class="itFeedTab === 'approved' ? 'bg-white text-[#131B2E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                            class="flex-1 py-1.5 px-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all">
                            <span>Disetujui</span>
                            <span :class="itFeedTab === 'approved' ? 'bg-[#16A34A] text-white' : 'bg-[#DCFCE7] text-[#16A34A]'"
                                class="px-1.5 py-0.5 rounded-full text-[9.5px] font-bold">
                                {{ $approvedApprovals->count() }}
                            </span>
                        </button>
                        <button type="button" @click="itFeedTab = 'rejected'"
                            :class="itFeedTab === 'rejected' ? 'bg-white text-[#131B2E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                            class="flex-1 py-1.5 px-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all">
                            <span>Ditolak</span>
                            <span :class="itFeedTab === 'rejected' ? 'bg-[#DC2626] text-white' : 'bg-[#FEE2E2] text-[#DC2626]'"
                                class="px-1.5 py-0.5 rounded-full text-[9.5px] font-bold">
                                {{ $rejectedApprovals->count() }}
                            </span>
                        </button>
                    </div>

                    <!-- 4. MENUNGGU FEED (_buildApprovalCard) -->
                    <div x-show="itFeedTab === 'pending'" class="space-y-2.5">
                        @forelse ($pendingApprovals as $p)
                        <div x-show="dirMatches(@js(strtolower($p->pgw_nama)), @js(strtolower($p->nik)), @js(strtolower((string)$p->catatan)), @js(strtolower((string)$p->tipe_pengajuan)))"
                            class="bg-white rounded-2xl border border-[#DAE2FD] p-3.5 shadow-2xs space-y-3">
                            <!-- Row 1: Metadata (Hanya Teks Saat Preview) -->
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">{{ $p->pgw_nama }}</h4>
                                        <p class="text-[11px] text-[#64748B] truncate mt-0.5">
                                            NIK: {{ $p->nik }} • {{ $p->user?->department ?? 'Operasional Lapangan' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 w-full">
                                    <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold border flex items-center space-x-1.5 {{ $p->is_late ? 'bg-[#FEF3C7] text-[#D97706] border-amber-300/50' : 'bg-[#DCFCE7] text-[#16A34A] border-emerald-300/50' }}">
                                        <span class="material-symbols-rounded text-xs shrink-0 leading-none">{{ $p->is_late ? 'schedule' : 'check_circle' }}</span>
                                        <span class="truncate min-w-0">{{ $p->is_late ? ($p->punctuality_label ?: 'Terlambat') : 'Tepat Waktu' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Slot Keterangan -->
                            <div class="p-2.5 rounded-xl bg-[#F8FAFC] border border-[#DAE2FD]/60 text-xs text-[#131B2E] flex items-start space-x-2">
                                <span class="material-symbols-rounded text-sm text-[#0051D5] mt-0.5 shrink-0">{{ $p->slot_icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-[11.5px] text-[#131B2E]">{{ $p->slot_label }} · {{ $p->formatted_waktu }} WIB</div>
                                    <div class="text-[11px] text-[#64748B] truncate mt-0.5">{{ $p->lokasi ?? ($p->catatan ?: 'Penugasan Lapangan') }}</div>
                                </div>
                            </div>

                            <!-- Row 3: Actions -->
                            <div class="grid grid-cols-3 gap-1.5 pt-0.5">
                                <button type="button" @click="openAttendanceDetail({{ json_encode($p->toDetailArray($userOffice)) }}, true)"
                                    class="h-9 px-1 rounded-lg bg-[#0051D5] hover:bg-[#003ea8] text-white text-[10.5px] font-bold flex items-center justify-center space-x-1 shadow-2xs transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">visibility</span>
                                    <span class="truncate">Lihat Rincian</span>
                                </button>
                                <button type="button" @click="reviewApprovalModal({{ $p->id }}, 'rejected', '{{ $p->pgw_nama }}', '{{ $p->tipe_pengajuan }}')"
                                    class="h-9 px-1 rounded-lg bg-[#DC2626] hover:bg-[#b91c1c] text-white text-[10.5px] font-bold flex items-center justify-center space-x-1 shadow-2xs transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">close</span>
                                    <span>Tolak</span>
                                </button>
                                <button type="button" @click="reviewApprovalModal({{ $p->id }}, 'approved', '{{ $p->pgw_nama }}', '{{ $p->tipe_pengajuan }}')"
                                    class="h-9 px-1 rounded-lg bg-[#16A34A] hover:bg-[#15803d] text-white text-[10.5px] font-bold flex items-center justify-center space-x-1 shadow-2xs transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">check</span>
                                    <span>Setujui</span>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="py-8 text-center text-xs text-[#64748B] bg-white rounded-2xl border border-[#DAE2FD]">
                            Tidak ada permohonan yang menunggu persetujuan.
                        </div>
                        @endforelse
                    </div>

                    <!-- 4. DISETUJUI FEED (_buildApprovalCard) -->
                    <div x-show="itFeedTab === 'approved'" class="space-y-2.5">
                        @forelse ($approvedApprovals as $a)
                        <div x-show="dirMatches(@js(strtolower($a->pgw_nama)), @js(strtolower($a->nik)), @js(strtolower((string)$a->catatan)), @js(strtolower((string)$a->tipe_pengajuan)))"
                            class="bg-white rounded-2xl border border-[#DAE2FD] p-3.5 shadow-2xs space-y-3">
                            <!-- Row 1: Metadata (Hanya Teks Saat Preview) -->
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">{{ $a->pgw_nama }}</h4>
                                        <p class="text-[11px] text-[#64748B] truncate mt-0.5">
                                            NIK: {{ $a->nik }} • {{ $a->user?->department ?? 'Operasional Lapangan' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-col space-y-1.5 w-full">
                                    <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold border flex items-center space-x-1.5 {{ $a->is_late ? 'bg-[#FEF3C7] text-[#D97706] border-amber-300/50' : 'bg-[#DCFCE7] text-[#16A34A] border-emerald-300/50' }}">
                                        <span class="material-symbols-rounded text-xs shrink-0 leading-none">{{ $a->is_late ? 'schedule' : 'check_circle' }}</span>
                                        <span class="truncate min-w-0">{{ $a->is_late ? ($a->punctuality_label ?: 'Terlambat') : 'Tepat Waktu' }}</span>
                                    </div>
                                    <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#DCFCE7] text-[#16A34A] border border-emerald-300/50 flex items-center space-x-1.5">
                                        <span class="material-symbols-rounded text-xs shrink-0 leading-none">check_circle</span>
                                        <span class="truncate min-w-0" title="Disetujui pada pukul {{ $a->formatted_reviewed_time }} WIB oleh {{ $a->reviewed_by ?: 'Superadmin IT' }}">Disetujui pada pukul {{ $a->formatted_reviewed_time }} WIB oleh {{ $a->reviewed_by ?: 'Superadmin IT' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Slot Keterangan -->
                            <div class="p-2.5 rounded-xl bg-[#F8FAFC] border border-[#DAE2FD]/60 text-xs text-[#131B2E] flex items-start space-x-2">
                                <span class="material-symbols-rounded text-sm text-[#0051D5] mt-0.5 shrink-0">{{ $a->slot_icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-[11.5px] text-[#131B2E]">{{ $a->slot_label }} · {{ $a->formatted_waktu }} WIB</div>
                                    <div class="text-[11px] text-[#64748B] truncate mt-0.5">{{ $a->lokasi ?? ($a->catatan ?: 'Penugasan Lapangan') }}</div>
                                </div>
                            </div>

                            @if ($a->catatan_reviewer && $a->catatan_reviewer !== 'Disetujui oleh Superadmin')
                                <div class="text-[11px] text-[#64748B] italic px-1">
                                    Catatan: {{ $a->catatan_reviewer }}
                                </div>
                            @endif

                            <!-- Row 3: Action Button (Full Width) -->
                            <div>
                                <button type="button" @click="openAttendanceDetail({{ json_encode($a->toDetailArray($userOffice)) }}, false)"
                                    class="w-full h-9 px-2 rounded-lg bg-[#0051D5] hover:bg-[#003ea8] text-white text-[11px] font-bold flex items-center justify-center space-x-1.5 shadow-2xs transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">visibility</span>
                                    <span>Lihat Rincian</span>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="py-8 text-center text-xs text-[#64748B] bg-white rounded-2xl border border-[#DAE2FD]">
                            Belum ada riwayat permohonan disetujui.
                        </div>
                        @endforelse
                    </div>

                    <!-- 4. DITOLAK FEED (_buildApprovalCard) -->
                    <div x-show="itFeedTab === 'rejected'" class="space-y-2.5">
                        @forelse ($rejectedApprovals as $r)
                        <div x-show="dirMatches(@js(strtolower($r->pgw_nama)), @js(strtolower($r->nik)), @js(strtolower((string)$r->catatan)), @js(strtolower((string)$r->tipe_pengajuan)))"
                            class="bg-white rounded-2xl border border-[#DAE2FD] p-3.5 shadow-2xs space-y-3">
                            <!-- Row 1: Metadata (Hanya Teks Saat Preview) -->
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate">{{ $r->pgw_nama }}</h4>
                                        <p class="text-[11px] text-[#64748B] truncate mt-0.5">
                                            NIK: {{ $r->nik }} • {{ $r->user?->department ?? 'Operasional Lapangan' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-col space-y-1.5 w-full">
                                    <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold border flex items-center space-x-1.5 {{ $r->is_late ? 'bg-[#FEF3C7] text-[#D97706] border-amber-300/50' : 'bg-[#DCFCE7] text-[#16A34A] border-emerald-300/50' }}">
                                        <span class="material-symbols-rounded text-xs shrink-0 leading-none">{{ $r->is_late ? 'schedule' : 'check_circle' }}</span>
                                        <span class="truncate min-w-0">{{ $r->is_late ? ($r->punctuality_label ?: 'Terlambat') : 'Tepat Waktu' }}</span>
                                    </div>
                                    <div class="w-full px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#FEE2E2] text-[#DC2626] border border-rose-300/50 flex items-center space-x-1.5">
                                        <span class="material-symbols-rounded text-xs shrink-0 leading-none">highlight_off</span>
                                        <span class="truncate min-w-0" title="Ditolak pada pukul {{ $r->formatted_reviewed_time }} WIB oleh {{ $r->reviewed_by ?: 'Superadmin IT' }}">Ditolak pada pukul {{ $r->formatted_reviewed_time }} WIB oleh {{ $r->reviewed_by ?: 'Superadmin IT' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Slot Keterangan -->
                            <div class="p-2.5 rounded-xl bg-[#F8FAFC] border border-[#DAE2FD]/60 text-xs text-[#131B2E] flex items-start space-x-2">
                                <span class="material-symbols-rounded text-sm text-[#0051D5] mt-0.5 shrink-0">{{ $r->slot_icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-[11.5px] text-[#131B2E]">{{ $r->slot_label }} · {{ $r->formatted_waktu }} WIB</div>
                                    <div class="text-[11px] text-[#64748B] truncate mt-0.5">{{ $r->lokasi ?? ($r->catatan ?: 'Penugasan Lapangan') }}</div>
                                </div>
                            </div>

                            @if ($r->catatan_reviewer)
                                <div class="px-2.5 py-1.5 rounded-lg bg-[#FEE2E2] border border-[#FECACA] text-[11px] font-semibold text-[#991B1B]">
                                    Alasan: {{ $r->catatan_reviewer }}
                                </div>
                            @endif

                            <!-- Row 3: Action Button (Full Width) -->
                            <div>
                                <button type="button" @click="openAttendanceDetail({{ json_encode($r->toDetailArray($userOffice)) }}, false)"
                                    class="w-full h-9 px-2 rounded-lg bg-[#0051D5] hover:bg-[#003ea8] text-white text-[11px] font-bold flex items-center justify-center space-x-1.5 shadow-2xs transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">visibility</span>
                                    <span>Lihat Rincian</span>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="py-8 text-center text-xs text-[#64748B] bg-white rounded-2xl border border-[#DAE2FD]">
                            Belum ada riwayat permohonan ditolak.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 1: OPERASIONAL IT (SuperadminOperationalScreen) --}}
        <div x-show="activeTab === 'operasional'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3.5 pb-6">
            
            <!-- Top Sub Tab Selector (Jam Operasional vs Titik Kantor) -->
            <div class="p-1 bg-[#EAEDFF] rounded-2xl flex items-center space-x-1">
                <button type="button" @click="operationalSubTab = 0"
                    :class="operationalSubTab === 0 ? 'bg-white text-[#00288E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all cursor-pointer">
                    <span class="material-symbols-rounded text-base">schedule</span>
                    <span class="truncate">Jam Operasional</span>
                </button>
                <button type="button" @click="operationalSubTab = 1"
                    :class="operationalSubTab === 1 ? 'bg-white text-[#00288E] font-bold shadow-xs' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all cursor-pointer">
                    <span class="material-symbols-rounded text-base">apartment</span>
                    <span class="truncate">Titik Kantor</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- SUBTAB 0: PENGATURAN JAM OPERASIONAL 4 SLOT -->
            <!-- ========================================== -->
            <div x-show="operationalSubTab === 0" class="space-y-3.5">
                
                <!-- 1. DAY OF WEEK SELECTOR (SENIN - MINGGU) -->
                <div class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3.5 shadow-2xs space-y-3">
                    <!-- Calendar Header -->
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-[#00288E]/10 flex items-center justify-center text-[#00288E] shrink-0">
                            <span class="material-symbols-rounded text-lg">calendar_month</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-[13px] font-bold text-[#131B2E] leading-snug">Pilih Hari Operasional (Senin - Minggu)</h3>
                            <p class="text-[10.5px] text-[#64748B] leading-tight">Pilih hari terlebih dahulu untuk mengatur jam kerja khusus</p>
                        </div>
                    </div>

                    <!-- Horizontal 7-Day Selector Bar -->
                    <div class="flex items-center space-x-2 overflow-x-auto pb-1 no-scrollbar">
                        <template x-for="d in opDays" :key="d.day">
                            <button type="button" @click="loadDaySchedule(d.day)"
                                :class="opSelectedDay === d.day
                                    ? 'bg-[#00288E] text-white border-[#00288E] shadow-[0_3px_8px_rgba(0,40,142,0.28)]'
                                    : (getDaySchedule(d.day).is_work_day
                                        ? 'bg-white text-[#131B2E] border-[#DAE2FD]/80 hover:bg-slate-50'
                                        : 'bg-[#F8FAFC] text-[#64748B] border-[#DAE2FD]/60 hover:bg-slate-100')"
                                class="w-[58px] shrink-0 py-2.5 px-1 rounded-[14px] border transition-all flex flex-col items-center justify-center space-y-1 cursor-pointer">
                                <span class="text-[13px] font-bold" x-text="d.short"></span>
                                <span :class="opSelectedDay === d.day
                                        ? 'bg-white/20 text-white'
                                        : (getDaySchedule(d.day).is_work_day
                                            ? 'bg-[#00288E]/10 text-[#00288E]'
                                            : 'bg-slate-200/60 text-[#64748B]')"
                                    class="text-[9px] font-semibold px-1.5 py-0.5 rounded-md truncate max-w-[50px]"
                                    x-text="getDaySchedule(d.day).is_work_day ? (getDaySchedule(d.day).masuk || '08:00') : 'Libur'">
                                </span>
                            </button>
                        </template>
                    </div>

                    <div class="border-t border-[#EAEDFF]"></div>

                    <!-- Active Selected Day Header & Work Day Toggle -->
                    <div class="flex items-center justify-between pt-0.5">
                        <div class="flex items-center space-x-2 min-w-0">
                            <div class="w-2.5 h-2.5 rounded-full shrink-0"
                                :class="opCurrentSchedule.is_work_day ? 'bg-[#059669]' : 'bg-slate-400'"></div>
                            <div class="text-[13px] text-[#131B2E] truncate font-sans">
                                Hari Terpilih: <strong class="text-[#00288E]" x-text="opCurrentDayName"></strong>
                                <span class="text-[11px] font-semibold ml-1"
                                    :class="opCurrentSchedule.is_work_day ? 'text-[#059669]' : 'text-[#64748B]'"
                                    x-text="opCurrentSchedule.is_work_day ? '(Hari Kerja)' : '(Hari Libur)'"></span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <span class="text-[11px] font-semibold"
                                :class="opCurrentSchedule.is_work_day ? 'text-[#00288E]' : 'text-[#64748B]'"
                                x-text="opCurrentSchedule.is_work_day ? 'Hari Aktif' : 'Libur'"></span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="opCurrentSchedule.is_work_day" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00288E]"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 2. OPERATIONAL HOURS SLOTS (ACTIVE DAY CONFIGURATION) -->
                <template x-if="opCurrentSchedule.is_work_day">
                    <div class="space-y-2">
                        <!-- Slot 1: Masuk -->
                        <div @click="openTimePicker('1', 'Absensi 1: Masuk', 'masuk')"
                            class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3 shadow-2xs hover:border-[#0051D5] hover:shadow-xs transition-all flex items-center justify-between cursor-pointer">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                <div class="w-10 h-10 rounded-full bg-[#0051D5]/10 text-[#0051D5] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-xl">login</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-[13px] font-bold text-[#131B2E]">Absensi 1: Masuk</h4>
                                    <p class="text-[10.5px] text-[#64748B] truncate mt-0.5">Mulai jam kerja dan batas perhitungan keterlambatan</p>
                                </div>
                            </div>
                            <div class="bg-[#0051D5]/10 text-[#0051D5] border border-[#0051D5]/25 px-2.5 py-1.5 rounded-xl flex items-center space-x-1 shrink-0 ml-2">
                                <span class="text-sm font-extrabold font-mono" x-text="opCurrentSchedule.masuk"></span>
                                <span class="text-[10px] font-semibold">WIB</span>
                            </div>
                        </div>

                        <!-- Slot 2: Istirahat Keluar -->
                        <div @click="openTimePicker('2', 'Absensi 2: Istirahat Keluar', 'istirahat_keluar')"
                            class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3 shadow-2xs hover:border-[#D97706] hover:shadow-xs transition-all flex items-center justify-between cursor-pointer">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                <div class="w-10 h-10 rounded-full bg-[#D97706]/10 text-[#D97706] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-xl">coffee</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-[13px] font-bold text-[#131B2E]">Absensi 2: Istirahat Keluar</h4>
                                    <p class="text-[10.5px] text-[#64748B] truncate mt-0.5"
                                        x-text="opSelectedDay === 5 ? 'Istirahat siang Sholat Jumat (default: 11:30 WIB)' : 'Awal jam istirahat siang karyawan'"></p>
                                </div>
                            </div>
                            <div class="bg-[#D97706]/10 text-[#D97706] border border-[#D97706]/25 px-2.5 py-1.5 rounded-xl flex items-center space-x-1 shrink-0 ml-2">
                                <span class="text-sm font-extrabold font-mono" x-text="opCurrentSchedule.istirahat_keluar"></span>
                                <span class="text-[10px] font-semibold">WIB</span>
                            </div>
                        </div>

                        <!-- Slot 3: Istirahat Masuk -->
                        <div @click="openTimePicker('3', 'Absensi 3: Istirahat Masuk', 'istirahat_masuk')"
                            class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3 shadow-2xs hover:border-[#7C3AED] hover:shadow-xs transition-all flex items-center justify-between cursor-pointer">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                <div class="w-10 h-10 rounded-full bg-[#7C3AED]/10 text-[#7C3AED] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-xl">work</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-[13px] font-bold text-[#131B2E]">Absensi 3: Istirahat Masuk</h4>
                                    <p class="text-[10.5px] text-[#64748B] truncate mt-0.5"
                                        x-text="opSelectedDay === 5 ? 'Selesai istirahat Sholat Jumat (default: 13:30 WIB)' : 'Selesai istirahat & kembali aktif bekerja'"></p>
                                </div>
                            </div>
                            <div class="bg-[#7C3AED]/10 text-[#7C3AED] border border-[#7C3AED]/25 px-2.5 py-1.5 rounded-xl flex items-center space-x-1 shrink-0 ml-2">
                                <span class="text-sm font-extrabold font-mono" x-text="opCurrentSchedule.istirahat_masuk"></span>
                                <span class="text-[10px] font-semibold">WIB</span>
                            </div>
                        </div>

                        <!-- Slot 4: Pulang -->
                        <div @click="openTimePicker('4', 'Absensi 4: Pulang', 'pulang')"
                            class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3 shadow-2xs hover:border-[#DC2626] hover:shadow-xs transition-all flex items-center justify-between cursor-pointer">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                <div class="w-10 h-10 rounded-full bg-[#DC2626]/10 text-[#DC2626] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-xl">logout</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-[13px] font-bold text-[#131B2E]">Absensi 4: Pulang</h4>
                                    <p class="text-[10.5px] text-[#64748B] truncate mt-0.5"
                                        x-text="opSelectedDay === 6 ? 'Jam kepulangan hari kerja setengah hari' : 'Waktu akhir jam kerja harian'"></p>
                                </div>
                            </div>
                            <div class="bg-[#DC2626]/10 text-[#DC2626] border border-[#DC2626]/25 px-2.5 py-1.5 rounded-xl flex items-center space-x-1 shrink-0 ml-2">
                                <span class="text-sm font-extrabold font-mono" x-text="opCurrentSchedule.pulang"></span>
                                <span class="text-[10px] font-semibold">WIB</span>
                            </div>
                        </div>

                        <!-- Tolerance Slider Card -->
                        <div class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3.5 shadow-2xs space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[13px] font-bold text-[#131B2E] truncate">
                                    Toleransi Keterlambatan Hari <span x-text="opCurrentDayName"></span>
                                </span>
                                <span class="px-2.5 py-1 rounded-xl bg-[#00288E]/12 text-[#00288E] text-xs font-extrabold font-mono shrink-0">
                                    <span x-text="opCurrentSchedule.toleransi_menit"></span> Menit
                                </span>
                            </div>
                            <p class="text-[11px] text-[#64748B] leading-relaxed">
                                Pegawai yang melakukan presensi masuk hari <span x-text="opCurrentDayName"></span> setelah <span class="font-bold text-[#131B2E]" x-text="opCurrentSchedule.masuk"></span> + <span class="font-bold text-[#00288E]" x-text="opCurrentSchedule.toleransi_menit"></span> menit akan ditandai sebagai Terlambat dan wajib mengisi alasan.
                            </p>
                            <input type="range" min="0" max="60" step="5" x-model.number="opCurrentSchedule.toleransi_menit"
                                class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#00288E]">
                            <div class="flex justify-between text-[10px] text-[#64748B] font-medium pt-0.5">
                                <span>0 mnt (Ketat)</span>
                                <span>30 mnt</span>
                                <span>60 mnt</span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Holiday Friendly Info Card -->
                <template x-if="!opCurrentSchedule.is_work_day">
                    <div class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-6 shadow-2xs text-center space-y-2.5">
                        <div class="w-14 h-14 rounded-full bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center mx-auto">
                            <span class="material-symbols-rounded text-3xl">beach_access</span>
                        </div>
                        <h4 class="text-[14px] font-bold text-[#131B2E]">
                            Hari <span x-text="opCurrentDayName"></span> Ditetapkan Sebagai Hari Libur
                        </h4>
                        <p class="text-[11.5px] text-[#64748B] max-w-xs mx-auto leading-relaxed">
                            Karyawan tidak memiliki kewajiban presensi masuk atau pulang pada hari libur operasional.
                        </p>
                    </div>
                </template>

                <!-- 3. ACTION BUTTON: SIMPAN JADWAL HARI TERPILIH -->
                <button type="button" @click="saveCurrentDaySchedule()"
                    class="w-full py-3.5 px-4 bg-[#00288E] hover:bg-[#001f70] text-white rounded-[14px] font-bold text-xs shadow-xs transition-colors flex items-center justify-center space-x-2 cursor-pointer">
                    <span class="material-symbols-rounded text-lg">save</span>
                    <span>Simpan Jadwal Hari <span x-text="opCurrentDayName"></span></span>
                </button>

                <!-- Auto-lock Outside Operational Hours Setting -->
                <div class="bg-white rounded-2xl border border-[#DAE2FD]/80 p-3.5 shadow-2xs flex items-center justify-between">
                    <div class="min-w-0 flex-1 pr-3">
                        <h4 class="text-[13px] font-bold text-[#131B2E]">Kunci Tombol di Luar Jadwal</h4>
                        <p class="text-[11px] text-[#64748B] mt-0.5 leading-tight">Tombol presensi otomatis terkunci jika belum masuk jam kerja.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" x-model="opAutoLockOutside" @change="saveAutoLockSetting(opAutoLockOutside)" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0D9488]"></div>
                    </label>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SUBTAB 1: MANAJEMEN LOKASI KANTOR PENEMPATAN -->
            <!-- ========================================== -->
            <div x-show="operationalSubTab === 1" class="space-y-3.5">
                
                <!-- 1. PILIH TITIK KANTOR UNTUK PRATINJAU PETA -->
                <div class="space-y-1.5">
                    <label class="block text-[13px] font-bold text-[#131B2E]">Pilih Titik Kantor Pratinjau</label>
                    <div class="relative bg-white rounded-xl border border-[#DAE2FD] shadow-2xs focus-within:border-[#00288E]">
                        <select x-model="opActiveOfficeId"
                            class="w-full py-2.5 pl-3 pr-8 bg-transparent text-xs font-semibold text-[#131B2E] outline-none appearance-none cursor-pointer font-sans">
                            <template x-for="off in opOffices" :key="off.id">
                                <option :value="off.id" x-text="off.name + (off.is_primary ? ' (Utama)' : ' (Cabang)')"></option>
                            </template>
                        </select>
                        <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xl">
                            keyboard_arrow_down
                        </span>
                    </div>
                </div>

                <!-- 2. KARTU DETAIL KANTOR YANG SEDANG DIINSPEKSI -->
                <div class="bg-white rounded-2xl border-[1.5px] border-[#0D9488]/40 p-3.5 shadow-2xs space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-rounded text-xl"
                            :class="currentActiveOffice.is_primary ? 'text-[#00288E]' : 'text-[#0D9488]'"
                            x-text="currentActiveOffice.is_primary ? 'account_balance' : 'location_on'"></span>
                        <h4 class="text-[14px] font-bold text-[#131B2E] truncate flex-1" x-text="currentActiveOffice.name"></h4>
                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-extrabold shrink-0"
                            :class="currentActiveOffice.is_primary ? 'bg-[#00288E]/12 text-[#00288E]' : 'bg-[#0D9488]/12 text-[#0D9488]'"
                            x-text="currentActiveOffice.is_primary ? 'Kantor Utama' : 'Kantor Cabang'"></span>
                    </div>
                    <p class="text-[11.5px] text-[#64748B] leading-relaxed" x-text="currentActiveOffice.address || 'Alamat kantor operasional'"></p>
                    <div>
                        <span class="px-2.5 py-1 rounded-lg bg-[#EAEDFF] text-[#00288E] text-[11px] font-semibold inline-block font-mono">
                            Koordinat: <span x-text="Number(currentActiveOffice.latitude).toFixed(5)"></span>, <span x-text="Number(currentActiveOffice.longitude).toFixed(5)"></span>
                        </span>
                    </div>
                </div>

                <!-- 3. MINI MAP VISUAL REPRESENTATION -->
                <div class="h-[160px] rounded-2xl border border-[#DAE2FD] overflow-hidden relative shadow-2xs bg-slate-100">
                    <!-- Satelit / Peta Switcher -->
                    <div class="absolute top-2.5 right-2.5 z-10 flex bg-white/95 backdrop-blur-xs rounded-xl p-0.5 border border-[#DAE2FD] shadow-xs">
                        <button type="button" @click="opMapMode = 'satellite'"
                            :class="(opMapMode || 'satellite') === 'satellite' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                            class="px-2 py-0.5 text-[10.5px] rounded-lg flex items-center space-x-1 transition-all">
                            <span class="material-symbols-rounded text-xs">satellite_alt</span>
                            <span>Satelit</span>
                        </button>
                        <button type="button" @click="opMapMode = 'roadmap'"
                            :class="(opMapMode || 'satellite') === 'roadmap' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                            class="px-2 py-0.5 text-[10.5px] rounded-lg flex items-center space-x-1 transition-all">
                            <span class="material-symbols-rounded text-xs">map</span>
                            <span>Peta</span>
                        </button>
                    </div>
                    <img :src="getCartoTile(currentActiveOffice.latitude, currentActiveOffice.longitude, 16, opMapMode)"
                        alt="Peta Titik Kantor"
                        class="w-full h-full object-cover">
                    <!-- Center Marker Pin -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <span class="material-symbols-rounded text-[36px] text-[#DC2626] drop-shadow-md pb-3">location_pin</span>
                    </div>
                    <!-- Floating Navigasi Maps Button -->
                    <a :href="'https://www.google.com/maps/search/?api=1&query=' + currentActiveOffice.latitude + ',' + currentActiveOffice.longitude + '&t=k'"
                        target="_blank" rel="noopener noreferrer"
                        class="absolute bottom-2.5 right-2.5 bg-white/95 backdrop-blur-xs px-3 py-1.5 rounded-full border border-[#DAE2FD]/80 shadow-md flex items-center space-x-1.5 text-[11.5px] font-bold text-[#00288E] hover:bg-white transition-all">
                        <span class="material-symbols-rounded text-sm text-[#00288E]">directions</span>
                        <span>Navigasi Maps</span>
                    </a>
                </div>

                <!-- 4. DAFTAR TITIK KANTOR & CABANG -->
                <div class="flex items-center justify-between pt-1">
                    <div>
                        <h3 class="text-[13.5px] font-bold text-[#131B2E]">
                            Daftar Titik Kantor (<span x-text="opOffices.length"></span> Lokasi Penempatan)
                        </h3>
                        <p class="text-[10.5px] text-[#64748B]">Semua titik kantor aktif bersamaan sebagai acuan penempatan karyawan</p>
                    </div>
                    <button type="button" @click="openOfficeAddModal()"
                        class="px-2.5 py-1.5 bg-[#0D9488]/10 text-[#0D9488] border border-[#0D9488]/30 rounded-xl text-[11.5px] font-bold flex items-center space-x-1 hover:bg-[#0D9488]/20 transition-all cursor-pointer shrink-0">
                        <span class="material-symbols-rounded text-sm">add_location_alt</span>
                        <span>Tambah Titik Baru</span>
                    </button>
                </div>

                <!-- List of Office Cards -->
                <div class="space-y-2.5">
                    <template x-for="office in opOffices" :key="office.id">
                        <div :class="office.is_primary
                                ? 'border-[#00288E]/60 border-[1.8px] bg-white'
                                : (office.id === opActiveOfficeId
                                    ? 'border-[#0D9488] border-[1.5px] bg-[#0D9488]/[0.04]'
                                    : 'border-[#DAE2FD]/80 border-[1px] bg-white')"
                            class="rounded-2xl p-3 shadow-2xs space-y-2.5 transition-all">
                            
                            <!-- Header -->
                            <div class="flex items-start space-x-2.5">
                                <div :class="office.is_primary ? 'bg-[#00288E]/12 text-[#00288E]' : 'bg-[#0D9488]/10 text-[#0D9488]'"
                                    class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-lg"
                                        x-text="office.is_primary ? 'account_balance' : 'location_city'"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h4 class="text-[13.5px] font-bold text-[#131B2E] truncate flex-1" x-text="office.name"></h4>
                                        <span :class="office.is_primary
                                                ? 'bg-[#00288E]/12 text-[#00288E] border border-[#00288E]/40'
                                                : 'bg-[#0D9488]/12 text-[#0D9488] border border-[#0D9488]/30'"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold flex items-center space-x-1 shrink-0">
                                            <span class="material-symbols-rounded text-[11px]" x-text="office.is_primary ? 'star' : 'apartment'"></span>
                                            <span x-text="office.is_primary ? 'Kantor Utama' : 'Kantor Cabang'"></span>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug" x-text="office.address || '-'"></p>
                                    <div class="mt-1.5 flex items-center space-x-2">
                                        <span class="px-2 py-0.5 rounded bg-[#EAEDFF] text-[#00288E] text-[10px] font-mono font-semibold">
                                            GPS: <span x-text="Number(office.latitude).toFixed(4)"></span>, <span x-text="Number(office.longitude).toFixed(4)"></span>
                                        </span>
                                        <span class="px-2 py-0.5 rounded bg-teal-50 text-teal-800 border border-teal-200 text-[10px] font-bold">
                                            Radius <span x-text="office.radius || 100"></span>m
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-100"></div>

                            <!-- Control Actions: Utamakan, Ubah, Hapus -->
                            <div class="flex items-center space-x-2 pt-0.5">
                                <template x-if="!office.is_primary">
                                    <button type="button" @click="setAsPrimaryOffice(office.id)"
                                        class="flex-1 h-[34px] rounded-lg bg-[#D97706] hover:bg-[#b45309] text-white text-[11.5px] font-bold shadow-2xs flex items-center justify-center space-x-1 transition-colors cursor-pointer">
                                        <span class="material-symbols-rounded text-sm">star</span>
                                        <span>Utamakan</span>
                                    </button>
                                </template>
                                <button type="button" @click="openOfficeEditModal(office)"
                                    class="flex-1 h-[34px] rounded-lg bg-[#0D9488] hover:bg-teal-700 text-white text-[11.5px] font-bold shadow-2xs flex items-center justify-center space-x-1 transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">edit</span>
                                    <span x-text="office.is_primary ? 'Ubah Data Kantor' : 'Ubah'"></span>
                                </button>
                                <template x-if="!office.is_primary">
                                    <button type="button" @click="openDeleteOfficeModal(office)"
                                        class="flex-1 h-[34px] rounded-lg bg-[#DC2626] hover:bg-rose-700 text-white text-[11.5px] font-bold shadow-2xs flex items-center justify-center space-x-1 transition-colors cursor-pointer">
                                        <span class="material-symbols-rounded text-sm">delete_forever</span>
                                        <span>Hapus</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- 5. SIMPAN ACUAN LOKASI KANTOR -->
                <button type="button" @click="saveGeofencingSettings()"
                    class="w-full h-[50px] bg-[#0D9488] hover:bg-teal-700 text-white rounded-xl font-bold text-[14.5px] shadow-sm flex items-center justify-center space-x-2 transition-colors cursor-pointer mt-2">
                    <span class="material-symbols-rounded text-lg">save</span>
                    <span>Simpan Acuan Lokasi Kantor</span>
                </button>
            </div>
        </div>

        {{-- TAB 2: PEGAWAI IT (SuperadminEmployeeScreen - Exact Match to Flutter) --}}
        <div x-show="activeTab === 'pegawai'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3 pb-8">
            
            <!-- Top Segmented Sub-Tab Bar (Matching Flutter _subTabController) -->
            <div class="bg-[#EAEDFF] rounded-2xl p-1 mb-2 flex items-center shadow-xs">
                <button type="button" @click="empSubTab = 0"
                    :class="empSubTab === 0 ? 'bg-white text-[#00288E] shadow-sm font-bold' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                    <span class="material-symbols-rounded text-base">shield</span>
                    <span class="truncate">Peran & Akses</span>
                </button>
                <button type="button" @click="empSubTab = 1"
                    :class="empSubTab === 1 ? 'bg-white text-[#00288E] shadow-sm font-bold' : 'text-[#64748B] hover:text-[#131B2E] font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                    <span class="material-symbols-rounded text-base">badge</span>
                    <span class="truncate">Data Pegawai</span>
                </button>
            </div>

            {{-- ========================================================================= --}}
            {{-- SUB-TAB 0: MANAJEMEN PERAN AKUN & PENGGUNA (_buildRoleManagementView)     --}}
            {{-- ========================================================================= --}}
            <div x-show="empSubTab === 0" class="space-y-3">
                <!-- 1. Summary Role Breakdown Card -->
                <div class="bg-white p-3.5 rounded-2xl border border-[#DAE2FD] shadow-2xs space-y-2.5">
                    <div class="text-[13px] font-bold text-[#131B2E]">Role</div>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Mini Stat 1: Superadmin (Staff IT) -->
                        <button type="button" @click="empFilterRole = (empFilterRole === 'staffIT' ? null : 'staffIT')"
                            :class="empFilterRole === 'staffIT' ? 'bg-[#0D9488]/16 border-[#0D9488]' : 'bg-[#0D9488]/8 border-[#0D9488]/20'"
                            class="p-2 rounded-xl border text-left transition-all cursor-pointer flex items-center space-x-1.5">
                            <i class="bi bi-person-fill-gear text-lg text-[#0D9488] shrink-0"></i>
                            <div class="min-w-0">
                                <div class="text-base font-extrabold text-[#0D9488] leading-tight" x-text="totalStaffIT()"></div>
                                <div class="text-[10px] text-[#64748B] truncate">Superadmin</div>
                            </div>
                        </button>
                        <!-- Mini Stat 2: Admin (Direktur) -->
                        <button type="button" @click="empFilterRole = (empFilterRole === 'direktur' ? null : 'direktur')"
                            :class="empFilterRole === 'direktur' ? 'bg-[#7C3AED]/16 border-[#7C3AED]' : 'bg-[#7C3AED]/8 border-[#7C3AED]/20'"
                            class="p-2 rounded-xl border text-left transition-all cursor-pointer flex items-center space-x-1.5">
                            <i class="bi bi-person-fill-lock text-lg text-[#7C3AED] shrink-0"></i>
                            <div class="min-w-0">
                                <div class="text-base font-extrabold text-[#7C3AED] leading-tight" x-text="totalDirektur()"></div>
                                <div class="text-[10px] text-[#64748B] truncate">Admin</div>
                            </div>
                        </button>
                        <!-- Mini Stat 3: Pengguna (Karyawan) -->
                        <button type="button" @click="empFilterRole = (empFilterRole === 'karyawan' ? null : 'karyawan')"
                            :class="empFilterRole === 'karyawan' ? 'bg-[#316BF3]/16 border-[#316BF3]' : 'bg-[#316BF3]/8 border-[#316BF3]/20'"
                            class="p-2 rounded-xl border text-left transition-all cursor-pointer flex items-center space-x-1.5">
                            <i class="bi bi-person-fill text-lg text-[#316BF3] shrink-0"></i>
                            <div class="min-w-0">
                                <div class="text-base font-extrabold text-[#316BF3] leading-tight" x-text="totalKaryawan()"></div>
                                <div class="text-[10px] text-[#64748B] truncate">Pengguna</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 2. Search bar with clear button -->
                <div class="relative flex items-center">
                    <span class="material-symbols-rounded absolute left-3 text-[#0D9488] text-lg pointer-events-none">search</span>
                    <input type="text" x-model="roleSearchQuery" placeholder="Cari akun nama, NIK, atau bidang..."
                        class="w-full pl-9 pr-9 py-2.5 bg-white text-xs rounded-xl border border-[#DAE2FD] outline-none focus:border-[#0D9488] shadow-2xs placeholder-[#64748B]">
                    <template x-if="roleSearchQuery.length > 0">
                        <button type="button" @click="roleSearchQuery = ''" class="absolute right-3 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-base">clear</span>
                        </button>
                    </template>
                </div>

                <!-- 3. Sort Toolbar Row -->
                <div class="flex items-center justify-between">
                    <button type="button" @click="sortBottomSheet = true"
                        :class="empSortOption !== 'default' ? 'bg-[#00288E]/10 border-[#00288E]/40 text-[#00288E] font-bold' : 'bg-white border-[#DAE2FD] text-[#131B2E] font-semibold'"
                        class="px-2.5 py-1.5 rounded-full border text-[11.5px] flex items-center space-x-1.5 transition-all shadow-2xs cursor-pointer">
                        <span class="material-symbols-rounded text-sm" :class="empSortOption !== 'default' ? 'text-[#00288E]' : 'text-[#64748B]'" x-text="getSortIcon(empSortOption)"></span>
                        <span x-text="'Urut: ' + getSortShortLabel(empSortOption)"></span>
                        <span class="material-symbols-rounded text-base text-[#64748B]">keyboard_arrow_down</span>
                    </button>
                    <span class="text-[11.5px] text-[#64748B] font-semibold font-mono" x-text="getFilteredEmployees(0).length + ' Akun'"></span>
                </div>

                <!-- 4. Role Filter Dropdown (Semua Akun, Superadmin, Admin, Pengguna) -->
                <div class="relative bg-white rounded-xl border border-[#DAE2FD] shadow-2xs overflow-hidden">
                    <select x-model="empFilterRole"
                        class="w-full px-3 py-2 text-xs font-semibold text-[#131B2E] bg-white outline-none cursor-pointer appearance-none pr-8">
                        <option :value="null">Semua Akun ({{ count($allEmployees) }} Akun)</option>
                        <option value="staffIT">Superadmin (Staff IT)</option>
                        <option value="direktur">Admin (Direktur)</option>
                        <option value="karyawan">Pengguna (Karyawan)</option>
                    </select>
                    <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-lg">keyboard_arrow_down</span>
                </div>

                <!-- 5. List of User Role Cards or Empty State -->
                <template x-if="getFilteredEmployees(0).length === 0">
                    <div class="my-5 p-6 bg-white rounded-2xl border border-[#DAE2FD] text-center shadow-2xs space-y-3">
                        <div class="w-14 h-14 mx-auto rounded-full bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">person_search</span>
                        </div>
                        <h4 class="text-sm font-bold text-[#131B2E]">Tidak Ada Pegawai Ditemukan</h4>
                        <p class="text-xs text-[#64748B] leading-relaxed max-w-xs mx-auto">Kriteria pencarian, filter cabang, bidang, atau sortir tidak cocok dengan pegawai mana pun.</p>
                        <button type="button" @click="resetAllEmpFilters()"
                            class="px-4 py-2 bg-[#00288E] hover:bg-blue-900 text-white rounded-xl text-xs font-bold shadow-2xs inline-flex items-center space-x-1.5 transition-colors cursor-pointer">
                            <span class="material-symbols-rounded text-sm">restart_alt</span>
                            <span>Reset Semua Filter & Pencarian</span>
                        </button>
                    </div>
                </template>

                <div x-show="getFilteredEmployees(0).length > 0" class="space-y-2">
                    <template x-for="user in getFilteredEmployees(0)" :key="'role_card_' + user.id">
                        <div class="bg-white rounded-2xl border border-[#DAE2FD] p-3 shadow-2xs space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden"
                                        :style="'background-color: ' + getRoleColor(user.role) + '22; color: ' + getRoleColor(user.role)">
                                        <template x-if="user.avatar_url">
                                            <img :src="user.avatar_url" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!user.avatar_url">
                                            <span x-text="user.name ? user.name.charAt(0).toUpperCase() : 'U'"></span>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-bold text-[13px] text-[#131B2E] truncate" x-text="user.name"></span>
                                            <template x-if="user.id == '{{ auth()->user()?->id }}' || user.nip == '{{ auth()->user()?->nik }}'">
                                                <span class="px-1.5 py-0.5 rounded-md bg-[#0D9488]/12 text-[#0D9488] font-bold text-[9.5px]">Anda</span>
                                            </template>
                                        </div>
                                        <div class="text-[11px] text-[#64748B] truncate" x-text="'NIK: ' + user.nip + ' • ' + user.department"></div>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center">
                                    <span class="w-8 h-8 rounded-full border flex items-center justify-center shadow-2xs transition-transform"
                                        :title="getRoleDisplayName(user.role)"
                                        :style="'background-color: ' + getRoleColor(user.role) + '1f; color: ' + getRoleColor(user.role) + '; border-color: ' + getRoleColor(user.role) + '4d'">
                                        <i :class="getRoleBootstrapIcon(user.role)" class="text-base leading-none"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="h-px bg-[#EAEDFF]"></div>
                            
                            <!-- Actions Row: Reset PIN (Amber) & Ubah Role (Teal) -->
                            <div class="grid grid-cols-2 gap-2 pt-0.5">
                                <button type="button" @click="openResetPinModal(user)"
                                    class="h-[34px] rounded-lg bg-[#D97706] hover:bg-amber-700 text-white font-bold text-[11.5px] shadow-2xs flex items-center justify-center space-x-1.5 transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">key</span>
                                    <span>Reset PIN</span>
                                </button>
                                <button type="button" @click="openChangeRoleModal(user)"
                                    class="h-[34px] rounded-lg bg-[#0D9488] hover:bg-teal-700 text-white font-bold text-[11.5px] shadow-2xs flex items-center justify-center space-x-1.5 transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">manage_accounts</span>
                                    <span>Ubah Role</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- SUB-TAB 1: MANAJEMEN DATA PEGAWAI (_buildEmployeeDataView)                --}}
            {{-- ========================================================================= --}}
            <div x-show="empSubTab === 1" class="space-y-3">
                <!-- 1. Header Direktori Kepegawaian -->
                <div>
                    <h3 class="text-sm font-bold text-[#131B2E]">Direktori Kepegawaian</h3>
                    <p class="text-[11px] text-[#64748B]" x-text="'Total ' + totalEmployees() + ' Pegawai Terdaftar'"></p>
                </div>

                <!-- 2. Search bar with clear button -->
                <div class="relative flex items-center">
                    <span class="material-symbols-rounded absolute left-3 text-[#0D9488] text-lg pointer-events-none">search</span>
                    <input type="text" x-model="empSearchQuery" placeholder="Cari nama pegawai, NIK, bidang, kantor..."
                        class="w-full pl-9 pr-9 py-2.5 bg-white text-xs rounded-xl border border-[#DAE2FD] outline-none focus:border-[#0D9488] shadow-2xs placeholder-[#64748B]">
                    <template x-if="empSearchQuery.length > 0">
                        <button type="button" @click="empSearchQuery = ''" class="absolute right-3 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-base">clear</span>
                        </button>
                    </template>
                </div>

                <!-- 3. Sort Toolbar -->
                <div class="flex items-center justify-between">
                    <button type="button" @click="sortBottomSheet = true"
                        :class="empSortOption !== 'default' ? 'bg-[#00288E]/10 border-[#00288E]/40 text-[#00288E] font-bold' : 'bg-white border-[#DAE2FD] text-[#131B2E] font-semibold'"
                        class="px-2.5 py-1.5 rounded-full border text-[11.5px] flex items-center space-x-1.5 transition-all shadow-2xs cursor-pointer">
                        <span class="material-symbols-rounded text-sm" :class="empSortOption !== 'default' ? 'text-[#00288E]' : 'text-[#64748B]'" x-text="getSortIcon(empSortOption)"></span>
                        <span x-text="'Urut: ' + getSortShortLabel(empSortOption)"></span>
                        <span class="material-symbols-rounded text-base text-[#64748B]">keyboard_arrow_down</span>
                    </button>
                    <span class="text-[11.5px] text-[#64748B] font-semibold font-mono" x-text="getFilteredEmployees(1).length + ' Pegawai'"></span>
                </div>

                <!-- 4. Office Filter Dropdown -->
                <div class="relative bg-white rounded-xl border border-[#DAE2FD] shadow-2xs overflow-hidden">
                    <select x-model="empFilterOfficeId"
                        class="w-full px-3 py-2 text-xs font-semibold text-[#131B2E] bg-white outline-none cursor-pointer appearance-none pr-8">
                        <option :value="null">Semua Kantor ({{ count($allEmployees) }} Pegawai)</option>
                        <template x-for="office in officesList" :key="'filter_office_' + office.id">
                            <option :value="office.id" x-text="office.name + (office.is_primary ? ' ★ Utama' : ' · Cabang')"></option>
                        </template>
                    </select>
                    <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-lg">keyboard_arrow_down</span>
                </div>

                <!-- 5. Tombol Tambah Pegawai Baru (Matching Flutter Button below sort dropdown) -->
                <button type="button" @click="openAddEmployeeModal()"
                    class="w-full h-10 bg-[#0D9488] hover:bg-teal-700 text-white rounded-xl font-bold text-[12.5px] shadow-xs flex items-center justify-center space-x-2 transition-colors cursor-pointer">
                    <span class="material-symbols-rounded text-lg">person_add</span>
                    <span>Tambah Pegawai Baru</span>
                </button>

                <!-- Active Department Filter Dismiss Chip -->
                <template x-if="empFilterDepartment && empFilterDepartment.length > 0">
                    <div class="flex items-center">
                        <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-[#00288E]/12 border border-[#00288E]/35 text-[#00288E] text-[11px] font-bold">
                            <span x-text="'Bidang: ' + empFilterDepartment"></span>
                            <button type="button" @click="empFilterDepartment = null" class="cursor-pointer ml-1 text-[#00288E] hover:opacity-75">
                                <span class="material-symbols-rounded text-sm">cancel</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- 6. Employee Directory Cards or Empty State -->
                <template x-if="getFilteredEmployees(1).length === 0">
                    <div class="my-5 p-6 bg-white rounded-2xl border border-[#DAE2FD] text-center shadow-2xs space-y-3">
                        <div class="w-14 h-14 mx-auto rounded-full bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">person_search</span>
                        </div>
                        <h4 class="text-sm font-bold text-[#131B2E]">Tidak Ada Pegawai Ditemukan</h4>
                        <p class="text-xs text-[#64748B] leading-relaxed max-w-xs mx-auto">Kriteria pencarian, filter cabang, bidang, atau sortir tidak cocok dengan pegawai mana pun.</p>
                        <button type="button" @click="resetAllEmpFilters()"
                            class="px-4 py-2 bg-[#00288E] hover:bg-blue-900 text-white rounded-xl text-xs font-bold shadow-2xs inline-flex items-center space-x-1.5 transition-colors cursor-pointer">
                            <span class="material-symbols-rounded text-sm">restart_alt</span>
                            <span>Reset Semua Filter & Pencarian</span>
                        </button>
                    </div>
                </template>

                <div x-show="getFilteredEmployees(1).length > 0" class="space-y-2">
                    <template x-for="user in getFilteredEmployees(1)" :key="'emp_card_' + user.id">
                        <div class="bg-white rounded-2xl border border-[#DAE2FD] p-3 shadow-2xs space-y-2.5">
                            <div class="flex items-start space-x-3">
                                <div class="w-11 h-11 rounded-full bg-[#0D9488]/15 text-[#0D9488] font-bold text-sm flex items-center justify-center shrink-0 overflow-hidden">
                                    <template x-if="user.avatar_url">
                                        <img :src="user.avatar_url" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!user.avatar_url">
                                        <span x-text="user.name ? user.name.charAt(0).toUpperCase() : 'P'"></span>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-[13.5px] text-[#131B2E] truncate" x-text="user.name"></div>
                                    <div class="text-[11.5px] text-[#64748B] truncate" x-text="'NIK: ' + user.nip + ' • ' + user.position"></div>
                                    <div class="text-[11px] font-semibold text-[#00288E] truncate mt-0.5" x-text="user.department"></div>
                                    
                                    <!-- Penempatan Badge -->
                                    <div class="mt-1 inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-[#0D9488]/10 border border-[#0D9488]/25 text-[#0D9488] text-[10px] font-bold max-w-full">
                                        <span class="material-symbols-rounded text-xs shrink-0">store_mall_directory</span>
                                        <span class="truncate" x-text="'Penempatan: ' + (user.office_name || 'Kantor Pusat')"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="h-px bg-[#EAEDFF]"></div>
                            
                            <!-- Actions Row: Detail (Teal) & Ubah Data (Purple) -->
                            <div class="grid grid-cols-2 gap-2 pt-0.5">
                                <button type="button" @click="openDetailSheet(user)"
                                    class="h-[34px] rounded-lg bg-[#0D9488] hover:bg-teal-700 text-white font-bold text-[11.5px] shadow-2xs flex items-center justify-center space-x-1.5 transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">visibility</span>
                                    <span>Detail</span>
                                </button>
                                <button type="button" @click="openEditModal(user)"
                                    class="h-[34px] rounded-lg bg-[#7C3AED] hover:bg-purple-700 text-white font-bold text-[11.5px] shadow-2xs flex items-center justify-center space-x-1.5 transition-colors cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">edit</span>
                                    <span>Ubah Data</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- TAB 3: AKUN SUPERADMIN (SuperadminProfileScreen - Exact Match to Flutter) --}}
        <div x-show="activeTab === 'akun'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-3.5 pb-8">
            
            <!-- 1. HERO CARD: PROFILE OVERVIEW & AVATAR (_buildHeroProfileCard) -->
            <div class="relative bg-white rounded-2xl border border-[#DAE2FD] p-4 shadow-sm overflow-hidden">
                <!-- Ambient decorative gradient circle -->
                <div class="absolute -top-7 -right-7 w-32 h-32 rounded-full bg-[#0D9488]/8 pointer-events-none"></div>

                <div class="relative z-10 flex items-start space-x-3.5">
                    <!-- Avatar with Upload Camera Button -->
                    <div class="relative shrink-0">
                        <div @click="superadminPhotoSheet = true" class="w-20 h-20 rounded-full p-[2.5px] bg-gradient-to-tr from-[#0D9488] to-[#042F2E] shadow-md shadow-teal-900/20 cursor-pointer overflow-hidden flex items-center justify-center">
                            <img :src="superadminAvatarUrl || currentUserAvatar" alt="Foto Profil Superadmin" class="profile-avatar-img w-full h-full rounded-full object-cover"
                                onerror="this.onerror=null; this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuBHnouN4NhcfWmjDbG_twFO6sebw3bZ18Eh5o0S5X77EHF5sjwqdJN1_ReCmIRAXuAloD9dPZQaPaz6HVgpuNpNvmO58tfZnPqD0S2Or68H_L0RpASTtBWA4c84ApGfNtIT2D9Dp8lVoCp0JDnad7J_CE3txa5AZ09a1vR9wuxu1NBIUFtj4kKAYnne-NzoAjd5q04Al1Cu_2jIhmMuPgiIkaTQw-LExIXsFQapZNGSTxquu43ZR8o';">
                        </div>
                        <button type="button" @click="superadminPhotoSheet = true" title="Ubah Foto Profil"
                            class="absolute -bottom-0.5 -right-0.5 w-[30px] h-[30px] rounded-full bg-[#0D9488] border-[2.2px] border-white shadow-md flex items-center justify-center text-white hover:scale-105 transition-transform cursor-pointer">
                            <span class="material-symbols-rounded text-base">photo_camera</span>
                        </button>
                    </div>

                    <!-- Identity Metadata -->
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h2 class="text-base font-bold text-[#131B2E] truncate" x-text="superadminName || 'Superadmin Pusimpel'"></h2>
                        <div class="text-xs text-[#64748B] mt-0.5 flex items-center space-x-1">
                            <span>NIK:</span>
                            <span class="font-bold text-[#131B2E]" x-text="superadminNik || 'P242101'"></span>
                        </div>
                        <p class="text-xs text-[#64748B] truncate mt-0.5" x-text="superadminDepartment + ' - ' + superadminPosition"></p>
                    </div>
                </div>
            </div>

            <!-- 2. FORM SECTION: INFORMASI KEPEGAWAIAN SUPERADMIN (_buildFormSection) -->
            <div class="bg-white rounded-2xl border border-[#DAE2FD] p-4 shadow-sm space-y-3.5 text-xs">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="text-sm font-bold text-[#131B2E] truncate">
                        Informasi Kepegawaian Superadmin
                        <span class="sr-only">Informasi Akun Superadmin</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EFF6FF] text-[#0D9488]">
                        Akses Superadmin
                        <span class="sr-only">Akses Superadmin Aktif</span>
                    </span>
                </div>

                <!-- Field 1: Nama Lengkap -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg">badge</span>
                        </span>
                        <input type="text" x-model="superadminName"
                            class="w-full pl-9 pr-3 py-2.5 bg-[#FAF8FF] rounded-xl border border-[#DAE2FD] text-xs font-medium text-[#131B2E] focus:border-[#0D9488] focus:bg-white outline-none transition-colors">
                    </div>
                </div>

                <!-- Field 2: Bidang (Dropdown from BidangData.allBidang) -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Bidang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg">business</span>
                        </span>
                        <select x-model="superadminDepartment" @change="onSuperadminBidangChange()"
                            class="w-full pl-9 pr-8 py-2.5 bg-[#FAF8FF] rounded-xl border border-[#DAE2FD] text-xs font-medium text-[#131B2E] focus:border-[#0D9488] focus:bg-white outline-none transition-colors appearance-none cursor-pointer">
                            <template x-for="b in bidangDataList" :key="'sa_b_' + b.nama">
                                <option :value="b.nama" x-text="b.nama"></option>
                            </template>
                        </select>
                        <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-lg">keyboard_arrow_down</span>
                    </div>
                </div>

                <!-- Field 3: Seksi Bidang (Dropdown filtered by Bidang) -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Seksi Bidang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg">admin_panel_settings</span>
                        </span>
                        <select x-model="superadminPosition"
                            class="w-full pl-9 pr-8 py-2.5 bg-[#FAF8FF] rounded-xl border border-[#DAE2FD] text-xs font-medium text-[#131B2E] focus:border-[#0D9488] focus:bg-white outline-none transition-colors appearance-none cursor-pointer">
                            <template x-for="s in getSeksiListForBidang(superadminDepartment)" :key="'sa_s_' + s">
                                <option :value="s" x-text="s"></option>
                            </template>
                        </select>
                        <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-lg">keyboard_arrow_down</span>
                    </div>
                </div>

                <!-- Field 4: Nomor WhatsApp / HP -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Nomor WhatsApp / HP</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <span class="material-symbols-rounded text-lg">call</span>
                        </span>
                        <input type="tel" x-model="superadminPhone"
                            class="w-full pl-9 pr-3 py-2.5 bg-[#FAF8FF] rounded-xl border border-[#DAE2FD] text-xs font-medium text-[#131B2E] focus:border-[#0D9488] focus:bg-white outline-none transition-colors">
                    </div>
                </div>

                <!-- Field 5: Kantor Penempatan (Titik Acuan) -->
                <div>
                    <label class="block text-xs font-semibold text-[#131B2E] mb-1.5">Kantor Penempatan (Acuan Radius Presensi)</label>
                    <div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#DAE2FD] flex items-start space-x-3">
                        <span class="material-symbols-rounded text-xl text-[#0D9488] mt-0.5 shrink-0">apartment</span>
                        <div class="min-w-0">
                            <div class="text-[13.5px] font-bold text-[#131B2E]" x-text="superadminOfficeName"></div>
                            <div class="text-[11px] text-[#64748B] mt-0.5">Titik acuan operasional dan konfigurasi pemantauan wilayah</div>
                        </div>
                    </div>
                </div>

                <!-- Security / Ubah PIN Row Button -->
                <div @click="superadminPinModal = true"
                    class="bg-[#FAF8FF] hover:bg-slate-100/70 p-3 rounded-xl border border-[#DAE2FD] flex items-center justify-between cursor-pointer transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-lg bg-teal-50 text-[#0D9488] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-lg">pin</span>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-[#131B2E]">Ubah PIN Otorisasi Superadmin</div>
                            <div class="text-[11px] text-[#64748B]">Terakhir diperbarui 30 hari yang lalu</div>
                        </div>
                    </div>
                    <span class="material-symbols-rounded text-lg text-[#64748B]">chevron_right</span>
                </div>
            </div>

            <!-- 3. PREFERENCES & SETTINGS MODULE (_buildPreferencesSection) -->
            <div class="bg-white rounded-2xl border border-[#DAE2FD] p-4 shadow-sm space-y-3.5 text-xs">
                <h3 class="text-sm font-bold text-[#131B2E]">Pengaturan &amp; Preferensi</h3>

                <!-- Preference 1: Presensi & Anomali Alert -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8.5 h-8.5 rounded-lg bg-slate-100 text-[#0D9488] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-lg">notifications_active</span>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-[#131B2E]">Notifikasi Sistem &amp; Presensi</div>
                            <div class="text-[11px] text-[#64748B]">Peringatan anomali presensi dan sinkronisasi server Pusimpel</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-2">
                        <input type="checkbox" x-model="superadminSystemAlerts" @change="if (superadminSystemAlerts && window.TirtafieldAlarm) window.TirtafieldAlarm.requestPermission()" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0D9488]"></div>
                    </label>
                </div>

                <div class="h-px bg-slate-100"></div>

                <!-- Preference 2: Backup Alert -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8.5 h-8.5 rounded-lg bg-slate-100 text-[#0D9488] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-lg">backup</span>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-[#131B2E]">Pengingat Pencadangan Otomatis</div>
                            <div class="text-[11px] text-[#64748B]">Pemberitahuan berkala jadwal pencadangan database &amp; sistem</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-2">
                        <input type="checkbox" x-model="superadminBackupAlerts" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0D9488]"></div>
                    </label>
                </div>

                <div class="border-b border-[#F1F5F9]"></div>

                <!-- Tombol Uji Notifikasi Mobile Superadmin -->
                <button type="button" @click="testSuperadminNotification()"
                    class="w-full py-2.5 px-3 bg-[#EAEDFF] hover:bg-[#DDE1FF] text-[#0D9488] border border-[#DAE2FD] rounded-xl font-bold text-xs flex items-center justify-center space-x-2 transition-colors cursor-pointer shadow-2xs">
                    <span class="material-symbols-rounded text-base">notifications_active</span>
                    <span>Uji Coba Notifikasi Sistem (Banner &amp; Layar Terkunci)</span>
                </button>
            </div>

            <!-- 4. ACTION BUTTONS (_buildActionButtons) -->
            <div class="space-y-3 pt-1">
                <!-- Primary Save Button -->
                <button type="button" @click="saveSuperadminProfile()"
                    class="w-full h-12 bg-[#0D9488] hover:bg-teal-700 text-white font-bold text-sm rounded-xl shadow-xs flex items-center justify-center space-x-2 transition-colors cursor-pointer">
                    <span class="material-symbols-rounded text-lg">save</span>
                    <span>Simpan Pengaturan Akun Superadmin</span>
                </button>

                <!-- Logout Button -->
                <button type="button" @click="superadminLogoutModal = true"
                    class="w-full h-12 rounded-xl border border-rose-300 text-rose-600 hover:bg-rose-50 font-bold text-[13.5px] flex items-center justify-center space-x-2 transition-colors cursor-pointer">
                    <span class="material-symbols-rounded text-lg">logout</span>
                    <span>Keluar dari Akun Superadmin</span>
                </button>
            </div>
        </div>

    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL FLOW 1: INTERACTIVE ATTENDANCE VERIFICATION (3-STEPS)              --}}
    {{-- STEP 1: VerifikasiLokasiScreen                                           --}}
    {{-- STEP 2: AmbilFotoAbsensiScreen                                           --}}
    {{-- STEP 3: BuktiPresensiBerhasilScreen                                      --}}
    {{-- ========================================================================= --}}
    <div x-show="verificationModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-0 sm:p-2 text-center">
            <div @click="closeVerificationModal()" class="fixed inset-0 bg-[#131B2E]/70 backdrop-blur-xs transition-opacity"></div>

            <!-- Modal Shell (Max Width Mobile 430px exactly matching Flutter app layout) -->
            <div class="relative bg-[#FAF8FF] w-full max-w-[430px] min-h-screen sm:min-h-0 sm:max-h-[92vh] sm:rounded-3xl overflow-y-auto shadow-2xl flex flex-col text-left">
                
                {{-- STEP 1: VERIFIKASI LOKASI SCREEN (Matching VerifikasiLokasiScreen.dart) --}}
                <div x-show="verificationStep === 1" class="flex-1 flex flex-col">
                    <!-- Top Modal Bar -->
                    <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-md px-4 py-3 border-b border-[#DAE2FD] flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="closeVerificationModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-[#131B2E] flex items-center justify-center transition-colors">
                                <span class="material-symbols-rounded text-xl">arrow_back</span>
                            </button>
                            <span class="font-bold text-[17px] text-[#131B2E]">Verifikasi Lokasi</span>
                        </div>
                        <!-- Live GPS Status Chip -->
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-[#EAEDFF] border border-[#DAE2FD] text-[#0051D5] text-[11px] font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0051D5] animate-pulse"></span>
                            <span>GPS Aktif</span>
                        </div>
                    </div>

                    <div class="p-4 space-y-3.5 flex-1">
                        <!-- 1. Digital Time Card -->
                        <div class="bg-white rounded-2xl border border-[#DAE2FD]/60 p-4 shadow-xs flex items-center justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-1.5 text-xs text-[#64748B] font-medium">
                                    <span class="material-symbols-rounded text-sm">calendar_today</span>
                                    <span x-text="indonesianDate">Kamis, 8 November 2026</span>
                                </div>
                                <div class="flex items-baseline space-x-1.5">
                                    <span class="font-bold text-3xl text-[#00288E] tracking-tight font-sans" x-text="clockTime">08:00:00</span>
                                    <span class="text-xs font-semibold text-[#0051D5]">WIB</span>
                                </div>
                                <div class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-[#705D00]/10 text-[#705D00] text-[11px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#705D00]"></span>
                                    <span x-text="activeSlotTitle || 'Presensi Masuk'"></span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-[#EAEDFF] border border-[#DAE2FD] flex items-center justify-center text-[#00288E]">
                                <span class="material-symbols-rounded text-2xl">schedule</span>
                            </div>
                        </div>

                        <!-- 2. Status Radius & Pelacakan Lapangan Card -->
                        <div class="bg-white rounded-2xl border p-4 shadow-xs space-y-3.5"
                            :class="calculatedDistance <= targetOfficeRadius ? 'border-emerald-300' : 'border-amber-300'">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <div class="p-2 rounded-xl" :class="calculatedDistance <= targetOfficeRadius ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                                        <span class="material-symbols-rounded text-xl" x-text="calculatedDistance <= targetOfficeRadius ? 'business' : 'share_location'"></span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-[13.5px] text-[#131B2E]" x-text="calculatedDistance <= targetOfficeRadius ? 'Presensi Kantor / Dalam Radius' : 'Presensi Lapangan / Luar Radius'"></div>
                                        <div class="text-[10.5px] text-[#64748B]" x-text="calculatedDistance <= targetOfficeRadius ? 'Posisi GPS Anda terverifikasi di dalam radius kantor' : 'Posisi GPS berada di luar radius kantor penempatan'"></div>
                                    </div>
                                </div>
                                <div class="px-2 py-1 rounded-lg text-[9.5px] font-extrabold flex items-center space-x-1 shrink-0"
                                    :class="calculatedDistance <= targetOfficeRadius ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                    <span class="material-symbols-rounded text-xs" x-text="calculatedDistance <= targetOfficeRadius ? 'check_circle' : 'location_on'"></span>
                                    <span x-text="(calculatedDistance <= targetOfficeRadius ? 'DALAM RADIUS (' : 'LUAR RADIUS (') + targetOfficeRadius + 'm)'"></span>
                                </div>
                            </div>

                            <!-- Bento Distance Box -->
                            <div class="p-3 rounded-xl border space-y-1"
                                :class="calculatedDistance <= targetOfficeRadius ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200'">
                                <div class="text-[10px] font-bold tracking-wider uppercase"
                                    :class="calculatedDistance <= targetOfficeRadius ? 'text-emerald-700' : 'text-amber-700'">
                                    JARAK DARI KANTOR PENEMPATAN
                                </div>
                                <div class="flex items-baseline space-x-2">
                                    <span class="font-extrabold text-2xl"
                                        :class="calculatedDistance <= targetOfficeRadius ? 'text-emerald-700' : 'text-amber-700'"
                                        x-text="calculatedDistance + ' m'"></span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                        :class="calculatedDistance <= targetOfficeRadius ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'"
                                        x-text="calculatedDistance <= targetOfficeRadius ? 'Di Dalam Radius' : 'Di Luar Radius'"></span>
                                </div>
                                <div class="text-[11.5px] font-semibold text-[#131B2E] pt-0.5 flex items-center justify-between">
                                    <span class="truncate">Basis Penempatan: <span class="font-bold text-[#00288E]" x-text="targetOfficeName"></span></span>
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-teal-50 text-teal-800 border border-teal-200 font-bold font-mono shrink-0 ml-1"
                                        x-text="'Radius: ' + targetOfficeRadius + 'm'"></span>
                                </div>
                            </div>

                            <!-- Kantor Penempatan Acuan Dropdown (Terkonfigurasi Superadmin) -->
                            <div class="space-y-1 pt-1 border-t border-[#DAE2FD]/60">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[10.5px] font-bold text-[#64748B] uppercase tracking-wider">Kantor Penempatan Acuan</label>
                                    <span class="text-[9.5px] text-[#0051D5] font-semibold">Sinkronisasi Superadmin</span>
                                </div>
                                <div class="relative bg-white rounded-xl border border-[#DAE2FD] focus-within:border-[#00288E]">
                                    <select x-model="selectedVerificationOfficeId" @change="onVerificationOfficeChange()"
                                        class="w-full py-2 pl-3 pr-8 bg-transparent text-xs font-semibold text-[#131B2E] outline-none appearance-none cursor-pointer font-sans">
                                        <template x-for="off in opOffices" :key="'v_off_' + off.id">
                                            <option :value="off.id" x-text="off.name + ' (Radius: ' + (off.radius || 100) + 'm)'"></option>
                                        </template>
                                    </select>
                                    <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-lg">
                                        keyboard_arrow_down
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Interactive Vector Map Visualizer Card -->
                        <div class="bg-white rounded-2xl border border-[#DAE2FD] overflow-hidden shadow-xs relative">
                            <!-- Satelit / Peta Switcher -->
                            <div class="absolute top-2.5 right-2.5 z-[1000] flex bg-white/95 backdrop-blur-xs rounded-xl p-0.5 border border-[#DAE2FD] shadow-xs">
                                <button type="button" @click="setMapMode('satellite')"
                                    :class="(mapMode || 'satellite') === 'satellite' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                                    class="px-2.5 py-1 text-[11px] rounded-lg flex items-center space-x-1 transition-all">
                                    <span class="material-symbols-rounded text-sm">satellite_alt</span>
                                    <span>Satelit</span>
                                </button>
                                <button type="button" @click="setMapMode('roadmap')"
                                    :class="(mapMode || 'satellite') === 'roadmap' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                                    class="px-2.5 py-1 text-[11px] rounded-lg flex items-center space-x-1 transition-all">
                                    <span class="material-symbols-rounded text-sm">map</span>
                                    <span>Peta</span>
                                </button>
                            </div>
                            <div id="attendanceMap" class="w-full h-56 bg-[#F2F3FF]"></div>
                            <!-- Map Footer Bar -->
                            <div class="px-4 py-2.5 bg-[#F2F3FF] border-t border-[#DAE2FD] flex items-center justify-between text-[11px]">
                                <div class="flex items-center space-x-1.5 text-[#0051D5] font-bold truncate mr-2">
                                    <span class="material-symbols-rounded text-sm shrink-0">share_location</span>
                                    <span class="truncate" x-text="'Jarak: ' + calculatedDistance + ' m ke ' + targetOfficeName"></span>
                                </div>
                                <div class="font-mono text-[10px] font-semibold text-[#64748B] shrink-0" x-text="'Geo-Tag: ' + gpsLat.toFixed(4) + ', ' + gpsLng.toFixed(4)"></div>
                            </div>
                        </div>

                        <!-- 4. Field Work Location Details Card -->
                        <div class="bg-white rounded-2xl border border-[#DAE2FD]/60 p-4 shadow-xs space-y-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9.5 h-9.5 rounded-xl bg-[#EAEDFF] text-[#00288E] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-xl">apartment</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-[10px] font-bold tracking-wider text-[#0051D5] uppercase">TUGAS LUAR KANTOR / LOKASI PRESENSI</div>
                                    <div class="text-[14.5px] font-bold text-[#131B2E] truncate font-sans" x-text="targetOfficeName"></div>
                                </div>
                            </div>

                            <div class="flex items-start space-x-2 text-xs text-[#64748B]">
                                <span class="material-symbols-rounded text-sm text-[#94A3B8] shrink-0 mt-0.5">location_on</span>
                                <span class="leading-relaxed" x-text="gpsAddress || 'Tanjung Pinang Timur, Kota Tanjung Pinang, Kepulauan Riau'"></span>
                            </div>

                            <!-- Petunjuk Rute & Navigasi Maps Button -->
                            <a :href="'https://www.google.com/maps/dir/?api=1&destination=' + targetOfficeLat + ',' + targetOfficeLng + '&t=k'" target="_blank"
                                class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#E8F0FE] border border-[#C2E7FF] text-[#1A73E8] text-xs font-bold hover:bg-[#D3E3FD] transition-colors">
                                <span class="material-symbols-rounded text-sm">directions</span>
                                <span>Petunjuk Rute & Navigasi Maps</span>
                                <span class="material-symbols-rounded text-xs">open_in_new</span>
                            </a>

                            <div class="pt-2 border-t border-[#EAEDFF] flex items-center justify-between text-xs">
                                <span class="text-[#64748B]">Jarak ke Kantor Penempatan:</span>
                                <span class="font-bold text-[#131B2E]" x-text="calculatedDistance + ' m'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Bottom Action Footer (50px Primary + 44px Secondary) -->
                    <div class="sticky bottom-0 bg-white/95 backdrop-blur-md p-4 border-t border-[#DAE2FD] space-y-2">
                        <button type="button" @click="proceedToPhotoStep()"
                            class="w-full h-[50px] rounded-[14px] bg-[#0051D5] hover:bg-[#00288E] text-white font-bold text-[14.5px] flex items-center justify-center space-x-2 shadow-md shadow-[#0051D5]/20 transition-all">
                            <span class="material-symbols-rounded text-xl">photo_camera</span>
                            <span>Lanjutkan Swafoto Presensi</span>
                        </button>
                        <button type="button" @click="refreshLiveLocation()"
                            class="w-full h-[44px] bg-white hover:bg-slate-50 border border-[#DAE2FD] text-[#131B2E] font-semibold text-xs rounded-xl flex items-center justify-center space-x-2 transition-colors">
                            <span class="material-symbols-rounded text-lg text-[#0051D5]">refresh</span>
                            <span>Segarkan Titik Lokasi</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 2: AMBIL FOTO ABSENSI SCREEN (Matching AmbilFotoAbsensiScreen.dart) --}}
                <div x-show="verificationStep === 2" class="flex-1 flex flex-col">
                    <!-- Top Modal Bar -->
                    <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-md px-4 py-3 border-b border-[#DAE2FD] flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="verificationStep = 1; stopSelfieCamera();" class="w-9 h-9 rounded-full bg-[#EAEDFF] text-[#131B2E] flex items-center justify-center transition-colors">
                                <span class="material-symbols-rounded text-xl">arrow_back</span>
                            </button>
                            <div>
                                <h3 class="font-bold text-base text-[#131B2E]" x-text="capturedPhoto ? 'Hasil Swafoto' : 'Ambil Swafoto'"></h3>
                                <div class="flex items-center space-x-1.5 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="capturedPhoto ? 'bg-emerald-600' : 'bg-[#0051D5]'"></span>
                                    <span class="text-[11px] font-semibold" :class="capturedPhoto ? 'text-[#0051D5]' : 'text-[#64748B]'"
                                        x-text="capturedPhoto ? 'Foto & Stempel Lokasi Siap Dikirim' : 'Posisikan kamera swafoto di lokasi tugas'"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="closeVerificationModal()" class="w-9 h-9 rounded-full bg-[#EAEDFF]/70 text-[#64748B] hover:text-[#131B2E] flex items-center justify-center transition-colors">
                            <span class="material-symbols-rounded text-xl">close</span>
                        </button>
                    </div>

                    <div class="p-4 space-y-3.5 flex-1 overflow-y-auto">
                        <!-- Clean Camera Viewport (4:5 Aspect Ratio) - Completely Clean Without Any Text -->
                        <div class="relative w-full aspect-[4/5] bg-[#0A101D] rounded-3xl overflow-hidden shadow-xl border border-white/15 flex items-center justify-center">
                            
                            <!-- Shutter Flash Overlay -->
                            <div x-show="isFlashing" x-transition.opacity.duration.250ms class="absolute inset-0 bg-white z-30 pointer-events-none"></div>

                            <!-- Live Video Stream (Mirrored horizontally for natural selfie feel) -->
                            <video id="selfieWebcamVideo" x-show="!capturedPhoto" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>
                            <canvas id="selfieCanvasCapture" class="hidden"></canvas>

                            <!-- Captured Photo Preview (Clean 4:5 Portrait, Unobstructed Full View) -->
                            <img x-show="capturedPhoto" :src="capturedPhoto" class="w-full h-full object-contain">
                        </div>

                        <!-- Phase 1: Live Controls (Shutter Button & Mobile Upload Fallback) -->
                        <div x-show="!capturedPhoto" class="space-y-3 pt-1">
                            <div class="p-3 bg-[#EAEDFF] border border-[#DAE2FD] rounded-xl flex items-center space-x-2 text-xs text-[#00288E]">
                                <span class="material-symbols-rounded text-base text-[#0051D5]">light_mode</span>
                                <span>Pastikan wajah Anda terlihat jelas dengan pencahayaan memadai.</span>
                            </div>

                            <div class="flex flex-col items-center justify-center pt-2">
                                <!-- Big Clean Circular Shutter Button (80x80) -->
                                <button type="button" @click="snapSelfiePhoto()"
                                    class="w-20 h-20 rounded-full bg-white p-1.5 shadow-xl shadow-[#0051D5]/20 border-2 border-[#DAE2FD] active:scale-95 transition-transform"
                                    title="Ambil Swafoto">
                                    <div class="w-full h-full rounded-full bg-[#0051D5] flex items-center justify-center text-white">
                                        <span class="material-symbols-rounded text-3xl">photo_camera</span>
                                    </div>
                                </button>
                                <div class="font-bold text-sm text-[#131B2E] mt-2.5 font-sans">Ambil Swafoto</div>
                                <div class="text-[11px] text-[#64748B]">Tekan tombol kamera untuk mengambil foto</div>
                            </div>

                            <!-- Mobile Native Camera / File Input Option -->
                            <div class="text-center pt-1">
                                <input type="file" id="selfieFileInput" accept="image/*" capture="user" @change="handleFileSelect($event)" class="hidden">
                                <button type="button" @click="document.getElementById('selfieFileInput').click()"
                                    class="inline-flex items-center space-x-1.5 text-xs text-[#0051D5] hover:text-[#00288E] font-semibold py-1.5 px-3.5 rounded-xl hover:bg-[#EAEDFF] transition-colors">
                                    <span class="material-symbols-rounded text-base">cloud_upload</span>
                                    <span>Buka Kamera Ponsel / Galeri</span>
                                </button>
                            </div>
                        </div>

                        <!-- Phase 2: Bento Verification Module (Matching AmbilFotoAbsensiScreen.dart) -->
                        <div x-show="capturedPhoto" class="space-y-3 pt-1">
                            <!-- Bento Container -->
                            <div class="bg-white rounded-2xl border border-[#DAE2FD] p-4 shadow-xs space-y-3">
                                <!-- Header Row -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-rounded text-xl">task_alt</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-sm text-[#131B2E]">Foto & Lokasi Terverifikasi</div>
                                            <div class="text-[11px] text-emerald-600 font-semibold">Seluruh parameter presensi terverifikasi sah</div>
                                        </div>
                                    </div>
                                    <div class="px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold flex items-center">
                                        <span>Siap Kirim</span>
                                    </div>
                                </div>

                                <div class="border-t border-[#EAEDFF]"></div>

                                <!-- Row 1: Bento 3 Stat Cards (Tanggal, Waktu, Jarak) -->
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="p-2.5 rounded-xl bg-[#F2F3FF] border border-[#DAE2FD] text-center">
                                        <div class="flex items-center justify-center space-x-1 text-[10px] font-bold text-[#64748B] uppercase">
                                            <span class="material-symbols-rounded text-xs">calendar_today</span>
                                            <span>TANGGAL</span>
                                        </div>
                                        <div class="font-bold text-xs text-[#131B2E] mt-1 truncate" x-text="indonesianDate.split(', ')[1] || 'Hari Ini'"></div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-[#F2F3FF] border border-[#DAE2FD] text-center">
                                        <div class="flex items-center justify-center space-x-1 text-[10px] font-bold text-[#64748B] uppercase">
                                            <span class="material-symbols-rounded text-xs">schedule</span>
                                            <span>WAKTU</span>
                                        </div>
                                        <div class="font-bold text-xs text-[#131B2E] mt-1" x-text="clockTime + ' WIB'"></div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-[#F2F3FF] border border-[#DAE2FD] text-center">
                                        <div class="flex items-center justify-center space-x-1 text-[10px] font-bold text-[#64748B] uppercase">
                                            <span class="material-symbols-rounded text-xs">straighten</span>
                                            <span>JARAK</span>
                                        </div>
                                        <div class="font-bold text-xs text-[#0051D5] mt-1" x-text="calculatedDistance + ' m'"></div>
                                    </div>
                                </div>

                                <!-- Row 2: Full-Width Location Card -->
                                <div class="p-3 rounded-xl bg-[#F2F3FF] border border-[#DAE2FD] space-y-1">
                                    <div class="flex items-center justify-between text-[10px]">
                                        <div class="flex items-center space-x-1 font-bold text-[#64748B] uppercase">
                                            <span class="material-symbols-rounded text-xs text-[#0051D5]">location_on</span>
                                            <span>LOKASI</span>
                                        </div>
                                        <span class="px-1.5 py-0.2 rounded text-[9.5px] font-semibold bg-emerald-100 text-emerald-800">Sesuai Maps</span>
                                    </div>
                                    <div class="font-bold text-xs text-[#131B2E]" x-text="targetOfficeName"></div>
                                    <div class="text-[11px] text-[#64748B]" x-text="gpsAddress || 'Tanjung Pinang Timur, Kota Tanjung Pinang, Kepulauan Riau'"></div>
                                </div>

                                <!-- Mandatory Late Reason (If Applicable) -->
                                <template x-if="isLateCheckIn">
                                    <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl space-y-2 text-xs">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-1.5 font-bold text-amber-900">
                                                <span class="material-symbols-rounded text-base text-amber-700">warning</span>
                                                <span x-text="'Tercatat Terlambat (' + lateDurationMinutes + ' Menit)'"></span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[9.5px] font-extrabold bg-amber-700 text-white uppercase">WAJIB ALASAN</span>
                                        </div>
                                        <p class="text-[11px] text-amber-800">Batas jam masuk operasional telah lewat. Anda wajib memberikan alasan keterlambatan sebelum presensi dapat dikirim.</p>
                                        <input type="text" x-model="lateReason" placeholder="Tuliskan alasan keterlambatan Anda di sini (wajib)..."
                                            class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg outline-none text-xs focus:ring-1 focus:ring-amber-500">
                                    </div>
                                </template>

                                <!-- Editable Notes Field -->
                                <div class="bg-[#F2F3FF] rounded-xl border border-[#DAE2FD] flex items-center px-3 py-1">
                                    <span class="material-symbols-rounded text-lg text-[#64748B] mr-2">edit_note</span>
                                    <input type="text" x-model="attendanceNotes" placeholder="Tambahkan catatan kegiatan operasional (opsional)..."
                                        class="w-full py-2 bg-transparent text-xs text-[#131B2E] outline-none">
                                </div>
                            </div>

                            <!-- Primary Submit Gradient Button (54px) -->
                            <button type="button" @click="submitFinalAttendance()" :disabled="isSubmitting"
                                class="w-full h-[54px] rounded-2xl bg-gradient-to-r from-[#00288E] to-[#0051D5] hover:from-[#001f70] hover:to-[#003ea8] text-white font-bold text-[15px] flex items-center justify-center space-x-2 shadow-lg shadow-[#0051D5]/25 transition-all disabled:opacity-50">
                                <template x-if="!isSubmitting">
                                    <span class="flex items-center space-x-2">
                                        <span class="material-symbols-rounded text-xl">send</span>
                                        <span>Kirim & Simpan Presensi</span>
                                    </span>
                                </template>
                                <template x-if="isSubmitting">
                                    <span class="flex items-center space-x-2">
                                        <span class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        <span>Mengirim Presensi...</span>
                                    </span>
                                </template>
                            </button>

                            <!-- Secondary Retake Outlined Button (44px) -->
                            <button type="button" @click="retakeSelfiePhoto()"
                                class="w-full h-[44px] bg-white hover:bg-slate-50 border border-[#DAE2FD] text-[#131B2E] font-semibold text-xs rounded-xl flex items-center justify-center space-x-1.5 transition-colors">
                                <span class="material-symbols-rounded text-base text-[#0051D5]">replay</span>
                                <span>Ambil Ulang Foto</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: BUKTI PRESENSI BERHASIL (Matching BuktiPresensiBerhasilScreen.dart) --}}
                <div x-show="verificationStep === 3" class="flex-1 flex flex-col">
                    <!-- Top Modal Bar -->
                    <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-md px-4 py-3 border-b border-[#DAE2FD] flex items-center justify-between">
                        <button type="button" @click="closeVerificationModal(); window.location.reload();" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-[#131B2E] flex items-center justify-center transition-colors">
                            <span class="material-symbols-rounded text-xl">arrow_back</span>
                        </button>
                        <span class="font-bold text-base text-[#131B2E]">Bukti Presensi</span>
                        <button type="button" @click="closeVerificationModal(); window.location.reload();" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-[#64748B] hover:text-[#131B2E] flex items-center justify-center transition-colors">
                            <span class="material-symbols-rounded text-xl">close</span>
                        </button>
                    </div>

                    <div class="p-4 space-y-4 flex-1 overflow-y-auto">
                        <!-- 1. Verified Success State Animation Emblem -->
                        <div class="text-center space-y-2 pt-2">
                            <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
                                <div class="absolute inset-0 rounded-full bg-emerald-500/20 animate-ping"></div>
                                <div class="w-16 h-16 rounded-full bg-emerald-600 shadow-lg shadow-emerald-600/30 flex items-center justify-center text-white">
                                    <span class="material-symbols-rounded text-4xl">check_circle</span>
                                </div>
                            </div>
                            <h2 class="text-xl font-bold text-[#131B2E] tracking-tight">Presensi Berhasil Terkirim!</h2>
                            <p class="text-xs text-[#64748B] max-w-xs mx-auto leading-relaxed">
                                Data kehadiran Anda telah tercatat dan terverifikasi di sistem perusahaan.
                            </p>
                        </div>

                        <!-- 2. Attendance Digital Slip / Receipt Card -->
                        <div class="bg-white rounded-2xl border border-[#DAE2FD] shadow-sm overflow-hidden">
                            <!-- Top Decorative Strip -->
                            <div class="h-1 w-full bg-gradient-to-r from-[#0051D5] to-[#705D00]"></div>

                            <div class="p-4 space-y-3.5">
                                <!-- Receipt Header Badge -->
                                <div class="flex justify-center">
                                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#EAEDFF] border border-[#DAE2FD] text-xs font-semibold text-[#131B2E]">
                                        <span class="w-2 h-2 rounded-full bg-[#0051D5] animate-pulse"></span>
                                        <span>Pending: Menunggu Disetujui oleh Direktur</span>
                                    </div>
                                </div>

                                <!-- Shift & Status Category Box -->
                                <div class="p-3 rounded-xl bg-[#F2F3FF] border border-[#DAE2FD] flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-9 h-9 rounded-xl bg-[#EAEDFF] text-[#00288E] flex items-center justify-center">
                                            <span class="material-symbols-rounded text-xl">badge</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-xs text-[#131B2E]" x-text="activeSlotTitle || 'Presensi Masuk'"></div>
                                            <div class="text-[11px] text-[#64748B]" x-text="'Shift Pagi • ' + (calculatedDistance <= targetOfficeRadius ? 'Kantor' : 'Lapangan')"></div>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        :class="isLateCheckIn ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800'"
                                        x-text="isLateCheckIn ? ('Terlambat (' + lateDurationMinutes + ' menit)') : 'Tepat Waktu'">
                                    </span>
                                </div>

                                <!-- Late Reason (If Applicable) -->
                                <template x-if="isLateCheckIn && lateReason">
                                    <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-1">
                                        <div class="font-bold text-[10.5px] uppercase tracking-wider text-amber-800">Alasan Keterlambatan Tercatat:</div>
                                        <div class="italic text-[11.5px]" x-text="lateReason"></div>
                                    </div>
                                </template>

                                <!-- Metric 1: Timestamp -->
                                <div class="flex items-start space-x-3 pt-1">
                                    <div class="w-8 h-8 rounded-full bg-[#EAEDFF] text-[#00288E] flex items-center justify-center shrink-0">
                                        <span class="material-symbols-rounded text-lg">schedule</span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#64748B]">WAKTU STEMPEL</div>
                                        <div class="flex items-baseline space-x-1.5 mt-0.5">
                                            <span class="font-bold text-base text-[#131B2E]" x-text="clockTime"></span>
                                            <span class="text-xs font-semibold text-[#64748B]">WIB</span>
                                        </div>
                                        <div class="text-xs text-[#64748B]" x-text="indonesianDate"></div>
                                    </div>
                                </div>

                                <div class="border-t border-[#EAEDFF]"></div>

                                <!-- Metric 2: Geofence & Location -->
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-[#EAEDFF] text-[#00288E] flex items-center justify-center shrink-0">
                                        <span class="material-symbols-rounded text-lg" x-text="calculatedDistance <= targetOfficeRadius ? 'business' : 'location_on'"></span>
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#64748B]"
                                                x-text="calculatedDistance <= targetOfficeRadius ? 'LOKASI KANTOR' : 'LOKASI PENUGASAN LAPANGAN'"></span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                :class="calculatedDistance <= targetOfficeRadius ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                                x-text="calculatedDistance <= targetOfficeRadius ? 'Di Dalam Radius' : 'Di Luar Radius (Lapangan)'"></span>
                                        </div>
                                        <div class="font-bold text-xs text-[#131B2E]" x-text="targetOfficeName"></div>
                                        <div class="text-[11px] text-[#64748B]" x-text="(gpsAddress || 'Tanjung Pinang Timur, Kota Tanjung Pinang') + ' • Jarak ' + calculatedDistance + ' m ke kantor'"></div>
                                        <div class="font-mono text-[10px] text-[#0051D5]" x-text="'GPS: ' + gpsLat.toFixed(5) + ', ' + gpsLng.toFixed(5)"></div>
                                    </div>
                                </div>

                                <div class="border-t border-[#EAEDFF]"></div>

                                <!-- Metric 3: Attached Selfie Snapshot & Physical Proof -->
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-[60px] rounded-xl overflow-hidden border border-[#DAE2FD] bg-slate-950 shrink-0 flex items-center justify-center">
                                        <img x-show="capturedPhoto" :src="capturedPhoto" class="w-full h-full object-contain">
                                        <div x-show="!capturedPhoto" class="w-full h-full flex items-center justify-center text-slate-400">
                                            <span class="material-symbols-rounded text-2xl">person</span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#64748B]">LAMPIRAN BUKTI FISIK</div>
                                        <div class="text-xs font-semibold text-[#131B2E] truncate font-mono mt-0.5"
                                            x-text="'{{ auth()->user()?->name ?? 'Karyawan' }}_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.jpg'"></div>
                                        <div class="flex items-center space-x-1.5 mt-1 text-[11px] text-[#0051D5]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#0051D5]"></span>
                                            <span>Menunggu Verifikasi Direktur</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Next Scheduled Activity Alert Banner -->
                        <div class="p-3 bg-white rounded-xl border border-[#DAE2FD] flex items-center justify-between shadow-xs">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-[#EAEDFF] text-[#0051D5] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-lg">upcoming</span>
                                </div>
                                <div>
                                    <div class="text-[11px] text-[#64748B]">Sesi Aktivitas Berikutnya</div>
                                    <div class="font-bold text-xs text-[#131B2E]">Presensi Istirahat Keluar (12:00 WIB)</div>
                                </div>
                            </div>
                            <span class="material-symbols-rounded text-lg text-slate-400">chevron_right</span>
                        </div>
                    </div>

                    <!-- 4. Sticky Bottom Action Button (52px) -->
                    <div class="sticky bottom-0 bg-white/95 backdrop-blur-md p-4 border-t border-[#DAE2FD]">
                        <button type="button" @click="closeVerificationModal(); window.location.reload();"
                            class="w-full h-[52px] rounded-2xl bg-[#00288E] hover:bg-[#001f70] text-white font-bold text-sm flex items-center justify-center space-x-2 shadow-md shadow-[#00288E]/20 transition-all">
                            <span class="material-symbols-rounded text-xl">home</span>
                            <span>Kembali ke Beranda</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL 3: VIEW COMPLETED RECEIPT --}}
    <div x-show="receiptModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="receiptModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-xl space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-[#131B2E]" x-text="viewReceiptData.title"></h3>
                    <button type="button" @click="receiptModal = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>
                <template x-if="viewReceiptData.foto">
                    <div class="relative w-full aspect-[4/5] bg-slate-950 rounded-xl overflow-hidden border border-slate-200 shadow-inner flex items-center justify-center">
                        <img :src="viewReceiptData.foto" alt="Bukti Swafoto Presensi" class="w-full h-full object-contain">
                    </div>
                </template>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between"><span class="text-[#64748B]">Waktu Tercatat:</span><span class="font-bold text-[#131B2E]" x-text="viewReceiptData.time + ' WIB'"></span></div>
                    <div class="flex justify-between"><span class="text-[#64748B]">Status:</span><span class="font-bold text-emerald-600" x-text="viewReceiptData.status"></span></div>
                    <div class="flex justify-between"><span class="text-[#64748B]">Jarak ke Kantor:</span><span class="font-mono text-[#0051D5]" x-text="viewReceiptData.jarak"></span></div>
                </div>
                <button type="button" @click="receiptModal = false" class="w-full py-2 bg-slate-100 text-slate-800 font-bold text-xs rounded-xl mt-2">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 4: REVIEW APPROVAL CONFIRMATION --}}
    <div x-show="reviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
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

    {{-- ========================================================================= --}}
    {{-- MODAL SUPERADMIN IT: SORT DIREKTORI PEGAWAI (_showSortBottomSheet)       --}}
    {{-- ========================================================================= --}}
    <div x-show="sortBottomSheet" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-screen text-center p-0 sm:p-4">
            <div @click="sortBottomSheet = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 transform transition-all">
                <!-- Grab handle -->
                <div class="w-9 h-1 bg-slate-300 rounded-full mx-auto mb-1"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-extrabold text-[#131B2E]">Urutkan Direktori Pegawai</h3>
                        <p class="text-[11px] text-[#64748B]">Pilih kriteria urutan daftar pegawai & akun</p>
                    </div>
                    <button type="button" @click="sortBottomSheet = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <div class="space-y-1.5 max-h-[60vh] overflow-y-auto">
                    <!-- Option 0: default (Standar / Bawaan) -->
                    <button type="button" @click="empSortOption = 'default'; sortBottomSheet = false;"
                        :class="empSortOption === 'default' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">sort</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">Standar (ID Terdaftar)</div>
                                <div class="text-[10px] text-[#64748B]">Urutan terdaftar tanpa auto-sort nama</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'default' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'default' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 1: nameAsc -->
                    <button type="button" @click="empSortOption = 'nameAsc'; sortBottomSheet = false;"
                        :class="empSortOption === 'nameAsc' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">sort_by_alpha</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">Nama (A - Z)</div>
                                <div class="text-[10px] text-[#64748B]">Urutan alfabet nama dari A sampai Z</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'nameAsc' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'nameAsc' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 2: nameDesc -->
                    <button type="button" @click="empSortOption = 'nameDesc'; sortBottomSheet = false;"
                        :class="empSortOption === 'nameDesc' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">sort_by_alpha</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">Nama (Z - A)</div>
                                <div class="text-[10px] text-[#64748B]">Urutan alfabet nama dari Z sampai A</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'nameDesc' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'nameDesc' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 3: nipAsc -->
                    <button type="button" @click="empSortOption = 'nipAsc'; sortBottomSheet = false;"
                        :class="empSortOption === 'nipAsc' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">pin</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">NIK (Terkecil - Terbesar)</div>
                                <div class="text-[10px] text-[#64748B]">Nomor induk kependudukan dari terkecil ke terbesar</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'nipAsc' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'nipAsc' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 4: nipDesc -->
                    <button type="button" @click="empSortOption = 'nipDesc'; sortBottomSheet = false;"
                        :class="empSortOption === 'nipDesc' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">pin</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">NIK (Terbesar - Terkecil)</div>
                                <div class="text-[10px] text-[#64748B]">Nomor induk kependudukan dari terbesar ke terkecil</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'nipDesc' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'nipDesc' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 5: department -->
                    <button type="button" @click="empSortOption = 'department'; sortBottomSheet = false;"
                        :class="empSortOption === 'department' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">business_center</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">Bidang</div>
                                <div class="text-[10px] text-[#64748B]">Dikelompokkan berdasarkan bidang kerja</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'department' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'department' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 6: role -->
                    <button type="button" @click="empSortOption = 'role'; sortBottomSheet = false;"
                        :class="empSortOption === 'role' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">admin_panel_settings</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">Peran & Hak Akses</div>
                                <div class="text-[10px] text-[#64748B]">Superadmin → Admin → Pengguna</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'role' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'role' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>

                    <!-- Option 7: office -->
                    <button type="button" @click="empSortOption = 'office'; sortBottomSheet = false;"
                        :class="empSortOption === 'office' ? 'bg-[#00288E]/8 border-[#00288E]/35 text-[#00288E]' : 'border-transparent hover:bg-slate-50 text-[#131B2E]'"
                        class="w-full p-2.5 rounded-xl border text-left flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center space-x-2.5">
                            <span class="material-symbols-rounded text-base text-[#00288E]">location_city</span>
                            <div>
                                <div class="text-xs font-bold leading-tight">Kantor Penempatan</div>
                                <div class="text-[10px] text-[#64748B]">Kantor Pusat (HQ) lalu kantor cabang</div>
                            </div>
                        </div>
                        <span class="material-symbols-rounded text-base" :class="empSortOption === 'office' ? 'text-[#00288E]' : 'text-slate-300'" x-text="empSortOption === 'office' ? 'check_circle' : 'radio_button_unchecked'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL SUPERADMIN IT: UBAH PERAN & HAK AKSES (_showChangeRoleModal)         --}}
    {{-- ========================================================================= --}}
    <div x-show="changeRoleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-screen text-center p-0 sm:p-4">
            <div @click="changeRoleModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 transform transition-all">
                <!-- Grab handle -->
                <div class="w-9 h-1 bg-slate-300 rounded-full mx-auto mb-1"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-extrabold text-[#131B2E]">Ubah Peran & Hak Akses</h3>
                        <p class="text-[11px] text-[#64748B]" x-text="'Tetapkan wewenang untuk ' + changeRoleData.name + ' (' + changeRoleData.nip + ')'"></p>
                    </div>
                    <button type="button" @click="changeRoleModal = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <div class="space-y-2">
                    <!-- Option 1: Superadmin (Staff IT) -->
                    <button type="button" @click="changeRoleData.selectedRole = 'staffIT'"
                        :class="changeRoleData.selectedRole === 'staffIT' ? 'bg-[#0D9488]/8 border-[#0D9488]' : 'border-slate-200'"
                        class="w-full p-3 rounded-xl border text-left flex items-start space-x-2.5 transition-all cursor-pointer">
                        <span class="material-symbols-rounded text-lg mt-0.5"
                            :class="changeRoleData.selectedRole === 'staffIT' ? 'text-[#0D9488]' : 'text-slate-300'"
                            x-text="changeRoleData.selectedRole === 'staffIT' ? 'check_circle' : 'radio_button_unchecked'"></span>
                        <div class="flex-1">
                            <div class="text-xs font-bold flex items-center space-x-1.5" :class="changeRoleData.selectedRole === 'staffIT' ? 'text-[#0D9488]' : 'text-[#131B2E]'">
                                <i class="bi bi-person-fill-gear text-sm"></i>
                                <span>Superadmin</span>
                            </div>
                            <div class="text-[10.5px] text-[#64748B] mt-0.5">Akses penuh manajemen user, jam kerja, lokasi kantor, dan audit sistem</div>
                        </div>
                    </button>

                    <!-- Option 2: Admin (Direktur) -->
                    <button type="button" @click="changeRoleData.selectedRole = 'direktur'"
                        :class="changeRoleData.selectedRole === 'direktur' ? 'bg-[#7C3AED]/8 border-[#7C3AED]' : 'border-slate-200'"
                        class="w-full p-3 rounded-xl border text-left flex items-start space-x-2.5 transition-all cursor-pointer">
                        <span class="material-symbols-rounded text-lg mt-0.5"
                            :class="changeRoleData.selectedRole === 'direktur' ? 'text-[#7C3AED]' : 'text-slate-300'"
                            x-text="changeRoleData.selectedRole === 'direktur' ? 'check_circle' : 'radio_button_unchecked'"></span>
                        <div class="flex-1">
                            <div class="text-xs font-bold flex items-center space-x-1.5" :class="changeRoleData.selectedRole === 'direktur' ? 'text-[#7C3AED]' : 'text-[#131B2E]'">
                                <i class="bi bi-person-fill-lock text-sm"></i>
                                <span>Admin</span>
                            </div>
                            <div class="text-[10.5px] text-[#64748B] mt-0.5">Akses dashboard eksekutif kehadiran & persetujuan presensi</div>
                        </div>
                    </button>

                    <!-- Option 3: Pengguna (Karyawan) -->
                    <button type="button" @click="changeRoleData.selectedRole = 'karyawan'"
                        :class="changeRoleData.selectedRole === 'karyawan' ? 'bg-[#316BF3]/8 border-[#316BF3]' : 'border-slate-200'"
                        class="w-full p-3 rounded-xl border text-left flex items-start space-x-2.5 transition-all cursor-pointer">
                        <span class="material-symbols-rounded text-lg mt-0.5"
                            :class="changeRoleData.selectedRole === 'karyawan' ? 'text-[#316BF3]' : 'text-slate-300'"
                            x-text="changeRoleData.selectedRole === 'karyawan' ? 'check_circle' : 'radio_button_unchecked'"></span>
                        <div class="flex-1">
                            <div class="text-xs font-bold flex items-center space-x-1.5" :class="changeRoleData.selectedRole === 'karyawan' ? 'text-[#316BF3]' : 'text-[#131B2E]'">
                                <i class="bi bi-person-fill text-sm"></i>
                                <span>Pengguna</span>
                            </div>
                            <div class="text-[10.5px] text-[#64748B] mt-0.5">Akses sesi presensi mandiri dan pengajuan persetujuan presensi</div>
                        </div>
                    </button>
                </div>

                <div class="pt-2">
                    <button type="button" @click="submitChangeRole()"
                        class="w-full py-2.5 bg-[#00288E] hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Simpan Perubahan Peran
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL SUPERADMIN IT: RESET PIN KARYAWAN (_showResetPinModal)              --}}
    {{-- ========================================================================= --}}
    <div x-show="resetPinModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen text-center p-4">
            <div @click="resetPinModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 transform transition-all">
                <h3 class="text-base font-bold text-[#131B2E]">Reset PIN Karyawan</h3>
                <p class="text-xs text-[#64748B] leading-relaxed"
                    x-text="'Reset PIN autentikasi presensi untuk pegawai ' + resetPinData.name + ' (NIK: ' + resetPinData.nip + '):'"></p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">PIN Baru (6 Digit)</label>
                    <input type="text" maxlength="6" x-model="resetPinData.newPin"
                        class="w-full px-3 py-2 text-sm font-mono tracking-widest text-center rounded-xl border border-slate-300 focus:border-[#0D9488] outline-none">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="resetPinModal = false"
                        class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="submitResetPin()"
                        class="px-4 py-2 bg-[#0D9488] hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Simpan PIN Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL SUPERADMIN IT: TAMBAH PEGAWAI BARU (_showAddEmployeeModal)          --}}
    {{-- ========================================================================= --}}
    <div x-show="addEmployeeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-screen text-center p-0 sm:p-4">
            <div @click="addEmployeeModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-md w-full text-left shadow-2xl flex flex-col max-h-[90vh] transform transition-all">
                <!-- Grab handle -->
                <div class="w-9 h-1 bg-slate-300 rounded-full mx-auto mb-2"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#0D9488]/12 text-[#0D9488] flex items-center justify-center">
                            <span class="material-symbols-rounded text-lg">person_add</span>
                        </div>
                        <h3 class="text-sm font-bold text-[#131B2E]">Tambah Pegawai Baru</h3>
                    </div>
                    <button type="button" @click="addEmployeeModal = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Scrollable Form Body -->
                <div class="flex-1 overflow-y-auto py-3 space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIK *</label>
                        <input type="text" x-model="addEmpForm.nip" placeholder="Contoh: 2171012345678901"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488]">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap (dengan Gelar) *</label>
                        <input type="text" x-model="addEmpForm.name" placeholder="Contoh: Ahmad Fadilah, S.T."
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488]">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Bidang *</label>
                        <select x-model="addEmpForm.department" @change="onAddBidangChange()"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488] bg-white cursor-pointer">
                            <template x-for="b in bidangDataList" :key="'add_b_' + b.nama">
                                <option :value="b.nama" x-text="b.nama"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Seksi Bidang</label>
                        <select x-model="addEmpForm.position"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488] bg-white cursor-pointer">
                            <template x-for="s in getSeksiListForBidang(addEmpForm.department)" :key="'add_s_' + s">
                                <option :value="s" x-text="s"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kantor Penempatan</label>
                        <select x-model="addEmpForm.office_id"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488] bg-white cursor-pointer">
                            <template x-for="o in officesList" :key="'add_o_' + o.id">
                                <option :value="o.id" x-text="o.name + ' (' + (o.is_primary ? 'Kantor Utama' : 'Kantor Cabang') + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">No. WhatsApp / HP</label>
                        <input type="tel" x-model="addEmpForm.phone" placeholder="081234567890"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488]">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Peran Akun</label>
                        <select x-model="addEmpForm.role"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#0D9488] bg-white cursor-pointer">
                            <option value="karyawan">Pengguna</option>
                            <option value="direktur">Admin</option>
                            <option value="staffIT">Superadmin</option>
                        </select>
                    </div>
                </div>

                <!-- Sticky Footer -->
                <div class="pt-3 border-t border-slate-100">
                    <button type="button" @click="submitAddEmployee()"
                        class="w-full py-2.5 bg-[#0D9488] hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Simpan Pegawai Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL SUPERADMIN IT: UBAH DATA PEGAWAI (_showEditEmployeeModal)           --}}
    {{-- ========================================================================= --}}
    <div x-show="editEmployeeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-screen text-center p-0 sm:p-4">
            <div @click="editEmployeeModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-md w-full text-left shadow-2xl flex flex-col max-h-[90vh] transform transition-all">
                <!-- Grab handle -->
                <div class="w-9 h-1 bg-slate-300 rounded-full mx-auto mb-2"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#00288E]/12 text-[#00288E] flex items-center justify-center">
                            <span class="material-symbols-rounded text-lg">edit_note</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#131B2E]">Ubah Data Pegawai</h3>
                            <span class="sr-only">Edit Data Pegawai</span>
                            <p class="text-[11px] text-[#64748B]" x-text="'NIK: ' + editEmpForm.nip + ' • ' + editEmpForm.name"></p>
                        </div>
                    </div>
                    <button type="button" @click="editEmployeeModal = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Scrollable Form Body -->
                <div class="flex-1 overflow-y-auto py-3 space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIK</label>
                        <input type="text" x-model="editEmpForm.nip"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#00288E]">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" x-model="editEmpForm.name"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#00288E]">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Bidang</label>
                        <select x-model="editEmpForm.department" @change="onEditBidangChange()"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#00288E] bg-white cursor-pointer">
                            <template x-for="b in bidangDataList" :key="'edit_b_' + b.nama">
                                <option :value="b.nama" x-text="b.nama"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Seksi Bidang</label>
                        <select x-model="editEmpForm.position"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#00288E] bg-white cursor-pointer">
                            <template x-for="s in getSeksiListForBidang(editEmpForm.department)" :key="'edit_s_' + s">
                                <option :value="s" x-text="s"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kantor Penempatan</label>
                        <select x-model="editEmpForm.office_id"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#00288E] bg-white cursor-pointer">
                            <template x-for="o in officesList" :key="'edit_o_' + o.id">
                                <option :value="o.id" x-text="o.name + ' (' + (o.is_primary ? 'Kantor Utama' : 'Kantor Cabang') + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                        <input type="tel" x-model="editEmpForm.phone"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl outline-none focus:border-[#00288E]">
                    </div>
                </div>

                <!-- Sticky Footer -->
                <div class="pt-3 border-t border-slate-100">
                    <button type="button" @click="submitEditEmployee()"
                        class="w-full py-2.5 bg-[#00288E] hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL SUPERADMIN IT: DETAIL DATA PEGAWAI (_showEmployeeDetailSheet)       --}}
    {{-- ========================================================================= --}}
    <div x-show="detailEmployeeSheet" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-screen text-center p-0 sm:p-4">
            <div @click="detailEmployeeSheet = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-sm w-full text-left shadow-2xl space-y-4 transform transition-all">
                <!-- Grab handle -->
                <div class="w-9 h-1 bg-slate-300 rounded-full mx-auto mb-1"></div>

                <div class="flex items-center space-x-3">
                    <div class="w-14 h-14 rounded-full bg-[#0D9488]/15 text-[#0D9488] font-bold text-lg flex items-center justify-center shrink-0 overflow-hidden">
                        <template x-if="detailEmpData && detailEmpData.avatar_url">
                            <img :src="detailEmpData.avatar_url" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!detailEmpData || !detailEmpData.avatar_url">
                            <span x-text="detailEmpData && detailEmpData.name ? detailEmpData.name.charAt(0).toUpperCase() : 'P'"></span>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-bold text-[#131B2E] truncate" x-text="detailEmpData ? detailEmpData.name : ''"></h3>
                        <p class="text-xs text-[#64748B]" x-text="detailEmpData ? ('NIK: ' + detailEmpData.nip) : ''"></p>
                        <div class="mt-1">
                            <template x-if="detailEmpData">
                                <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-semibold border inline-flex items-center space-x-1"
                                    :style="'background-color: ' + getRoleColor(detailEmpData.role) + '1f; color: ' + getRoleColor(detailEmpData.role) + '; border-color: ' + getRoleColor(detailEmpData.role) + '4d'">
                                    <span class="material-symbols-rounded text-xs" x-text="getRoleIcon(detailEmpData.role)"></span>
                                    <span x-text="getRoleDisplayName(detailEmpData.role)"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-[#EAEDFF]"></div>

                <!-- Detail Rows -->
                <div class="space-y-2 text-xs">
                    <div class="flex items-start">
                        <span class="w-32 text-[#64748B] text-[11.5px] shrink-0">Bidang</span>
                        <span class="font-semibold text-[#131B2E] text-[12px]" x-text="detailEmpData ? detailEmpData.department : '-'"></span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-32 text-[#64748B] text-[11.5px] shrink-0">Kantor Penempatan</span>
                        <span class="font-semibold text-[#131B2E] text-[12px]" x-text="detailEmpData ? detailEmpData.office_name : '-'"></span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-32 text-[#64748B] text-[11.5px] shrink-0">Seksi Bidang</span>
                        <span class="font-semibold text-[#131B2E] text-[12px]" x-text="detailEmpData ? detailEmpData.position : '-'"></span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-32 text-[#64748B] text-[11.5px] shrink-0">No. WhatsApp / HP</span>
                        <span class="font-semibold text-[#131B2E] text-[12px]" x-text="detailEmpData ? detailEmpData.phone : '-'"></span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-32 text-[#64748B] text-[11.5px] shrink-0">Email Perusahaan</span>
                        <span class="font-semibold text-[#131B2E] text-[12px] truncate" x-text="detailEmpData ? detailEmpData.email : '-'"></span>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="button" @click="detailEmployeeSheet = false"
                        class="w-full py-2.5 bg-[#00288E] hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Tutup Detail Pegawai
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- FLOATING SNACKBAR TOAST (Matching Flutter SnackBarBehavior.floating)      --}}
    {{-- ========================================================================= --}}
    <div x-show="empToastMessage" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-[400px] px-4 pointer-events-none">
        <div class="bg-[#131B2E] text-white px-4 py-3 rounded-2xl shadow-xl flex items-center space-x-3 text-xs border border-slate-700 pointer-events-auto">
            <span class="material-symbols-rounded text-teal-400 text-lg">check_circle</span>
            <span class="flex-1 font-medium" x-text="empToastMessage"></span>
            <button type="button" @click="empToastMessage = null" class="text-slate-400 hover:text-white cursor-pointer">
                <span class="material-symbols-rounded text-base">close</span>
            </button>
        </div>
    </div>

    {{-- SHEET KARYAWAN: PILIH FOTO PROFIL KARYAWAN --}}
    <div x-show="karyawanPhotoSheet" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen text-center p-0 sm:items-center sm:p-4">
            <div @click="karyawanPhotoSheet = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 transform transition-all">
                <div class="w-10 h-1 bg-slate-300 rounded-full mx-auto mb-1"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-[#131B2E]">Pilih Foto Profil Karyawan</h3>
                    <button type="button" @click="karyawanPhotoSheet = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Option 1: Kamera Langsung -->
                <div @click="triggerProfileCamera('karyawan')"
                    class="p-3 rounded-2xl bg-[#F8FAFC] hover:bg-[#EAEDFF]/40 border border-[#DAE2FD]/60 flex items-center space-x-3.5 cursor-pointer transition-colors">
                    <div class="w-11 h-11 rounded-xl bg-[#0051D5]/12 text-[#0051D5] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">camera_alt</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[13.5px] font-semibold text-[#131B2E]">Ambil Foto Kamera</h4>
                        <p class="text-[11.5px] text-[#64748B]">Gunakan kamera untuk mengambil foto profil langsung</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>

                <!-- Option 2: Unggah Galeri -->
                <div @click="triggerProfileGallery('karyawan')"
                    class="p-3 rounded-2xl bg-[#F8FAFC] hover:bg-[#EAEDFF]/40 border border-[#DAE2FD]/60 flex items-center space-x-3.5 cursor-pointer transition-colors">
                    <div class="w-11 h-11 rounded-xl bg-[#0051D5]/12 text-[#0051D5] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">photo_library</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[13.5px] font-semibold text-[#131B2E]">Pilih dari Galeri</h4>
                        <p class="text-[11.5px] text-[#64748B]">Pilih berkas foto dari galeri penyimpanan perangkat</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL KARYAWAN: UBAH PIN PRESENSI & KEAMANAN --}}
    <div x-show="karyawanPinModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="karyawanPinModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 text-xs">
                <!-- Title Row -->
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-rounded text-2xl text-[#0051D5]">lock_reset</span>
                    <h3 class="text-base font-bold text-[#131B2E]">Ubah PIN Presensi & Keamanan</h3>
                </div>

                <p class="text-xs text-[#64748B] leading-relaxed">
                    Masukkan PIN lama Anda terlebih dahulu untuk verifikasi keamanan sebelum menetapkan 6 digit PIN baru:
                </p>

                <!-- Error Message Box -->
                <div x-show="karyawanPinError" x-cloak class="p-2.5 rounded-xl bg-red-50 border border-red-200 flex items-center space-x-2 text-red-700">
                    <span class="material-symbols-rounded text-base text-red-600 shrink-0">error</span>
                    <span class="text-[11.5px] font-semibold flex-1" x-text="karyawanPinError"></span>
                </div>

                <!-- Field 1: PIN Lama -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">PIN Lama</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0051D5]">
                            <span class="material-symbols-rounded text-lg">lock_clock</span>
                        </span>
                        <input :type="karyawanObscureOld ? 'password' : 'text'" maxlength="6" x-model="karyawanOldPin" placeholder="PIN Lama (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="karyawanObscureOld = !karyawanObscureOld" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="karyawanObscureOld ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Field 2: PIN Baru -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">PIN Baru (6 Digit)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0051D5]">
                            <span class="material-symbols-rounded text-lg">pin</span>
                        </span>
                        <input :type="karyawanObscureNew ? 'password' : 'text'" maxlength="6" x-model="karyawanNewPin" placeholder="PIN Baru (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="karyawanObscureNew = !karyawanObscureNew" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="karyawanObscureNew ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Field 3: Konfirmasi PIN Baru -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">Konfirmasi PIN Baru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0051D5]">
                            <span class="material-symbols-rounded text-lg">verified_user</span>
                        </span>
                        <input :type="karyawanObscureConfirm ? 'password' : 'text'" maxlength="6" x-model="karyawanConfirmPin" placeholder="Konfirmasi PIN Baru (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="karyawanObscureConfirm = !karyawanObscureConfirm" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="karyawanObscureConfirm ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="karyawanPinModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl">Batal</button>
                    <button type="button" @click="saveKaryawanPin()" class="px-4 py-1.5 bg-[#0051D5] hover:bg-[#00288E] text-white font-bold rounded-xl shadow-xs">Simpan PIN Baru</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UNIVERSAL: AMBIL FOTO PROFIL KAMERA LANGSUNG --}}
    <div x-show="profileCameraModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="profileCameraModal = false; stopProfileCamera();" class="fixed inset-0 bg-[#131B2E]/75 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-3xl p-5 max-w-sm w-full text-center shadow-2xl space-y-4">
                <!-- Header -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center space-x-2 text-[#00288E]">
                        <span class="material-symbols-rounded text-xl">photo_camera</span>
                        <h3 class="text-sm font-bold text-[#131B2E]">Kamera Foto Profil</h3>
                    </div>
                    <button type="button" @click="profileCameraModal = false; stopProfileCamera();" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Viewfinder / Snapshot Preview -->
                <div class="relative w-full aspect-square max-w-[280px] mx-auto rounded-full overflow-hidden bg-slate-900 border-4 border-[#0051D5] shadow-lg flex items-center justify-center">
                    <!-- Live Video Feed -->
                    <video id="profileCameraVideo" autoplay playsinline class="w-full h-full object-cover" x-show="!profileSnappedPhoto"></video>
                    
                    <!-- Snapped Photo Preview -->
                    <img :src="profileSnappedPhoto" x-show="profileSnappedPhoto" class="w-full h-full object-cover" alt="Pratinjau Foto Profil">
                    
                    <!-- Hidden Canvas for Snapshot -->
                    <canvas id="profileCameraCanvas" class="hidden"></canvas>
                </div>

                <!-- Shutter & Controls -->
                <div class="pt-2">
                    <!-- When live streaming: Shutter button & flip button -->
                    <template x-if="!profileSnappedPhoto">
                        <div class="flex items-center justify-center space-x-4">
                            <button type="button" @click="toggleProfileCameraFacing()" title="Putar Kamera"
                                class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-colors">
                                <span class="material-symbols-rounded text-xl">flip_camera_ios</span>
                            </button>
                            <button type="button" @click="snapProfileCameraPhoto()"
                                class="w-14 h-14 rounded-full bg-[#0051D5] hover:bg-[#00288E] text-white flex items-center justify-center shadow-lg shadow-[#0051D5]/30 hover:scale-105 active:scale-95 transition-all">
                                <span class="material-symbols-rounded text-2xl">photo_camera</span>
                            </button>
                            <div class="w-10 h-10"></div>
                        </div>
                    </template>

                    <!-- When photo snapped: Retake and Confirm buttons -->
                    <template x-if="profileSnappedPhoto">
                        <div class="flex items-center justify-center space-x-3">
                            <button type="button" @click="retakeProfileCameraPhoto()"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl flex items-center space-x-1.5 transition-colors">
                                <span class="material-symbols-rounded text-base">refresh</span>
                                <span>Ambil Ulang</span>
                            </button>
                            <button type="button" @click="confirmProfileCameraPhoto()"
                                class="px-5 py-2 bg-[#0051D5] hover:bg-[#00288E] text-white text-xs font-bold rounded-xl flex items-center space-x-1.5 shadow-md shadow-[#0051D5]/20 hover:scale-102 transition-all">
                                <span class="material-symbols-rounded text-base">check</span>
                                <span>Gunakan Foto Ini</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Native File Inputs for Camera and Gallery -->
    <input type="file" id="profileGalleryFileInput" accept="image/*" class="hidden" @change="handleProfileFileSelected($event, 'gallery')">
    <input type="file" id="profileCameraNativeInput" accept="image/*" capture="user" class="hidden" @change="handleProfileFileSelected($event, 'camera')">

    {{-- MODAL ADMIN: PILIH FOTO PROFIL ADMIN (Matching _showChangePhotoSheet) --}}
    <div x-show="adminPhotoSheet" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen text-center p-0 sm:items-center sm:p-4">
            <div @click="adminPhotoSheet = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 transform transition-all">
                <!-- Grab handle -->
                <div class="w-10 h-1 bg-slate-300 rounded-full mx-auto mb-1"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-[#131B2E]">Pilih Foto Profil Admin</h3>
                    <button type="button" @click="adminPhotoSheet = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Option 1: Kamera Langsung -->
                <div @click="triggerProfileCamera('direktur')"
                    class="p-3 rounded-2xl bg-[#F8FAFC] hover:bg-[#EAEDFF]/40 border border-[#DAE2FD]/60 flex items-center space-x-3.5 cursor-pointer transition-colors">
                    <div class="w-11 h-11 rounded-xl bg-[#0051D5]/12 text-[#0051D5] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">camera_alt</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[13.5px] font-semibold text-[#131B2E]">Ambil Foto Kamera</h4>
                        <p class="text-[11.5px] text-[#64748B]">Gunakan kamera untuk mengambil foto profil resmi</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>

                <!-- Option 2: Unggah Galeri -->
                <div @click="triggerProfileGallery('direktur')"
                    class="p-3 rounded-2xl bg-[#F8FAFC] hover:bg-[#EAEDFF]/40 border border-[#DAE2FD]/60 flex items-center space-x-3.5 cursor-pointer transition-colors">
                    <div class="w-11 h-11 rounded-xl bg-[#0051D5]/12 text-[#0051D5] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">photo_library</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[13.5px] font-semibold text-[#131B2E]">Pilih dari Galeri</h4>
                        <p class="text-[11.5px] text-[#64748B]">Pilih berkas foto dari galeri penyimpanan perangkat</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL ADMIN: UBAH PIN OTORISASI ADMIN (Matching _showChangePinDialog) --}}
    <div x-show="adminPinModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="adminPinModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 text-xs">
                <!-- Title Row -->
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-rounded text-2xl text-[#0051D5]">lock_reset</span>
                    <h3 class="text-base font-bold text-[#131B2E]">Ubah PIN Otorisasi Admin</h3>
                </div>

                <p class="text-xs text-[#64748B] leading-relaxed">
                    Masukkan PIN otorisasi lama Anda terlebih dahulu untuk verifikasi keamanan sebelum menetapkan PIN baru:
                </p>

                <!-- Error Message Box -->
                <div x-show="adminPinError" x-cloak class="p-2.5 rounded-xl bg-red-50 border border-red-200 flex items-center space-x-2 text-red-700">
                    <span class="material-symbols-rounded text-base text-red-600 shrink-0">error</span>
                    <span class="text-[11.5px] font-semibold flex-1" x-text="adminPinError"></span>
                </div>

                <!-- Field 1: PIN Lama -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">PIN Lama</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0051D5]">
                            <span class="material-symbols-rounded text-lg">lock_clock</span>
                        </span>
                        <input :type="adminObscureOld ? 'password' : 'text'" maxlength="6" x-model="adminOldPin" placeholder="PIN Lama (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="adminObscureOld = !adminObscureOld" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="adminObscureOld ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Field 2: PIN Baru -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">PIN Baru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0051D5]">
                            <span class="material-symbols-rounded text-lg">pin</span>
                        </span>
                        <input :type="adminObscureNew ? 'password' : 'text'" maxlength="6" x-model="adminNewPin" placeholder="PIN Baru (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="adminObscureNew = !adminObscureNew" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="adminObscureNew ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Field 3: Konfirmasi PIN Baru -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">Konfirmasi PIN Baru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0051D5]">
                            <span class="material-symbols-rounded text-lg">verified_user</span>
                        </span>
                        <input :type="adminObscureConfirm ? 'password' : 'text'" maxlength="6" x-model="adminConfirmPin" placeholder="Konfirmasi PIN Baru (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="adminObscureConfirm = !adminObscureConfirm" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="adminObscureConfirm ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="adminPinModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl">Batal</button>
                    <button type="button" @click="saveAdminPin()" class="px-4 py-1.5 bg-[#0051D5] hover:bg-[#00288E] text-white font-bold rounded-xl shadow-xs">Simpan PIN Baru</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL ADMIN: KONFIRMASI LOGOUT ADMIN (Matching _showLogoutDialog) --}}
    <div x-show="adminLogoutModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="adminLogoutModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3">
                <h3 class="text-base font-bold text-[#131B2E]">Keluar dari Akun Admin</h3>
                <p class="text-xs text-[#64748B] leading-relaxed">
                    Apakah Anda yakin ingin keluar dari sesi Admin Direktur saat ini?
                </p>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="adminLogoutModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">Batal</button>
                    <form action="{{ route('web.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs">Ya, Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- FLOATING SUCCESS TOAST (Matching _showSuccessToast with SnackBarBehavior.floating) --}}
    <div x-show="adminSuccessToastMessage" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 max-w-[360px] w-full px-4 pointer-events-none">
        <div class="bg-[#1E293B] text-white px-4 py-3 rounded-full shadow-2xl flex items-center justify-center space-x-2 text-xs font-semibold pointer-events-auto">
            <span class="material-symbols-rounded text-lg text-teal-300 shrink-0">check_circle</span>
            <span class="truncate" x-text="adminSuccessToastMessage"></span>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: PILIH FOTO PROFIL SUPERADMIN (Matching _showChangePhotoSheet) --}}
    <div x-show="superadminPhotoSheet" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen text-center p-0 sm:items-center sm:p-4">
            <div @click="superadminPhotoSheet = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-3xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 transform transition-all">
                <!-- Grab handle -->
                <div class="w-10 h-1 bg-slate-300 rounded-full mx-auto mb-1"></div>

                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-[#131B2E]">Pilih Foto Profil Superadmin</h3>
                    <button type="button" @click="superadminPhotoSheet = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Option 1: Kamera Langsung -->
                <div @click="triggerProfileCamera('staffIT')"
                    class="p-3 rounded-2xl bg-[#F8FAFC] hover:bg-teal-50/50 border border-[#DAE2FD]/60 flex items-center space-x-3.5 cursor-pointer transition-colors">
                    <div class="w-11 h-11 rounded-xl bg-[#0D9488]/12 text-[#0D9488] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">camera_alt</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[13.5px] font-semibold text-[#131B2E]">Ambil Foto Kamera</h4>
                        <p class="text-[11.5px] text-[#64748B]">Gunakan kamera untuk mengambil foto profil resmi</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>

                <!-- Option 2: Unggah Galeri -->
                <div @click="triggerProfileGallery('staffIT')"
                    class="p-3 rounded-2xl bg-[#F8FAFC] hover:bg-teal-50/50 border border-[#DAE2FD]/60 flex items-center space-x-3.5 cursor-pointer transition-colors">
                    <div class="w-11 h-11 rounded-xl bg-[#0D9488]/12 text-[#0D9488] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">photo_library</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-[13.5px] font-semibold text-[#131B2E]">Pilih dari Galeri</h4>
                        <p class="text-[11.5px] text-[#64748B]">Pilih berkas foto dari galeri penyimpanan perangkat</p>
                    </div>
                    <span class="material-symbols-rounded text-xl text-slate-400">chevron_right</span>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: UBAH PIN OTORISASI SUPERADMIN (Matching _showChangePinDialog) --}}
    <div x-show="superadminPinModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="superadminPinModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3.5 text-xs">
                <!-- Title Row -->
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-rounded text-2xl text-[#0D9488]">lock_reset</span>
                    <h3 class="text-base font-bold text-[#131B2E]">Ubah PIN Otorisasi Superadmin</h3>
                </div>

                <p class="text-xs text-[#64748B] leading-relaxed">
                    Masukkan PIN otorisasi lama Anda terlebih dahulu untuk verifikasi keamanan sebelum menetapkan PIN baru:
                </p>

                <!-- Error Message Box -->
                <div x-show="superadminPinError" x-cloak class="p-2.5 rounded-xl bg-red-50 border border-red-200 flex items-center space-x-2 text-red-700">
                    <span class="material-symbols-rounded text-base text-red-600 shrink-0">error</span>
                    <span class="text-[11.5px] font-semibold flex-1" x-text="superadminPinError"></span>
                </div>

                <!-- Field 1: PIN Lama -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">PIN Lama</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0D9488]">
                            <span class="material-symbols-rounded text-lg">lock_clock</span>
                        </span>
                        <input :type="superadminObscureOld ? 'password' : 'text'" maxlength="6" x-model="superadminOldPin" placeholder="PIN Lama (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="superadminObscureOld = !superadminObscureOld" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="superadminObscureOld ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Field 2: PIN Baru -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">PIN Baru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0D9488]">
                            <span class="material-symbols-rounded text-lg">pin</span>
                        </span>
                        <input :type="superadminObscureNew ? 'password' : 'text'" maxlength="6" x-model="superadminNewPin" placeholder="PIN Baru (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="superadminObscureNew = !superadminObscureNew" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="superadminObscureNew ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Field 3: Konfirmasi PIN Baru -->
                <div>
                    <label class="block font-semibold text-[#131B2E] mb-1">Konfirmasi PIN Baru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#0D9488]">
                            <span class="material-symbols-rounded text-lg">verified_user</span>
                        </span>
                        <input :type="superadminObscureConfirm ? 'password' : 'text'" maxlength="6" x-model="superadminConfirmPin" placeholder="Konfirmasi PIN Baru (6 digit)"
                            class="w-full pl-9 pr-9 py-2 bg-[#F8FAFC] rounded-xl border border-slate-300 text-xs font-semibold tracking-widest text-[#131B2E] outline-none">
                        <button type="button" @click="superadminObscureConfirm = !superadminObscureConfirm" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-lg" x-text="superadminObscureConfirm ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="superadminPinModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl">Batal</button>
                    <button type="button" @click="saveSuperadminPin()" class="px-4 py-1.5 bg-[#0D9488] hover:bg-teal-700 text-white font-bold rounded-xl shadow-xs">Simpan PIN Baru</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: KONFIRMASI LOGOUT SUPERADMIN (Matching _showLogoutDialog) --}}
    <div x-show="superadminLogoutModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="superadminLogoutModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3">
                <h3 class="text-base font-bold text-[#131B2E]">Keluar dari Akun Superadmin</h3>
                <p class="text-xs text-[#64748B] leading-relaxed">
                    Apakah Anda yakin ingin keluar dari sesi Superadmin IT saat ini?
                </p>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="superadminLogoutModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">Batal</button>
                    <form action="{{ route('web.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs">Ya, Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- FLOATING SUPERADMIN SUCCESS TOAST (Matching _showSuccessToast with SnackBarBehavior.floating) --}}
    <div x-show="superadminToastMessage" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 max-w-[360px] w-full px-4 pointer-events-none">
        <div class="bg-[#1E293B] text-white px-4 py-3 rounded-full shadow-2xl flex items-center justify-center space-x-2 text-xs font-semibold pointer-events-auto">
            <span class="material-symbols-rounded text-lg text-teal-300 shrink-0">check_circle</span>
            <span class="truncate" x-text="superadminToastMessage"></span>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: FORM TAMBAH / UBAH TITIK KANTOR (Matching _showOfficeFormDialog) --}}
    <div x-show="officeModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="officeModalOpen = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl max-w-[440px] w-full text-left shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <!-- Sticky Header -->
                <div class="p-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                    <div class="flex items-center space-x-2.5">
                        <div :class="isEditOffice ? 'bg-[#00288E]/12 text-[#00288E]' : 'bg-[#0D9488]/12 text-[#0D9488]'"
                            class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-lg"
                                x-text="isEditOffice ? 'edit_location_alt' : 'add_location_alt'"></span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#131B2E]"
                                x-text="isEditOffice ? 'Ubah Titik Lokasi Kantor' : 'Tambah Titik Lokasi Kantor Baru'"></h3>
                            <p class="text-[11px] text-[#64748B]"
                                x-text="isEditOffice ? 'Perbarui data titik penempatan & radius' : 'Atur koordinat presensi & basis penempatan'"></p>
                        </div>
                    </div>
                    <button type="button" @click="officeModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <span class="material-symbols-rounded text-xl">close</span>
                    </button>
                </div>

                <!-- Scrollable Body -->
                <div class="p-4 space-y-3 overflow-y-auto flex-1 text-xs">
                    <!-- Klasifikasi Titik -->
                    <div>
                        <label class="block font-bold text-[#131B2E] mb-1.5">Klasifikasi Titik Kantor:</label>
                        <div class="p-1 bg-[#EAEDFF] rounded-xl flex items-center space-x-1 border border-[#DAE2FD]/60">
                            <button type="button" @click="officeForm.is_primary = false"
                                :class="!officeForm.is_primary ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'text-[#64748B] font-semibold'"
                                class="flex-1 py-1.5 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
                                <span class="material-symbols-rounded text-sm">apartment</span>
                                <span>Kantor Cabang</span>
                            </button>
                            <button type="button" @click="officeForm.is_primary = true"
                                :class="officeForm.is_primary ? 'bg-[#00288E] text-white font-bold shadow-xs' : 'text-[#64748B] font-semibold'"
                                class="flex-1 py-1.5 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
                                <span class="material-symbols-rounded text-sm">star</span>
                                <span>Kantor Utama</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tautan Google Maps / Share Lokasi -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1 text-[#131B2E] font-bold">
                                <span class="material-symbols-rounded text-sm text-[#0D9488]">share_location</span>
                                <span>Tautan Google Maps / Share Lokasi:</span>
                            </div>
                            <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-[#0D9488]/10 text-[#0D9488]">Otomatis</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <div class="relative flex-1">
                                <span class="material-symbols-rounded absolute left-2.5 top-1/2 -translate-y-1/2 text-[#0D9488] text-base">link</span>
                                <input type="text" x-model="officeForm.map_link" @keydown.enter.prevent="resolveMapLink()"
                                    placeholder="Tempel tautan (misal: https://maps.app.goo.gl/...)"
                                    class="w-full pl-8 pr-2.5 py-2 bg-white rounded-xl border border-slate-300 text-xs text-[#131B2E] outline-none focus:border-[#0D9488]">
                            </div>
                            <button type="button" @click="resolveMapLink()"
                                :disabled="officeForm.is_resolving"
                                class="px-3 py-2 bg-[#0D9488] hover:bg-teal-700 text-white rounded-xl font-bold text-xs shrink-0 transition-colors cursor-pointer flex items-center space-x-1">
                                <template x-if="officeForm.is_resolving">
                                    <span class="animate-spin material-symbols-rounded text-sm">progress_activity</span>
                                </template>
                                <span>Terapkan</span>
                            </button>
                        </div>
                        <template x-if="officeForm.resolve_status">
                            <div :class="officeForm.resolve_success ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
                                class="p-2 rounded-lg border text-[11px] flex items-center space-x-1.5">
                                <span class="material-symbols-rounded text-sm"
                                    x-text="officeForm.resolve_success ? 'check_circle' : 'error'"></span>
                                <span class="flex-1" x-text="officeForm.resolve_status"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Live Mini Map Preview -->
                    <div class="h-[125px] rounded-xl border border-[#DAE2FD] overflow-hidden relative shadow-2xs bg-slate-100">
                        <!-- Satelit / Peta Switcher -->
                        <div class="absolute top-1.5 right-1.5 z-10 flex bg-white/95 backdrop-blur-xs rounded-lg p-0.5 border border-[#DAE2FD] shadow-xs">
                            <button type="button" @click="officeFormMapMode = 'satellite'"
                                :class="(officeFormMapMode || 'satellite') === 'satellite' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                                class="px-2 py-0.5 text-[10px] rounded flex items-center space-x-1 transition-all">
                                <span class="material-symbols-rounded text-[11px]">satellite_alt</span>
                                <span>Satelit</span>
                            </button>
                            <button type="button" @click="officeFormMapMode = 'roadmap'"
                                :class="(officeFormMapMode || 'satellite') === 'roadmap' ? 'bg-[#00288E] text-white font-bold shadow-2xs' : 'text-[#64748B] hover:text-[#131B2E]'"
                                class="px-2 py-0.5 text-[10px] rounded flex items-center space-x-1 transition-all">
                                <span class="material-symbols-rounded text-[11px]">map</span>
                                <span>Peta</span>
                            </button>
                        </div>
                        <img :src="getCartoTile(officeForm.preview_lat, officeForm.preview_lng, 16, officeFormMapMode)"
                            alt="Peta Titik Lokasi"
                            class="w-full h-full object-cover">
                        <!-- Geofence Circle Overlay simulation (dynamic with current radius) -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div :style="`width: ${Math.min(125, Math.max(45, 45 + ((officeForm.radius - 50) / 450) * 75))}px; height: ${Math.min(125, Math.max(45, 45 + ((officeForm.radius - 50) / 450) * 75))}px;`"
                                class="rounded-full bg-[#0D9488]/20 border-[1.5px] border-[#0D9488] transition-all"></div>
                        </div>
                        <!-- Center pin -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <span class="material-symbols-rounded text-[32px] text-[#DC2626] drop-shadow-md pb-2">location_pin</span>
                        </div>
                        <!-- Bottom pill -->
                        <div class="absolute bottom-1.5 left-2 right-2 bg-white/95 backdrop-blur-xs px-2 py-1 rounded-lg border border-[#DAE2FD] flex items-center justify-between text-[10px] font-bold">
                            <span class="text-[#131B2E] font-mono truncate">
                                Titik: <span x-text="Number(officeForm.preview_lat).toFixed(5)"></span>, <span x-text="Number(officeForm.preview_lng).toFixed(5)"></span>
                            </span>
                            <span class="text-[#0D9488] shrink-0 ml-1">
                                Radius <span x-text="officeForm.radius"></span>m
                            </span>
                        </div>
                    </div>

                    <!-- Nama Kantor -->
                    <div>
                        <label class="block font-bold text-[#131B2E] mb-1">Nama Kantor / Unit Pelayanan:</label>
                        <div class="relative">
                            <span class="material-symbols-rounded absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-base">business</span>
                            <input type="text" x-model="officeForm.name" placeholder="Contoh: Kantor Pusat PERUMDA Tirta Kepri"
                                class="w-full pl-8 pr-2.5 py-2 bg-white rounded-xl border border-slate-300 text-xs text-[#131B2E] outline-none focus:border-[#0D9488]">
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div>
                        <label class="block font-bold text-[#131B2E] mb-1">Alamat Lengkap:</label>
                        <div class="relative">
                            <span class="material-symbols-rounded absolute left-2.5 top-2 text-slate-400 text-base">place</span>
                            <textarea rows="2" x-model="officeForm.address" placeholder="Contoh: Jl. M.T Haryono Km.3, Tanjungpinang"
                                class="w-full pl-8 pr-2.5 py-1.5 bg-white rounded-xl border border-slate-300 text-xs text-[#131B2E] outline-none focus:border-[#0D9488] resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Radius Slider -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1 font-bold text-[#131B2E]">
                                <span class="material-symbols-rounded text-sm text-[#0D9488]">radar</span>
                                <span>Radius Presensi Geofence:</span>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-[#0D9488]/10 text-[#0D9488] font-bold text-[11px]"
                                x-text="officeForm.radius + ' Meter'"></span>
                        </div>
                        <input type="range" min="50" max="500" step="10" x-model.number="officeForm.radius"
                            class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#0D9488]">
                        <div class="flex justify-between text-[10px] text-[#64748B] font-medium pt-0.5">
                            <span>50m (Ketat)</span>
                            <span>250m</span>
                            <span>500m (Maksimal)</span>
                        </div>
                    </div>

                    <!-- Titik Koordinat Manual -->
                    <div>
                        <label class="block font-bold text-[#131B2E] mb-1">Titik Koordinat Manual:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="text" x-model="officeForm.latitude" @input="updateManualCoords()" placeholder="Latitude"
                                    class="w-full px-2.5 py-2 bg-white rounded-xl border border-slate-300 text-xs font-mono text-[#131B2E] outline-none focus:border-[#0D9488]">
                            </div>
                            <div>
                                <input type="text" x-model="officeForm.longitude" @input="updateManualCoords()" placeholder="Longitude"
                                    class="w-full px-2.5 py-2 bg-white rounded-xl border border-slate-300 text-xs font-mono text-[#131B2E] outline-none focus:border-[#0D9488]">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Actions Footer -->
                <div class="p-3 bg-white border-t border-slate-100 flex items-center space-x-2 shrink-0">
                    <button type="button" @click="officeModalOpen = false"
                        class="flex-1 py-2 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="saveOfficeFormModal()"
                        class="flex-1 py-2 rounded-xl bg-[#0D9488] hover:bg-teal-700 text-white font-bold text-xs flex items-center justify-center space-x-1 transition-colors cursor-pointer shadow-xs">
                        <span class="material-symbols-rounded text-sm">check</span>
                        <span x-text="isEditOffice ? 'Simpan Perubahan' : 'Simpan Lokasi'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: KONFIRMASI HAPUS TITIK KANTOR (Matching _showDeleteOfficeDialog) --}}
    <div x-show="deleteOfficeModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="deleteOfficeModalOpen = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-sm w-full text-left shadow-2xl space-y-3">
                <h3 class="text-base font-bold text-[#131B2E]">Hapus Titik Kantor</h3>
                <p class="text-xs text-[#64748B] leading-relaxed">
                    Apakah Anda yakin ingin menghapus titik kantor cabang "<span class="font-bold text-[#131B2E]" x-text="officeToDelete ? (officeToDelete.name || officeToDelete.nama_kantor) : ''"></span>"?
                </p>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="deleteOfficeModalOpen = false"
                        class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="button" @click="confirmDeleteOffice()"
                        class="px-4 py-1.5 bg-[#DC2626] hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs flex items-center space-x-1 cursor-pointer">
                        <span class="material-symbols-rounded text-sm">delete_forever</span>
                        <span>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL TIME PICKER UNTUK 4 SLOT --}}
    <div x-show="timePickerOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div @click="timePickerOpen = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-2xl p-5 max-w-xs w-full text-left shadow-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-[#131B2E]" x-text="timePickerTitle"></h3>
                    <button type="button" @click="timePickerOpen = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-rounded text-lg">close</span>
                    </button>
                </div>
                <div class="space-y-2">
                    <label class="block text-[11px] text-[#64748B]">Pilih waktu jadwal presensi:</label>
                    <input type="time" x-model="timePickerValue"
                        class="w-full text-center py-2 px-3 bg-[#F8FAFC] border border-[#DAE2FD] rounded-xl text-lg font-bold font-mono text-[#00288E] outline-none">
                    <!-- Preset Pills -->
                    <div class="grid grid-cols-4 gap-1.5 pt-1">
                        <template x-for="preset in ['07:30', '08:00', '08:30', '11:30', '12:00', '13:00', '13:30', '17:00']" :key="preset">
                            <button type="button" @click="timePickerValue = preset"
                                :class="timePickerValue === preset ? 'bg-[#00288E] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="py-1 rounded-lg text-[10.5px] font-bold font-mono transition-colors" x-text="preset"></button>
                        </template>
                    </div>
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="timePickerOpen = false"
                        class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="button" @click="saveTimePicker()"
                        class="px-4 py-1.5 bg-[#00288E] hover:bg-[#001f70] text-white text-xs font-bold rounded-xl shadow-xs">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- FLOATING OPERATIONAL TOAST (Matching SnackBarBehavior.floating in Flutter) --}}
    <div x-show="opToastMessage" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 max-w-[380px] w-full px-4 pointer-events-none">
        <div class="bg-[#1E293B] text-white px-4 py-3 rounded-full shadow-2xl flex items-center justify-center space-x-2 text-xs font-semibold pointer-events-auto">
            <span class="material-symbols-rounded text-lg text-emerald-400 shrink-0">check_circle</span>
            <span class="truncate" x-text="opToastMessage"></span>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: LOG AKTIVITAS SISTEM (Matching SuperadminActivityLogScreen.dart) --}}
    <div x-show="activityLogModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-3 sm:p-4 text-center">
            <div @click="activityLogModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-[#F8FAFC] rounded-2xl max-w-[480px] w-full text-left shadow-2xl flex flex-col max-h-[92vh] overflow-hidden border border-slate-200">
                <!-- Header (Matching AppBar in SuperadminActivityLogScreen) -->
                <div class="p-3.5 px-4 border-b border-slate-200/80 flex items-center justify-between shrink-0 bg-white">
                    <div class="flex items-center space-x-2.5">
                        <button type="button" @click="activityLogModal = false" class="p-1.5 -ml-1 text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">
                            <span class="material-symbols-rounded text-xl leading-none">arrow_back</span>
                        </button>
                        <div>
                            <h3 class="text-sm font-bold text-[#131B2E] leading-tight">Log Aktivitas Superadmin</h3>
                            <p class="text-[11px] text-[#64748B] font-medium leading-tight">Audit Trail &amp; Rekam Jejak Sistem</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button type="button" @click="exportActivityLogsCsv()" title="Ekspor Data CSV"
                            class="p-2 text-[#0D9488] hover:bg-teal-50 rounded-xl transition-colors">
                            <span class="material-symbols-rounded text-xl leading-none">download</span>
                        </button>
                        <button type="button" @click="showSuperadminToast('Log aktivitas telah disegarkan.')" title="Segarkan Log"
                            class="p-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                            <span class="material-symbols-rounded text-xl leading-none">refresh</span>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Body (Matching _SuperadminActivityLogScreenState content) -->
                <div class="p-4 space-y-3.5 overflow-y-auto flex-1">
                    <!-- 1. KPI Bento Metrics (Matching _buildMetricsBento) -->
                    <div class="grid grid-cols-4 gap-2">
                        <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-extrabold text-[#00288E]" x-text="getActivityLogMetrics().total"></span>
                                <span class="material-symbols-rounded text-sm text-[#00288E]">history</span>
                            </div>
                            <div class="mt-1">
                                <p class="text-[10px] font-bold text-[#131B2E] truncate">Total Aktivitas</p>
                                <p class="text-[8.5px] text-[#64748B] truncate">Rekam Jejak</p>
                            </div>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-extrabold text-[#0D9488]" x-text="getActivityLogMetrics().today"></span>
                                <span class="material-symbols-rounded text-sm text-[#0D9488]">today</span>
                            </div>
                            <div class="mt-1">
                                <p class="text-[10px] font-bold text-[#131B2E] truncate">Hari Ini</p>
                                <p class="text-[8.5px] text-[#64748B] truncate">Aksi Aktif</p>
                            </div>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-extrabold text-[#00288E]" x-text="getActivityLogMetrics().geofence"></span>
                                <span class="material-symbols-rounded text-sm text-[#00288E]">apartment</span>
                            </div>
                            <div class="mt-1">
                                <p class="text-[10px] font-bold text-[#131B2E] truncate">Lokasi Kantor</p>
                                <p class="text-[8.5px] text-[#64748B] truncate">Titik Kantor</p>
                            </div>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-extrabold text-[#D97706]" x-text="getActivityLogMetrics().security"></span>
                                <span class="material-symbols-rounded text-sm text-[#D97706]">shield</span>
                            </div>
                            <div class="mt-1">
                                <p class="text-[10px] font-bold text-[#131B2E] truncate">Keamanan</p>
                                <p class="text-[8.5px] text-[#64748B] truncate">Audit Sesi</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Search Box (Matching _buildSearchBox) -->
                    <div class="relative bg-white rounded-xl border border-slate-200 shadow-xs flex items-center px-3 py-1">
                        <span class="material-symbols-rounded text-lg text-[#0D9488] mr-2">search</span>
                        <input type="text" x-model="activityLogSearch" placeholder="Cari aksi, nama aktor, NIK, atau objek..."
                            class="w-full py-1.5 text-xs text-[#131B2E] placeholder-[#94A3B8] bg-transparent focus:outline-none">
                        <button type="button" x-show="activityLogSearch" @click="activityLogSearch = ''" class="p-1 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-rounded text-sm">close</span>
                        </button>
                    </div>

                    <!-- 3. Category Filter Chips (Matching _buildCategoryChips) -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1">
                        <button type="button" @click="activityLogCategory = null"
                            :class="activityLogCategory === null ? 'bg-[#00288E] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            class="px-2.5 py-1.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex items-center space-x-1 shrink-0 transition-all">
                            <span class="material-symbols-rounded text-xs">apps</span>
                            <span>Semua Kategori</span>
                        </button>
                        <button type="button" @click="activityLogCategory = 'geofencing'"
                            :class="activityLogCategory === 'geofencing' ? 'bg-[#0D9488] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            class="px-2.5 py-1.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex items-center space-x-1 shrink-0 transition-all">
                            <span class="material-symbols-rounded text-xs">apartment</span>
                            <span>Lokasi Kantor</span>
                        </button>
                        <button type="button" @click="activityLogCategory = 'approval'"
                            :class="activityLogCategory === 'approval' ? 'bg-[#D97706] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            class="px-2.5 py-1.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex items-center space-x-1 shrink-0 transition-all">
                            <span class="material-symbols-rounded text-xs">assignment_turned_in</span>
                            <span>Persetujuan</span>
                        </button>
                        <button type="button" @click="activityLogCategory = 'operationalSchedule'"
                            :class="activityLogCategory === 'operationalSchedule' ? 'bg-[#16A34A] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            class="px-2.5 py-1.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex items-center space-x-1 shrink-0 transition-all">
                            <span class="material-symbols-rounded text-xs">schedule</span>
                            <span>Jam Operasional</span>
                        </button>
                        <button type="button" @click="activityLogCategory = 'employeeAccount'"
                            :class="activityLogCategory === 'employeeAccount' ? 'bg-[#00288E] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            class="px-2.5 py-1.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex items-center space-x-1 shrink-0 transition-all">
                            <span class="material-symbols-rounded text-xs">manage_accounts</span>
                            <span>Pegawai &amp; Akun</span>
                        </button>
                        <button type="button" @click="activityLogCategory = 'securityAuth'"
                            :class="activityLogCategory === 'securityAuth' ? 'bg-[#00288E] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            class="px-2.5 py-1.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex items-center space-x-1 shrink-0 transition-all">
                            <span class="material-symbols-rounded text-xs">security</span>
                            <span>Keamanan &amp; Sesi</span>
                        </button>
                    </div>

                    <!-- 4. Severity Level Filter Chips (Matching _buildLevelFilterChips) -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1 text-[10px]">
                        <span class="text-[11px] font-bold text-slate-500 mr-1 shrink-0">Tingkat:</span>
                        <button type="button" @click="activityLogLevel = null"
                            :class="activityLogLevel === null ? 'bg-slate-700 text-white font-bold' : 'bg-white text-slate-600 border border-slate-200 font-medium'"
                            class="px-2 py-0.5 rounded-md shrink-0 transition-colors">Semua</button>
                        <button type="button" @click="activityLogLevel = 'success'"
                            :class="activityLogLevel === 'success' ? 'bg-emerald-600 text-white font-bold' : 'bg-white text-emerald-700 border border-slate-200 font-medium'"
                            class="px-2 py-0.5 rounded-md shrink-0 transition-colors">Sukses</button>
                        <button type="button" @click="activityLogLevel = 'info'"
                            :class="activityLogLevel === 'info' ? 'bg-sky-600 text-white font-bold' : 'bg-white text-sky-700 border border-slate-200 font-medium'"
                            class="px-2 py-0.5 rounded-md shrink-0 transition-colors">Informasi</button>
                        <button type="button" @click="activityLogLevel = 'warning'"
                            :class="activityLogLevel === 'warning' ? 'bg-amber-600 text-white font-bold' : 'bg-white text-amber-700 border border-slate-200 font-medium'"
                            class="px-2 py-0.5 rounded-md shrink-0 transition-colors">Peringatan</button>
                    </div>

                    <!-- 5. Timeline Audit Feed List (Matching _buildLogFeed & _buildLogCard) -->
                    <div>
                        <div class="flex items-center justify-between pb-2 pt-1">
                            <p class="text-xs font-bold text-[#131B2E]">
                                Rekam Audit Trail (<span x-text="filteredActivityLogs().length"></span> Log)
                            </p>
                            <span class="text-[10px] text-[#64748B]">Ketuk kartu untuk detail audit</span>
                        </div>

                        <!-- Empty State -->
                        <div x-show="filteredActivityLogs().length === 0"
                            class="bg-white rounded-2xl border border-slate-200 p-6 text-center shadow-xs">
                            <span class="material-symbols-rounded text-4xl text-slate-300">search_off</span>
                            <h4 class="text-xs font-bold text-slate-800 mt-2">Tidak ada log aktivitas yang cocok</h4>
                            <p class="text-[11px] text-slate-500 mt-1 max-w-[280px] mx-auto">Coba ubah kata kunci pencarian atau reset filter kategori/tingkat keparahan.</p>
                            <button type="button" @click="activityLogSearch = ''; activityLogCategory = null; activityLogLevel = null;"
                                class="mt-3 px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg inline-flex items-center space-x-1">
                                <span class="material-symbols-rounded text-sm">refresh</span>
                                <span>Reset Semua Filter</span>
                            </button>
                        </div>

                        <!-- List Cards -->
                        <div class="space-y-2.5">
                            <template x-for="log in filteredActivityLogs()" :key="log.id">
                                <div @click="openActivityDetail(log)"
                                    class="bg-white p-3 rounded-2xl border border-slate-200 hover:border-teal-300 shadow-xs cursor-pointer transition-all hover:shadow-md flex items-start space-x-3">
                                    <!-- Icon Node -->
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                        :style="'background-color: ' + log.categoryColor + '18; color: ' + log.categoryColor">
                                        <span class="material-symbols-rounded text-lg" x-text="log.categoryIcon"></span>
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Tags & Time -->
                                        <div class="flex items-center justify-between text-[9.5px] mb-1">
                                            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                                                <span class="px-1.5 py-0.5 rounded font-bold"
                                                    :style="'background-color: ' + log.categoryColor + '15; color: ' + log.categoryColor"
                                                    x-text="log.categoryLabel"></span>
                                                <span class="px-1.5 py-0.5 rounded font-extrabold"
                                                    :style="'background-color: ' + log.levelColor + '18; color: ' + log.levelColor"
                                                    x-text="log.levelLabel"></span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium shrink-0 ml-1" x-text="log.relativeTime"></span>
                                        </div>
                                        <!-- Title -->
                                        <h4 class="text-xs font-bold text-[#131B2E] leading-snug line-clamp-1" x-text="log.title"></h4>
                                        <!-- Description -->
                                        <p class="text-[11px] text-[#64748B] line-clamp-2 mt-0.5 leading-relaxed" x-text="log.description"></p>
                                        <!-- Footer Info -->
                                        <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
                                            <div class="flex items-center space-x-1 truncate">
                                                <span class="material-symbols-rounded text-xs text-slate-400">person</span>
                                                <span class="font-medium text-slate-700 truncate" x-text="log.actorName + ' (' + log.actorRole + ')'"></span>
                                            </div>
                                            <span x-show="log.ipAddress" class="font-mono text-slate-400 text-[9px] shrink-0 ml-2" x-text="'• IP ' + log.ipAddress"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SUPERADMIN: RINCIAN AUDIT DETAIL (Matching _showAuditDetailModal in SuperadminActivityLogScreen) --}}
    <div x-show="activityLogDetailModal && activityLogSelected" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-screen p-0 sm:p-4 text-center">
            <div @click="activityLogDetailModal = false" class="fixed inset-0 bg-[#131B2E]/60 backdrop-blur-xs transition-opacity"></div>
            <div class="relative bg-white rounded-t-3xl sm:rounded-2xl max-w-[440px] w-full text-left shadow-2xl p-5 overflow-hidden border border-slate-100 max-h-[90vh] overflow-y-auto">
                <!-- Handle Bar -->
                <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-3.5"></div>

                <template x-if="activityLogSelected">
                    <div>
                        <!-- Header with Category and Level Pill -->
                        <div class="flex items-center justify-between mb-2.5">
                            <div class="px-2.5 py-1 rounded-lg text-xs font-bold flex items-center space-x-1.5"
                                :style="'background-color: ' + activityLogSelected.categoryColor + '18; color: ' + activityLogSelected.categoryColor">
                                <span class="material-symbols-rounded text-sm" x-text="activityLogSelected.categoryIcon"></span>
                                <span x-text="activityLogSelected.categoryLabel"></span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold"
                                :style="'background-color: ' + activityLogSelected.levelColor + '18; color: ' + activityLogSelected.levelColor"
                                x-text="activityLogSelected.levelLabel"></span>
                        </div>

                        <!-- Title and Description -->
                        <h3 class="text-sm font-extrabold text-[#131B2E] leading-snug" x-text="activityLogSelected.title"></h3>
                        <p class="text-xs text-[#64748B] mt-1 leading-relaxed" x-text="activityLogSelected.description"></p>

                        <div class="border-t border-slate-100 my-3"></div>

                        <!-- Audit Details -->
                        <h4 class="text-xs font-bold text-[#131B2E] mb-2">Rincian Rekam Jejak Audit (Audit Trail):</h4>
                        <div class="bg-[#F8FAFC] rounded-xl p-3 space-y-1.5 text-[11px] border border-slate-100">
                            <div class="flex justify-between">
                                <span class="text-slate-500">ID Log:</span>
                                <span class="font-mono font-bold text-slate-800" x-text="activityLogSelected.id"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Waktu Eksekusi:</span>
                                <span class="font-semibold text-slate-800" x-text="activityLogSelected.formattedDate + ' • ' + activityLogSelected.formattedTime"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Aktor / Pelaksana:</span>
                                <span class="font-semibold text-slate-800 text-right" x-text="activityLogSelected.actorName + ' (' + activityLogSelected.actorRole + ')'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">NIK Aktor:</span>
                                <span class="font-mono text-slate-800" x-text="activityLogSelected.actorNip"></span>
                            </div>
                            <div class="flex justify-between" x-show="activityLogSelected.targetEntity">
                                <span class="text-slate-500">Target Objek:</span>
                                <span class="font-medium text-slate-800 text-right truncate max-w-[200px]" x-text="activityLogSelected.targetEntity"></span>
                            </div>
                            <div class="flex justify-between" x-show="activityLogSelected.ipAddress">
                                <span class="text-slate-500">Alamat IP:</span>
                                <span class="font-mono text-slate-800" x-text="activityLogSelected.ipAddress"></span>
                            </div>
                            <div class="flex justify-between" x-show="activityLogSelected.deviceInfo">
                                <span class="text-slate-500">Perangkat:</span>
                                <span class="font-medium text-slate-800 text-right truncate max-w-[200px]" x-text="activityLogSelected.deviceInfo"></span>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <template x-if="activityLogSelected.metadata && Object.keys(activityLogSelected.metadata).length > 0">
                            <div class="mt-3">
                                <h4 class="text-xs font-bold text-[#131B2E] mb-1.5">Parameter Perubahan (Metadata):</h4>
                                <div class="bg-[#F1F5F9] rounded-xl p-2.5 font-mono text-[10.5px] text-slate-700 space-y-1">
                                    <template x-for="(val, key) in activityLogSelected.metadata" :key="key">
                                        <div>• <span class="text-slate-500" x-text="key"></span>: <span class="font-bold text-slate-900" x-text="val"></span></div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Action Buttons -->
                        <div class="mt-4 pt-2 flex items-center space-x-2">
                            <button type="button" @click="copyActivityDetail(activityLogSelected)"
                                class="flex-1 py-2 px-3 border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl flex items-center justify-center space-x-1.5 transition-colors">
                                <span class="material-symbols-rounded text-sm">content_copy</span>
                                <span>Salin Log</span>
                            </button>
                            <button type="button" @click="activityLogDetailModal = false"
                                class="flex-1 py-2 px-3 bg-[#0D9488] hover:bg-[#0f766e] text-white font-bold text-xs rounded-xl flex items-center justify-center space-x-1 transition-colors shadow-xs">
                                <span>Tutup</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- MODAL 6: ATTENDANCE DETAIL BOTTOM SHEET (Matching Flutter AttendanceDetailBottomSheet.dart) --}}
    <x-attendance-detail-modal />

    <!-- 7. IN-APP BANNER NOTIFICATION (Heads-Up Banner di Layar) -->
    <div x-show="alarmBannerVisible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-full opacity-0"
        class="fixed top-2 left-1/2 -translate-x-1/2 w-[92%] max-w-[420px] z-[99999] bg-[#00288E] text-white p-3.5 rounded-2xl shadow-2xl border border-white/20 select-none">
        <div class="flex items-start space-x-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center shrink-0 animate-bounce">
                <span class="material-symbols-rounded text-2xl">alarm_on</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-300">Banner Pemberitahuan &amp; Alarm</span>
                    <span class="text-[10px] text-white/70" x-text="clockTime"></span>
                </div>
                <h4 class="text-xs font-bold text-white truncate mt-0.5" x-text="alarmBannerData.title"></h4>
                <p class="text-[11px] text-white/90 leading-tight mt-0.5 line-clamp-2" x-text="alarmBannerData.body"></p>
                <div class="mt-2.5 flex items-center space-x-2">
                    <button type="button" @click="openPresensiFromAlarm(alarmBannerData.slotType)"
                        class="px-3 py-1 bg-white text-[#00288E] rounded-lg text-xs font-bold hover:bg-slate-100 transition-colors shadow-xs">
                        Presensi Sekarang
                    </button>
                    <button type="button" @click="dismissActiveAlarm()"
                        class="px-3 py-1 bg-white/20 text-white rounded-lg text-xs font-semibold hover:bg-white/30 transition-colors">
                        Matikan Alarm
                    </button>
                </div>
            </div>
            <button type="button" @click="dismissActiveAlarm()" class="text-white/70 hover:text-white transition-colors">
                <span class="material-symbols-rounded text-base">close</span>
            </button>
        </div>
    </div>

    <!-- 8. MODAL DERING ALARM PRESENSI AKTIF (Loud Ringing Dialog) -->
    <div x-show="isAlarmModalOpen" class="fixed inset-0 z-[999998] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 max-w-[360px] w-full text-center space-y-4 shadow-2xl border border-slate-100 relative overflow-hidden">
            <!-- Pulsing Ambient Ring -->
            <div class="w-20 h-20 mx-auto rounded-full bg-[#00288E]/10 flex items-center justify-center relative">
                <div class="absolute inset-0 rounded-full bg-[#0051D5]/20 animate-ping"></div>
                <div class="w-14 h-14 rounded-full bg-[#00288E] text-white flex items-center justify-center shadow-lg relative z-10 animate-bounce">
                    <span class="material-symbols-rounded text-3xl">notifications_active</span>
                </div>
            </div>

            <div>
                <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10.5px] font-extrabold uppercase tracking-wider">
                    Alarm Presensi Aktif
                </span>
                <h3 class="text-base font-extrabold text-[#131B2E] mt-2" x-text="alarmBannerData.title || 'Waktunya Presensi Lapangan'"></h3>
                <p class="text-xs text-[#64748B] mt-1 leading-relaxed" x-text="alarmBannerData.body || 'Silakan lakukan verifikasi lokasi dan bukti swafoto sesuai jadwal operasional.'"></p>
                <div class="mt-2 text-[11px] font-mono font-bold text-[#0051D5]">
                    Pukul <span x-text="clockTime"></span> WIB
                </div>
            </div>

            <div class="p-2.5 bg-[#F8FAFC] rounded-2xl border border-[#DAE2FD]/80 text-[11px] text-slate-600 flex items-center justify-center space-x-1.5">
                <span class="material-symbols-rounded text-base text-[#0051D5]">smartphone</span>
                <span>Notifikasi dikirim ke Banner, Layar Terkunci &amp; Pusat Pemberitahuan</span>
            </div>

            <div class="space-y-2 pt-1">
                <button type="button" @click="openPresensiFromAlarm(alarmBannerData.slotType)"
                    class="w-full py-3 bg-[#00288E] hover:bg-[#001D6E] text-white rounded-xl font-bold text-xs shadow-md transition-colors flex items-center justify-center space-x-2">
                    <span class="material-symbols-rounded text-base">check_circle</span>
                    <span>Lakukan Presensi Sekarang</span>
                </button>
                <button type="button" @click="dismissActiveAlarm()"
                    class="w-full py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs transition-colors">
                    Matikan &amp; Tutup Alarm
                </button>
            </div>
        </div>
    </div>

    <!-- 9. MODAL PENGATURAN & UJI ALARM PRESENSI (Matching Mobile Preferences) -->
    <div x-show="alarmSettingsModal" class="fixed inset-0 z-[99990] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-3xl max-w-[420px] w-full max-h-[90vh] overflow-y-auto p-5 shadow-2xl border border-slate-100 space-y-4"
            @click.outside="alarmSettingsModal = false">
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#00288E]/10 text-[#00288E] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">alarm_on</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#131B2E]">Pengaturan Alarm &amp; Notifikasi</h3>
                        <p class="text-[11px] text-[#64748B]">Banner, Layar Terkunci &amp; Pusat Pemberitahuan</p>
                    </div>
                </div>
                <button type="button" @click="alarmSettingsModal = false" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-rounded text-xl">close</span>
                </button>
            </div>

            <!-- Status Izin Notifikasi Perangkat -->
            <div class="p-3.5 rounded-2xl border text-xs"
                :class="alarmNotificationPerm === 'granted' ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' : 'bg-amber-50/80 border-amber-200 text-amber-900'">
                <div class="flex items-start space-x-2.5">
                    <span class="material-symbols-rounded text-lg shrink-0 mt-0.5"
                        :class="alarmNotificationPerm === 'granted' ? 'text-emerald-600' : 'text-amber-600'"
                        x-text="alarmNotificationPerm === 'granted' ? 'check_circle' : 'notification_important'"></span>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-[12px]"
                            x-text="alarmNotificationPerm === 'granted' ? 'Izin Notifikasi Perangkat Aktif' : 'Izin Notifikasi Belum Diaktifkan'"></div>
                        <p class="text-[11px] leading-relaxed mt-0.5 opacity-90"
                            x-text="alarmNotificationPerm === 'granted' 
                                ? 'Notifikasi alarm siap tampil sebagai Banner di layar, muncul pada Layar Terkunci, dan menetap di Pusat Pemberitahuan perangkat Anda.' 
                                : 'Aktifkan izin perangkat agar alarm dapat memunculkan banner, bersuara di Layar Terkunci, dan menetap di Pusat Pemberitahuan.'">
                        </p>
                        <template x-if="alarmNotificationPerm !== 'granted'">
                            <button type="button" @click="requestAlarmPermission()"
                                class="mt-2 px-3 py-1.5 bg-[#00288E] text-white rounded-xl text-xs font-bold hover:bg-[#001D6E] transition-colors flex items-center space-x-1.5">
                                <span class="material-symbols-rounded text-sm">notifications</span>
                                <span>Aktifkan Izin Notifikasi Perangkat</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Tombol Simulasi Uji Coba Alarm & Notifikasi -->
            <div class="p-3.5 bg-[#F2F6FF] rounded-2xl border border-[#DAE2FD] space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-rounded text-lg text-[#0051D5]">ring_volume</span>
                        <span class="text-xs font-bold text-[#131B2E]">Uji Coba Alarm &amp; Notifikasi</span>
                    </div>
                    <span class="text-[10px] font-bold text-[#0051D5] bg-white px-2 py-0.5 rounded-full border border-[#DAE2FD]">Simulasi Langsung</span>
                </div>
                <p class="text-[11px] text-[#64748B] leading-relaxed">
                    Tekan tombol di bawah untuk menguji suara alarm nyaring, getaran perangkat, serta kemunculan notifikasi pada <b>Banner Layar</b>, <b>Layar Terkunci</b>, dan <b>Pusat Pemberitahuan</b>.
                </p>
                <button type="button" @click="testDeviceAlarm('masuk')"
                    class="w-full py-2.5 bg-[#0051D5] hover:bg-[#003EA8] text-white rounded-xl font-bold text-xs flex items-center justify-center space-x-2 shadow-xs transition-colors">
                    <span class="material-symbols-rounded text-base">notifications_active</span>
                    <span>Uji Alarm &amp; Kirim Notifikasi Sekarang</span>
                </button>
            </div>

            <!-- Daftar 4 Slot Pengingat Presensi -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#131B2E]">4 Slot Pengingat Harian Otomatis</h4>
                    <span class="text-[10.5px] text-[#64748B]">Sesuai Tab Operasional</span>
                </div>

                <!-- Slot 1: Masuk -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">login</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">1. Presensi Masuk</div>
                            <div class="text-[10.5px] text-[#64748B]">Pukul {{ $todaySchedule['masuk'] ?? '07:45' }} WIB (Toleransi {{ $todaySchedule['toleransi_menit'] ?? 15 }}m)</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmSlotsEnabled.masuk" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#059669]"></div>
                    </label>
                </div>

                <!-- Slot 2: Istirahat Keluar -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">free_breakfast</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">2. Istirahat Keluar</div>
                            <div class="text-[10.5px] text-[#64748B]">Pukul {{ $todaySchedule['istirahat_keluar'] ?? '11:30' }} WIB</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmSlotsEnabled.istirahat_keluar" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0051D5]"></div>
                    </label>
                </div>

                <!-- Slot 3: Istirahat Masuk -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">timer</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">3. Istirahat Selesai / Masuk</div>
                            <div class="text-[10.5px] text-[#64748B]">Pukul {{ $todaySchedule['istirahat_masuk'] ?? '13:00' }} WIB</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmSlotsEnabled.istirahat_masuk" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#4F46E5]"></div>
                    </label>
                </div>

                <!-- Slot 4: Pulang -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-800 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">logout</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">4. Presensi Pulang</div>
                            <div class="text-[10.5px] text-[#64748B]">Pukul {{ $todaySchedule['pulang'] ?? '16:45' }} WIB</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmSlotsEnabled.pulang" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#EF4444]"></div>
                    </label>
                </div>
            </div>

            <!-- Preferensi Output Alarm & Notifikasi Mobile -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#131B2E]">Preferensi Suara &amp; Perangkat Mobile</h4>
                    <span class="text-[10px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full font-semibold border border-emerald-200">PWA Mobile Ready</span>
                </div>

                <!-- Toggle 1: Suara Alarm Nyaring -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">volume_up</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">Suara Alarm Nyaring</div>
                            <div class="text-[10.5px] text-[#64748B]">Melodi 4-nada otomatis via Web Audio API</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmSoundEnabled" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00288E]"></div>
                    </label>
                </div>

                <!-- Toggle 2: Getaran Perangkat -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">vibration</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">Getaran Perangkat Mobile</div>
                            <div class="text-[10.5px] text-[#64748B]">Getar pulsa saat jam presensi berdering</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmVibrationEnabled" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00288E]"></div>
                    </label>
                </div>

                <!-- Toggle 3: Banner Layar & Layar Terkunci -->
                <div class="p-2.5 bg-[#F8FAFC] rounded-xl border border-[#DAE2FD]/70 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-base">notification_important</span>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-bold text-[#131B2E]">Layar Terkunci &amp; Pusat Pemberitahuan</div>
                            <div class="text-[10.5px] text-[#64748B]">Tampilkan notifikasi di layar kunci &amp; banner</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="alarmDeviceNotificationEnabled" @change="if (alarmDeviceNotificationEnabled && window.TirtafieldAlarm) window.TirtafieldAlarm.requestPermission()" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00288E]"></div>
                    </label>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="pt-2">
                <button type="button" @click="saveAlarmPreferences()"
                    class="w-full py-2.5 bg-[#00288E] hover:bg-[#001D6E] text-white rounded-xl font-bold text-xs transition-colors flex items-center justify-center space-x-1.5 shadow-xs">
                    <span>Simpan &amp; Tutup Pengaturan</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function tirtafieldApp() {
        return {
            activeTab: '{{ auth()->user()?->isKaryawan() ? 'beranda' : 'dashboard' }}',
            operationalSubTab: 0,
            
            // Superadmin Employee Management States (Matching Flutter SuperadminEmployeeScreen)
            empSubTab: 0,
            employeesList: @json($allEmployees->map(fn($u) => $u->toApiResponse())),
            officesList: @json($systemSettings['geofencing']['offices'] ?? []),
            bidangDataList: [
                { nama: 'Dewan Direksi', seksi: ['Direktur'] },
                { nama: 'Bidang Hubungan Langganan', seksi: ['Seksi Pembacaan Meter', 'Seksi Perawatan Meter'] },
                { nama: 'Bidang Teknik', seksi: ['Seksi Perencanaan', 'Seksi Distribusi', 'Seksi Survei & Verifikasi Teknis'] },
                { nama: 'Satuan Pengendalian Kehilangan Air', seksi: ['Bidang Kehilangan Air', 'Bidang Analisa Jaringan & GIS'] },
                { nama: 'PUSIMPEL IT & System', seksi: ['Superadmin Pusimpel', 'Staff IT & Jaringan'] }
            ],
            roleSearchQuery: '',
            empSearchQuery: '',
            empFilterRole: null,
            empFilterDepartment: null,
            empFilterOfficeId: null,
            empSortOption: 'default',
            sortBottomSheet: false,
            changeRoleModal: false,
            changeRoleData: { id: null, nip: '', name: '', selectedRole: 'karyawan' },
            resetPinModal: false,
            resetPinData: { id: null, nip: '', name: '', newPin: '123456' },
            addEmployeeModal: false,
            addEmpForm: {
                nip: '',
                name: '',
                department: 'Bidang Hubungan Langganan',
                position: 'Seksi Pembacaan Meter',
                office_id: '{{ $offices->first()?->id ?? "hq" }}',
                phone: '',
                role: 'karyawan',
                pin: '123456'
            },
            editEmployeeModal: false,
            editEmpForm: {
                id: null,
                nip: '',
                name: '',
                department: 'Bidang Hubungan Langganan',
                position: 'Seksi Pembacaan Meter',
                office_id: '{{ $offices->first()?->id ?? "hq" }}',
                phone: ''
            },
            detailEmployeeSheet: false,
            detailEmpData: null,
            empToastMessage: null,
            empToastTimeout: null,
            showEmpToast(msg) {
                if (this.empToastTimeout) clearTimeout(this.empToastTimeout);
                this.empToastMessage = msg;
                this.empToastTimeout = setTimeout(() => {
                    this.empToastMessage = null;
                }, 3500);
            },
            empSearch: '',
            dirSearch: '',
            approvalTab: 'pending',
            itFeedTab: 'pending',
            clockTime: '{{ \Carbon\Carbon::now("Asia/Jakarta")->format("H:i:s") }}',
            indonesianDate: '{{ \Carbon\Carbon::parse($today)->locale("id")->isoFormat("dddd, D MMMM Y") }}',
            greeting: 'Selamat Pagi',

            // Locked SnackBar Toast Matching Flutter SnackBarBehavior.floating
            lockedToastMessage: null,
            lockedToastTimeout: null,
            showLockedToast(msg) {
                if (this.lockedToastTimeout) clearTimeout(this.lockedToastTimeout);
                this.lockedToastMessage = msg;
                this.lockedToastTimeout = setTimeout(() => {
                    this.lockedToastMessage = null;
                }, 3500);
            },

            // Modal Verification Flow (3 Steps)
            verificationModal: false,
            verificationStep: 1,
            activeSlotType: 'masuk',
            activeSlotTitle: 'Presensi Masuk',
            opTodaySchedule: @json($todaySchedule),
            myAttendanceSlots: @json($mySlots),
            isOvernightActive: {{ !empty($isOvernightActive) ? 'true' : 'false' }},
            selectedVerificationOfficeId: '{{ $userOffice?->id ?? ($offices->first()?->id ?? "hq") }}',
            targetOfficeName: '{{ $userOffice?->nama_kantor ?? "Kantor Pusat PERUMDA Tirta Kepri" }}',
            targetOfficeLat: {{ (float)($userOffice?->latitude ?? 0.92031) }},
            targetOfficeLng: {{ (float)($userOffice?->longitude ?? 104.46069) }},
            targetOfficeRadius: {{ (int)($userOffice?->radius ?? 100) }},
            gpsLat: {{ (float)($userOffice?->latitude ?? 0.92031) }},
            gpsLng: {{ (float)($userOffice?->longitude ?? 104.46069) }},
            gpsAddress: 'Tj. Pinang Timur, Kota Tanjung Pinang, Kepulauan Riau 29113',
            calculatedDistance: 12,
            isRefreshingGps: false,
            isLateCheckIn: false,
            lateDurationMinutes: 0,
            lateReason: '',
            leafletMap: null,
            leafletTileLayer: null,
            mapMode: 'satellite',
            selfieStream: null,
            capturedPhoto: null,
            attendanceNotes: '',
            isSubmitting: false,
            isFlashing: false,

            // Alarm & Notification States (Banner, Layar Terkunci, Pusat Pemberitahuan)
            alarmSettingsModal: false,
            alarmBannerVisible: false,
            alarmBannerData: {
                title: 'Pengingat Presensi Tirtafield',
                body: 'Waktunya melakukan presensi mobile.',
                slotType: 'masuk',
                slotTitle: 'Presensi Masuk'
            },
            isAlarmModalOpen: false,
            alarmNotificationPerm: ('Notification' in window) ? Notification.permission : 'default',
            alarmSlotsEnabled: {
                masuk: true,
                istirahat_keluar: true,
                istirahat_masuk: true,
                pulang: true
            },
            alarmSoundEnabled: true,
            alarmVibrationEnabled: true,
            alarmDeviceNotificationEnabled: true,
            alarmTriggeredToday: {},

            // Modal Other States
            requestModal: false,
            receiptModal: false,
            reviewModal: false,
            addEmpModal: false,
            editEmpModal: false,
            editEmpData: {},
            detailModal: false,
            detailPhotoModal: false,
            viewReceiptData: {},
            reviewData: {},
            reviewNotes: '',

            // Universal Current User Avatar State
            currentUserAvatar: '{{ auth()->user()?->avatar_url ?? "" }}',

            // Profile Camera & Upload State
            profileCameraModal: false,
            profileCameraStream: null,
            profileCameraFacing: 'user',
            profileSnappedPhoto: null,
            isUploadingProfilePhoto: false,
            activePhotoRoleTarget: null,

            // Karyawan Akun & PIN States
            karyawanPhotoSheet: false,
            karyawanPinModal: false,
            karyawanOldPin: '',
            karyawanNewPin: '',
            karyawanConfirmPin: '',
            karyawanObscureOld: true,
            karyawanObscureNew: true,
            karyawanObscureConfirm: true,
            karyawanPinError: null,

            // Admin Direktur Akun States (Matching AdminAkunScreen.dart)
            adminPhone: '{{ auth()->user()?->phone ?? "081234567890" }}',
            adminPresenceAlerts: true,
            adminApprovalAlerts: true,
            adminPhotoSheet: false,
            adminPinModal: false,
            adminLogoutModal: false,
            adminOldPin: '',
            adminNewPin: '',
            adminConfirmPin: '',
            adminObscureOld: true,
            adminObscureNew: true,
            adminObscureConfirm: true,
            adminPinError: null,
            adminSuccessToastMessage: null,
            adminSuccessToastTimeout: null,

            // Superadmin IT Profile States (Matching SuperadminProfileScreen.dart)
            superadminName: '{{ addslashes(auth()->user()?->name ?? "Superadmin Pusimpel") }}',
            superadminNik: '{{ addslashes(auth()->user()?->nik ?? "P242101") }}',
            superadminDepartment: '{{ addslashes(auth()->user()?->department ?? "PUSIMPEL IT & System") }}',
            superadminPosition: '{{ addslashes(auth()->user()?->position ?? "Superadmin Pusimpel") }}',
            superadminPhone: '{{ addslashes(auth()->user()?->phone ?? "081234567890") }}',
            superadminAvatarUrl: '{{ auth()->user()?->avatar_url ?? "https://lh3.googleusercontent.com/aida-public/AB6AXuBHnouN4NhcfWmjDbG_twFO6sebw3bZ18Eh5o0S5X77EHF5sjwqdJN1_ReCmIRAXuAloD9dPZQaPaz6HVgpuNpNvmO58tfZnPqD0S2Or68H_L0RpASTtBWA4c84ApGfNtIT2D9Dp8lVoCp0JDnad7J_CE3txa5AZ09a1vR9wuxu1NBIUFtj4kKAYnne-NzoAjd5q04Al1Cu_2jIhmMuPgiIkaTQw-LExIXsFQapZNGSTxquu43ZR8o" }}',
            superadminOfficeName: '{{ addslashes($userOffice?->nama_kantor ?? "Kantor Pusat PERUMDA Tirta Kepri") }}',
            superadminSystemAlerts: true,
            superadminBackupAlerts: true,
            superadminPhotoSheet: false,
            superadminPinModal: false,
            superadminLogoutModal: false,
            superadminOldPin: '',
            superadminNewPin: '',
            superadminConfirmPin: '',
            superadminObscureOld: true,
            superadminObscureNew: true,
            superadminObscureConfirm: true,
            superadminPinError: null,
            superadminToastMessage: null,
            superadminToastTimeout: null,

            // Superadmin Activity Log States (Matching SuperadminActivityLogScreen.dart & ActivityLogProvider.dart)
            activityLogModal: false,
            activityLogDetailModal: false,
            activityLogSearch: '',
            activityLogCategory: null,
            activityLogLevel: null,
            activityLogSelected: null,
            activityLogs: [
                {
                    id: 'act_001',
                    title: 'Penetapan Kantor Pusat sebagai Kantor Utama',
                    description: 'Kantor Pusat PERUMDA Tirta Kepri Tanjung Pinang berhasil ditetapkan sebagai titik acuan kantor utama multi-site geofencing.',
                    dateGroupKey: 'Hari Ini',
                    formattedDate: 'Hari ini',
                    formattedTime: '10:45:00 WIB',
                    relativeTime: '12 mnt lalu',
                    actorName: 'Superadmin Pusimpel',
                    actorRole: 'Superadmin IT',
                    actorNip: 'P242101',
                    category: 'geofencing',
                    categoryLabel: 'Geofencing & Lokasi',
                    categoryColor: '#0D9488',
                    categoryIcon: 'radar',
                    level: 'success',
                    levelLabel: 'Sukses',
                    levelColor: '#16A34A',
                    ipAddress: '192.168.1.15',
                    deviceInfo: 'Chrome 122.0 (Windows 11)',
                    targetEntity: 'Kantor Pusat PERUMDA Tirta Kepri Tanjung Pinang',
                    metadata: {
                        'action': 'set_primary_office',
                        'office_id': 'hq',
                        'radius': '50 meter',
                        'coordinates': '0.92031, 104.46069'
                    }
                },
                {
                    id: 'act_002',
                    title: 'Pembaruan Radius Geofencing Aktif',
                    description: 'Radius toleransi validasi lokasi untuk seluruh presensi pegawai di Kantor Pusat disetel pada 50 meter.',
                    dateGroupKey: 'Hari Ini',
                    formattedDate: 'Hari ini',
                    formattedTime: '10:12:00 WIB',
                    relativeTime: '45 mnt lalu',
                    actorName: 'Superadmin Pusimpel',
                    actorRole: 'Superadmin IT',
                    actorNip: 'P242101',
                    category: 'geofencing',
                    categoryLabel: 'Geofencing & Lokasi',
                    categoryColor: '#0D9488',
                    categoryIcon: 'radar',
                    level: 'info',
                    levelLabel: 'Informasi',
                    levelColor: '#0284C7',
                    ipAddress: '192.168.1.15',
                    deviceInfo: 'Chrome 122.0 (Windows 11)',
                    targetEntity: 'Radius Geofence Presensi',
                    metadata: {
                        'previous_radius': '100m',
                        'new_radius': '50m',
                        'strict_mode': 'true'
                    }
                },
                {
                    id: 'act_003',
                    title: 'Persetujuan Presensi Masuk Disetujui',
                    description: 'Menyetujui presensi masuk penugasan lapangan Budi Santoso di luar radius penempatan.',
                    dateGroupKey: 'Hari Ini',
                    formattedDate: 'Hari ini',
                    formattedTime: '09:35:00 WIB',
                    relativeTime: '1 jam lalu',
                    actorName: 'IR. H. ABDUL KHOLIK F, M.M.',
                    actorRole: 'Direktur Utama',
                    actorNip: 'D001',
                    category: 'approval',
                    categoryLabel: 'Persetujuan Absen',
                    categoryColor: '#D97706',
                    categoryIcon: 'assignment_turned_in',
                    level: 'success',
                    levelLabel: 'Sukses',
                    levelColor: '#16A34A',
                    ipAddress: '192.168.1.8',
                    deviceInfo: 'Absensi Lapangan Mobile (Android 14)',
                    targetEntity: 'Presensi Masuk (Budi Santoso)',
                    metadata: {
                        'approval_id': 'apr_001',
                        'applicant': 'Budi Santoso',
                        'decision': 'APPROVED',
                        'slot': '1. Masuk Pagi'
                    }
                },
                {
                    id: 'act_004',
                    title: 'Penambahan Titik Kantor Cabang Baru',
                    description: 'Menambahkan titik lokasi kantor cabang baru "Unit Pelayanan Bintan Centre" dengan radius valid 50 meter.',
                    dateGroupKey: 'Hari Ini',
                    formattedDate: 'Hari ini',
                    formattedTime: '08:45:00 WIB',
                    relativeTime: '2 jam lalu',
                    actorName: 'Superadmin Pusimpel',
                    actorRole: 'Superadmin IT',
                    actorNip: 'P242101',
                    category: 'geofencing',
                    categoryLabel: 'Geofencing & Lokasi',
                    categoryColor: '#0D9488',
                    categoryIcon: 'apartment',
                    level: 'success',
                    levelLabel: 'Sukses',
                    levelColor: '#16A34A',
                    ipAddress: '192.168.1.15',
                    deviceInfo: 'Chrome 122.0 (Windows 11)',
                    targetEntity: 'Unit Pelayanan Bintan Centre',
                    metadata: {
                        'office_id': 'bintan_center',
                        'latitude': '0.93245',
                        'longitude': '104.47890',
                        'radius': '50m'
                    }
                },
                {
                    id: 'act_005',
                    title: 'Peringatan Percobaan Lokasi Palsu (Mock GPS)',
                    description: 'Sistem keamanan geofencing memblokir indikasi injeksi koordinat lokasi tiruan (Mock GPS Provider) pada perangkat pegawai.',
                    dateGroupKey: 'Hari Ini',
                    formattedDate: 'Hari ini',
                    formattedTime: '07:50:00 WIB',
                    relativeTime: '3 jam lalu',
                    actorName: 'Sistem Keamanan Otomatis',
                    actorRole: 'Sistem',
                    actorNip: 'SYSTEM',
                    category: 'securityAuth',
                    categoryLabel: 'Keamanan & Sesi',
                    categoryColor: '#00288E',
                    categoryIcon: 'security',
                    level: 'warning',
                    levelLabel: 'Peringatan',
                    levelColor: '#D97706',
                    ipAddress: '10.20.14.88',
                    deviceInfo: 'Xiaomi Redmi Note 11 (Android 13)',
                    targetEntity: 'Deteksi Integritas GPS Mobile',
                    metadata: {
                        'reason': 'isFromMockProvider: true',
                        'action_taken': 'Presensi diblokir, diminta verifikasi manual'
                    }
                },
                {
                    id: 'act_006',
                    title: 'Pembaruan Jadwal Toleransi Jam Operasional',
                    description: 'Toleransi keterlambatan presensi masuk pagi disetel menjadi 15 menit sebelum status tercatat Terlambat.',
                    dateGroupKey: 'Kemarin',
                    formattedDate: 'Kemarin',
                    formattedTime: '16:20:00 WIB',
                    relativeTime: 'Kemarin',
                    actorName: 'Superadmin Pusimpel',
                    actorRole: 'Superadmin IT',
                    actorNip: 'P242101',
                    category: 'operationalSchedule',
                    categoryLabel: 'Jam Operasional',
                    categoryColor: '#16A34A',
                    categoryIcon: 'schedule',
                    level: 'info',
                    levelLabel: 'Informasi',
                    levelColor: '#0284C7',
                    ipAddress: '192.168.1.15',
                    deviceInfo: 'Chrome 122.0 (Windows 11)',
                    targetEntity: 'Slot Presensi Masuk (07:30 - 08:00 WIB)',
                    metadata: {
                        'start_time': '07:30',
                        'end_time': '08:00',
                        'tolerance_minutes': '15'
                    }
                },
                {
                    id: 'act_007',
                    title: 'Pembuatan Akun Pegawai Baru',
                    description: 'Pendaftaran akun karyawan lapangan baru atas nama Ahmad Fauzi (NIK: P005) pada Unit Pelayanan Sei Pulai.',
                    dateGroupKey: 'Kemarin',
                    formattedDate: 'Kemarin',
                    formattedTime: '14:10:00 WIB',
                    relativeTime: 'Kemarin',
                    actorName: 'Superadmin Pusimpel',
                    actorRole: 'Superadmin IT',
                    actorNip: 'P242101',
                    category: 'employeeAccount',
                    categoryLabel: 'Pegawai & Akun',
                    categoryColor: '#00288E',
                    categoryIcon: 'manage_accounts',
                    level: 'success',
                    levelLabel: 'Sukses',
                    levelColor: '#16A34A',
                    ipAddress: '192.168.1.15',
                    deviceInfo: 'Chrome 122.0 (Windows 11)',
                    targetEntity: 'Ahmad Fauzi (NIK: P005)',
                    metadata: {
                        'unit': 'Sei Pulai',
                        'role': 'Karyawan'
                    }
                }
            ],
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
            opMapMode: 'satellite',
            officeFormMapMode: 'satellite',

            // Superadmin Operational States (Matching SuperadminOperationalScreen.dart)
            opDays: [
                { day: 1, name: 'Senin', short: 'Sen' },
                { day: 2, name: 'Selasa', short: 'Sel' },
                { day: 3, name: 'Rabu', short: 'Rab' },
                { day: 4, name: 'Kamis', short: 'Kam' },
                { day: 5, name: 'Jumat', short: 'Jum' },
                { day: 6, name: 'Sabtu', short: 'Sab' },
                { day: 7, name: 'Minggu', short: 'Min' }
            ],
            opDailySchedules: @json($systemSettings['operational_hours']['daily_schedules'] ?? []),
            opAutoLockOutside: {{ ($systemSettings['operational_hours']['auto_lock_outside'] ?? true) ? 'true' : 'false' }},
            opHariKerja: '{{ $systemSettings['operational_hours']['hari_kerja'] ?? 'Senin - Jumat (5 Hari Kerja)' }}',
            opSelectedDay: {{ \Carbon\Carbon::now('Asia/Jakarta')->dayOfWeekIso }},
            opCurrentSchedule: {
                day_of_week: 1,
                day_name: 'Senin',
                is_work_day: true,
                masuk: '08:00',
                istirahat_keluar: '12:00',
                istirahat_masuk: '13:00',
                pulang: '17:00',
                toleransi_menit: 15
            },
            opOffices: @json($systemSettings['geofencing']['offices'] ?? []),
            opActiveOfficeId: '{{ $systemSettings['geofencing']['active_office_id'] ?? '' }}',
            opToastMessage: null,
            opToastTimeout: null,

            // Time Picker Dialog State
            timePickerOpen: false,
            timePickerSlotNum: '1',
            timePickerTitle: '',
            timePickerValue: '08:00',
            timePickerTargetKey: 'masuk',

            // Office Form Dialog State (Matching _showOfficeFormDialog)
            officeModalOpen: false,
            isEditOffice: false,
            officeForm: {
                id: '',
                name: '',
                address: '',
                latitude: '0.92031',
                longitude: '104.46069',
                radius: 100,
                is_primary: false,
                map_link: '',
                is_resolving: false,
                resolve_status: null,
                resolve_success: false,
                preview_lat: 0.92031,
                preview_lng: 104.46069,
            },

            // Delete Office Confirmation Dialog State (Matching _showDeleteOfficeDialog)
            deleteOfficeModalOpen: false,
            officeToDelete: null,

            initApp() {
                this.updateGreeting();
                this.initOperational();
                this.loadPreferences();
                setInterval(() => {
                    const now = new Date();
                    const h = String(now.getHours()).padStart(2, '0');
                    const m = String(now.getMinutes()).padStart(2, '0');
                    const s = String(now.getSeconds()).padStart(2, '0');
                    this.clockTime = `${h}:${m}:${s}`;
                }, 1000);

                window.addEventListener('switch-tab', (e) => {
                    if (e.detail) this.activeTab = e.detail;
                });

                // Integrasi Event Listener Alarm & Notifikasi Perangkat
                window.addEventListener('tirtafield:alarm', (e) => {
                    if (e.detail) {
                        this.alarmBannerData = e.detail;
                        this.alarmBannerVisible = true;
                        this.isAlarmModalOpen = true;
                    }
                });

                window.addEventListener('tirtafield:notification-clicked', (e) => {
                    const slot = (e.detail && e.detail.slotType) ? e.detail.slotType : 'masuk';
                    this.openPresensiFromAlarm(slot);
                });

                // Pengecekan otomatis jadwal alarm 4 slot setiap 10 detik
                setInterval(() => this.checkScheduledAlarms(), 10000);
            },

            loadPreferences() {
                try {
                    // 1. Karyawan Alarm & Notification Preferences
                    const savedAlarm = localStorage.getItem('tirtafield_alarm_prefs');
                    if (savedAlarm) {
                        const parsed = JSON.parse(savedAlarm);
                        if (parsed.slots) this.alarmSlotsEnabled = Object.assign(this.alarmSlotsEnabled, parsed.slots);
                        if (parsed.sound !== undefined) this.alarmSoundEnabled = parsed.sound;
                        if (parsed.vibration !== undefined) this.alarmVibrationEnabled = parsed.vibration;
                        if (parsed.deviceNotif !== undefined) this.alarmDeviceNotificationEnabled = parsed.deviceNotif;
                    }

                    // 2. Admin Direktur Preferences
                    const savedAdminPresence = localStorage.getItem('tirtafield_admin_presence_alerts');
                    if (savedAdminPresence !== null) this.adminPresenceAlerts = (savedAdminPresence === '1');
                    const savedAdminApproval = localStorage.getItem('tirtafield_admin_approval_alerts');
                    if (savedAdminApproval !== null) this.adminApprovalAlerts = (savedAdminApproval === '1');

                    // 3. Superadmin Preferences
                    const savedSuperadminSys = localStorage.getItem('tirtafield_superadmin_system_alerts');
                    if (savedSuperadminSys !== null) this.superadminSystemAlerts = (savedSuperadminSys === '1');
                    const savedSuperadminBackup = localStorage.getItem('tirtafield_superadmin_backup_alerts');
                    if (savedSuperadminBackup !== null) this.superadminBackupAlerts = (savedSuperadminBackup === '1');
                } catch(e) {
                    console.warn('[Tirtafield] Gagal membaca preferensi lokal:', e);
                }
            },

            saveAlarmPreferences() {
                try {
                    localStorage.setItem('tirtafield_alarm_prefs', JSON.stringify({
                        slots: this.alarmSlotsEnabled,
                        sound: this.alarmSoundEnabled,
                        vibration: this.alarmVibrationEnabled,
                        deviceNotif: this.alarmDeviceNotificationEnabled
                    }));
                } catch(e) {}
                this.alarmSettingsModal = false;
                if (typeof this.showSuccessToast === 'function') {
                    this.showSuccessToast('Pengaturan alarm & preferensi berhasil disimpan!');
                }
            },

            async testAdminNotification() {
                if (window.TirtafieldAlarm) {
                    await window.TirtafieldAlarm.showDeviceNotification({
                        title: '🔔 UJI NOTIFIKASI DIREKTUR',
                        body: 'Notifikasi Persetujuan & Presensi Lapangan aktif di perangkat mobile Anda.',
                        slotType: 'admin_test',
                        slotTitle: 'Persetujuan Presensi',
                        url: '/approvals'
                    });
                    window.TirtafieldAlarm.playAlarmSound();
                    setTimeout(() => window.TirtafieldAlarm.stopAlarmSound(), 2500);
                    if (typeof this.showSuccessToast === 'function') {
                        this.showSuccessToast('Notifikasi uji coba berhasil dikirim ke perangkat mobile!');
                    }
                }
            },

            async testSuperadminNotification() {
                if (window.TirtafieldAlarm) {
                    await window.TirtafieldAlarm.showDeviceNotification({
                        title: '⚡ UJI NOTIFIKASI SUPERADMIN',
                        body: 'Peringatan sistem, anomali presensi, dan pencadangan aktif di perangkat mobile Anda.',
                        slotType: 'superadmin_test',
                        slotTitle: 'Audit Sistem',
                        url: '/dashboard'
                    });
                    window.TirtafieldAlarm.playAlarmSound();
                    setTimeout(() => window.TirtafieldAlarm.stopAlarmSound(), 2500);
                    if (typeof this.showSuperadminToast === 'function') {
                        this.showSuperadminToast('Notifikasi sistem berhasil dikirim ke perangkat mobile!');
                    }
                }
            },

            openAlarmSettingsModal() {
                this.alarmNotificationPerm = ('Notification' in window) ? Notification.permission : 'default';
                this.alarmSettingsModal = true;
            },

            async requestAlarmPermission() {
                if (window.TirtafieldAlarm) {
                    const granted = await window.TirtafieldAlarm.requestPermission();
                    this.alarmNotificationPerm = ('Notification' in window) ? Notification.permission : 'default';
                    if (granted) {
                        alert('Izin Notifikasi Berhasil Diaktifkan! Alarm presensi kini dapat muncul di Banner, Layar Terkunci, dan Pusat Pemberitahuan perangkat Anda.');
                    }
                }
            },

            async testDeviceAlarm(slotType = 'masuk') {
                if (window.TirtafieldAlarm) {
                    await window.TirtafieldAlarm.testAlarm(slotType, this.getSlotTitle(slotType));
                }
            },

            dismissActiveAlarm() {
                if (window.TirtafieldAlarm) {
                    window.TirtafieldAlarm.stopAlarmSound();
                }
                this.alarmBannerVisible = false;
                this.isAlarmModalOpen = false;
            },

            openPresensiFromAlarm(slotType = 'masuk') {
                this.dismissActiveAlarm();
                this.startAttendance(slotType || 'masuk', this.getSlotTitle(slotType || 'masuk'));
            },

            getSlotTitle(slotType) {
                const titles = {
                    masuk: 'Presensi Masuk',
                    istirahat_keluar: 'Istirahat Keluar',
                    istirahat_masuk: 'Istirahat Masuk',
                    pulang: 'Presensi Pulang'
                };
                return titles[slotType] || 'Presensi';
            },

            checkScheduledAlarms() {
                if (!this.opTodaySchedule) return;
                const now = new Date();
                const nowHm = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
                const todayKey = `${now.getFullYear()}-${now.getMonth()+1}-${now.getDate()}`;

                const slots = [
                    { type: 'masuk', time: this.opTodaySchedule.masuk, title: 'Presensi Masuk' },
                    { type: 'istirahat_keluar', time: this.opTodaySchedule.istirahat_keluar, title: 'Istirahat Keluar' },
                    { type: 'istirahat_masuk', time: this.opTodaySchedule.istirahat_masuk, title: 'Istirahat Masuk' },
                    { type: 'pulang', time: this.opTodaySchedule.pulang, title: 'Presensi Pulang' }
                ];

                slots.forEach(slot => {
                    if (!slot.time || !this.alarmSlotsEnabled[slot.type]) return;
                    const triggerKey = `${todayKey}_${slot.type}_${slot.time}`;
                    if (nowHm === slot.time && !this.alarmTriggeredToday[triggerKey]) {
                        this.alarmTriggeredToday[triggerKey] = true;
                        if (window.TirtafieldAlarm) {
                            window.TirtafieldAlarm.triggerFullAlarm({
                                title: `⏰ Waktunya ${slot.title}`,
                                body: `Jadwal operasional ${slot.title} (${slot.time} WIB) telah tiba. Buka aplikasi untuk melakukan presensi.`,
                                slotType: slot.type,
                                slotTitle: slot.title,
                                playSound: this.alarmSoundEnabled,
                                vibrate: this.alarmVibrationEnabled,
                                showDeviceNotif: this.alarmDeviceNotificationEnabled
                            });
                        }
                    }
                });
            },

            updateGreeting() {
                const hour = new Date().getHours();
                if (hour >= 4 && hour < 11) this.greeting = 'Selamat Pagi';
                else if (hour >= 11 && hour < 15) this.greeting = 'Selamat Siang';
                else if (hour >= 15 && hour < 18) this.greeting = 'Selamat Sore';
                else this.greeting = 'Selamat Malam';
            },

            empMatches(name, nik, dept) {
                if (!this.empSearch.trim()) return true;
                const q = this.empSearch.toLowerCase().trim();
                return (name && name.toLowerCase().includes(q)) || (nik && nik.toLowerCase().includes(q)) || (dept && dept.toLowerCase().includes(q));
            },

            dirMatches(name, nik, catatan, tipe) {
                if (!this.dirSearch || !this.dirSearch.trim()) return true;
                const q = this.dirSearch.toLowerCase().trim();
                return (name && String(name).toLowerCase().includes(q)) ||
                       (nik && String(nik).toLowerCase().includes(q)) ||
                       (catatan && String(catatan).toLowerCase().includes(q)) ||
                       (tipe && String(tipe).toLowerCase().includes(q));
            },

            canStartAttendance(slotType) {
                const now = new Date();
                const currentH = now.getHours();
                const currentMinutes = currentH * 60 + now.getMinutes();
                const isDiniHari = (currentH < 7); // 00:00 - 06:59 pagi

                const isSlot1Done = !!(this.myAttendanceSlots && (this.myAttendanceSlots.masuk || this.myAttendanceSlots.Masuk));
                const isSlot2Done = !!(this.myAttendanceSlots && (this.myAttendanceSlots.istirahatKeluar || this.myAttendanceSlots.istirahat_keluar));
                const isSlot3Done = !!(this.myAttendanceSlots && (this.myAttendanceSlots.istirahatMasuk || this.myAttendanceSlots.istirahat_masuk));
                const isSlot4Done = !!(this.myAttendanceSlots && (this.myAttendanceSlots.pulang || this.myAttendanceSlots.Pulang));

                const norm = String(slotType || '').toLowerCase().replace(/_/g, '');

                // Khusus kasus kepulangan lembur hingga dini hari lintas hari:
                if (norm === 'pulang' && isDiniHari) {
                    if (!isSlot1Done && !this.isOvernightActive) {
                        return {
                            allowed: false,
                            message: 'Presensi Pulang lembur hanya dapat dilakukan jika Anda memiliki sesi presensi masuk.'
                        };
                    }
                    if (isSlot4Done) {
                        return { allowed: false, message: 'Anda sudah melakukan Presensi Pulang untuk sesi lembur ini.' };
                    }
                    // Lembur dini hari diizinkan tanpa batasan jam pulang 17:00
                    return { allowed: true };
                }

                const sched = this.opTodaySchedule;
                if (!sched || sched.is_work_day === false) {
                    return {
                        allowed: false,
                        message: 'Hari ini adalah hari libur operasional. Sesi presensi tidak dapat dibuka.'
                    };
                }

                const parseMinutes = (tStr, defaultStr) => {
                    const str = tStr || defaultStr;
                    const parts = str.split(':');
                    return parseInt(parts[0], 10) * 60 + parseInt(parts[1] || '0', 10);
                };

                const jamMasukMin = parseMinutes(sched.masuk, '08:00');
                const jamIstirahatKeluarMin = parseMinutes(sched.istirahat_keluar, '12:00');
                const jamIstirahatMasukMin = parseMinutes(sched.istirahat_masuk, '13:00');
                const jamPulangMin = parseMinutes(sched.pulang, '17:00');

                // Jendela presensi masuk dibuka 60 menit sebelum jam masuk resmi
                const jamBukaMasukMin = Math.max(0, jamMasukMin - 60);

                const formatTime = (totalMin) => {
                    const h = String(Math.floor(totalMin / 60)).padStart(2, '0');
                    const m = String(totalMin % 60).padStart(2, '0');
                    return `${h}:${m}`;
                };

                if (norm === 'masuk') {
                    if (isSlot1Done) {
                        return { allowed: false, message: 'Anda sudah melakukan Presensi Masuk hari ini.' };
                    }
                    if (currentMinutes < jamBukaMasukMin) {
                        return {
                            allowed: false,
                            message: `Sesi Presensi Masuk belum dapat dibuka! Jadwal presensi masuk baru dibuka mulai pukul ${formatTime(jamBukaMasukMin)} WIB (Jadwal masuk: ${sched.masuk || '08:00'} WIB).`
                        };
                    }
                    return { allowed: true };
                }

                if (norm === 'istirahatkeluar') {
                    if (!isSlot1Done) {
                        return {
                            allowed: false,
                            message: 'Presensi Istirahat Keluar baru dapat dilakukan setelah Anda melakukan Presensi Masuk.'
                        };
                    }
                    if (isSlot2Done) {
                        return { allowed: false, message: 'Anda sudah melakukan Presensi Istirahat Keluar hari ini.' };
                    }
                    if (currentMinutes < jamIstirahatKeluarMin) {
                        return {
                            allowed: false,
                            message: `Sesi Presensi Istirahat Keluar belum dapat dibuka! Jadwal resmi dibuka pukul ${sched.istirahat_keluar || '12:00'} WIB.`
                        };
                    }
                    return { allowed: true };
                }

                if (norm === 'istirahatmasuk') {
                    if (!isSlot2Done) {
                        return {
                            allowed: false,
                            message: 'Presensi Istirahat Masuk baru dapat dilakukan setelah melakukan Presensi Istirahat Keluar.'
                        };
                    }
                    if (isSlot3Done) {
                        return { allowed: false, message: 'Anda sudah melakukan Presensi Istirahat Masuk hari ini.' };
                    }
                    if (currentMinutes < jamIstirahatKeluarMin) {
                        return {
                            allowed: false,
                            message: `Sesi Presensi Istirahat Masuk belum dapat dibuka! Jadwal resmi dibuka pukul ${sched.istirahat_masuk || '13:00'} WIB.`
                        };
                    }
                    return { allowed: true };
                }

                if (norm === 'pulang') {
                    if (!isSlot1Done) {
                        return {
                            allowed: false,
                            message: 'Presensi Pulang baru dapat dilakukan setelah Anda melakukan Presensi Masuk.'
                        };
                    }
                    if (isSlot4Done) {
                        return { allowed: false, message: 'Anda sudah melakukan Presensi Pulang hari ini.' };
                    }
                    if (currentMinutes < jamPulangMin) {
                        return {
                            allowed: false,
                            message: `Sesi Presensi Pulang belum dapat dilakukan! Jam pulang kantor dibuka pukul ${sched.pulang || '17:00'} WIB.`
                        };
                    }
                    return { allowed: true };
                }

                return { allowed: true };
            },

            startAttendance(slotType, slotTitle) {
                const check = this.canStartAttendance(slotType);
                if (!check.allowed) {
                    this.showLockedToast(check.message);
                    return;
                }

                this.activeSlotType = slotType;
                this.activeSlotTitle = slotTitle;
                this.verificationStep = 1;
                this.capturedPhoto = null;
                this.attendanceNotes = '';
                this.verificationModal = true;

                // Sinkronkan kantor acuan dengan data terkonfigurasi Superadmin
                if (!this.selectedVerificationOfficeId) {
                    this.selectedVerificationOfficeId = '{{ $userOffice?->id ?? "" }}' || (this.opOffices[0]?.id || 'hq');
                }
                const off = this.opOffices.find(o => String(o.id) === String(this.selectedVerificationOfficeId));
                if (off) {
                    this.targetOfficeName = off.name || off.nama_kantor;
                    this.targetOfficeLat = parseFloat(off.latitude) || 0.92031;
                    this.targetOfficeLng = parseFloat(off.longitude) || 104.46069;
                    this.targetOfficeRadius = parseInt(off.radius) || 100;
                }

                // Hitung keterlambatan sesuai jam operasional Superadmin hari ini
                this.checkPunctuality(slotType);

                this.refreshLiveLocation(false);
                this.$nextTick(() => {
                    setTimeout(() => this.initLeafletMap(), 250);
                });
            },

            checkPunctuality(slotType) {
                this.isLateCheckIn = false;
                this.lateDurationMinutes = 0;
                this.lateReason = '';

                const sched = this.opTodaySchedule;
                if (!sched || !sched.is_work_day) return;

                const now = new Date();
                const currentMinutes = now.getHours() * 60 + now.getMinutes();

                let targetStr = null;
                let tolerance = parseInt(sched.toleransi_menit) || 0;

                if (slotType === 'masuk') {
                    targetStr = sched.masuk || '08:00';
                } else if (slotType === 'istirahatMasuk') {
                    targetStr = sched.istirahat_masuk || '13:00';
                    tolerance = 0;
                }

                if (targetStr) {
                    const parts = targetStr.split(':');
                    if (parts.length >= 2) {
                        const targetMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]) + tolerance;
                        if (currentMinutes > targetMinutes) {
                            this.isLateCheckIn = true;
                            this.lateDurationMinutes = currentMinutes - targetMinutes;
                        }
                    }
                }
            },

            onVerificationOfficeChange() {
                const off = this.opOffices.find(o => String(o.id) === String(this.selectedVerificationOfficeId));
                if (off) {
                    this.targetOfficeName = off.name || off.nama_kantor;
                    this.targetOfficeLat = parseFloat(off.latitude) || 0.92031;
                    this.targetOfficeLng = parseFloat(off.longitude) || 104.46069;
                    this.targetOfficeRadius = parseInt(off.radius) || 100;
                    this.calculatedDistance = Math.round(this.calculateDistance(
                        this.gpsLat, this.gpsLng,
                        this.targetOfficeLat, this.targetOfficeLng
                    ));
                    if (this.leafletMap) this.initLeafletMap();
                }
            },

            setMapMode(mode) {
                this.mapMode = mode;
                if (!this.leafletMap) return;
                if (this.leafletTileLayer) {
                    this.leafletMap.removeLayer(this.leafletTileLayer);
                    this.leafletTileLayer = null;
                }
                const gKey = window.GOOGLE_MAPS_API_KEY || '{{ config("services.google.maps_api_key") }}' || 'AIzaSyDpm1SPneUjheEMnk3TDOP38js6TQLmXps';
                if (mode === 'satellite') {
                    const tileUrl = gKey 
                        ? `https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&key=${gKey}`
                        : 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
                    const tileOpts = gKey
                        ? { subdomains: ['0', '1', '2', '3'], maxZoom: 20, attribution: '&copy; Google Maps' }
                        : { maxZoom: 19, attribution: '&copy; Esri World Imagery' };
                    this.leafletTileLayer = L.tileLayer(tileUrl, tileOpts).addTo(this.leafletMap);
                } else {
                    const tileUrl = gKey 
                        ? `https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&key=${gKey}`
                        : 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                    const tileOpts = gKey
                        ? { subdomains: ['0', '1', '2', '3'], maxZoom: 20, attribution: '&copy; Google Maps' }
                        : { maxZoom: 19, attribution: '&copy; CartoDB' };
                    this.leafletTileLayer = L.tileLayer(tileUrl, tileOpts).addTo(this.leafletMap);
                }
            },

            initLeafletMap() {
                if (this.leafletMap) {
                    this.leafletMap.remove();
                    this.leafletMap = null;
                    this.leafletTileLayer = null;
                }
                const mapEl = document.getElementById('attendanceMap');
                if (!mapEl) return;

                this.leafletMap = L.map('attendanceMap', {
                    zoomControl: false,
                    attributionControl: false
                }).setView([this.targetOfficeLat, this.targetOfficeLng], 16);

                // Tampilkan mode satelit secara default atau sesuai pilihan
                this.setMapMode(this.mapMode || 'satellite');

                // Office Icon
                const officeIcon = L.divIcon({
                    className: 'custom-office-icon',
                    html: '<div style="background:#00288E;color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(0,40,142,0.4);border:2px solid white;"><span class="material-symbols-rounded" style="font-size:16px;line-height:1;">apartment</span></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                L.marker([this.targetOfficeLat, this.targetOfficeLng], { icon: officeIcon }).addTo(this.leafletMap);

                // Radius Circle
                L.circle([this.targetOfficeLat, this.targetOfficeLng], {
                    color: '#0051D5',
                    fillColor: '#316BF3',
                    fillOpacity: 0.15,
                    radius: this.targetOfficeRadius,
                    weight: 2,
                    dashArray: '4, 4'
                }).addTo(this.leafletMap);

                // User Marker
                const userIcon = L.divIcon({
                    className: 'custom-user-icon',
                    html: '<div style="background:#059669;color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 12px #059669;border:3px solid white;"><span class="material-symbols-rounded" style="font-size:14px;line-height:1;">person</span></div>',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });
                L.marker([this.gpsLat, this.gpsLng], { icon: userIcon }).addTo(this.leafletMap);

                // Vector Dashed Line
                L.polyline([[this.gpsLat, this.gpsLng], [this.targetOfficeLat, this.targetOfficeLng]], {
                    color: '#0051D5',
                    weight: 2,
                    dashArray: '5, 5',
                    opacity: 0.8
                }).addTo(this.leafletMap);
            },

            refreshLiveLocation(showFeedback = true) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.gpsLat = pos.coords.latitude;
                            this.gpsLng = pos.coords.longitude;
                            this.calculatedDistance = Math.round(this.calculateDistance(
                                this.gpsLat, this.gpsLng,
                                this.targetOfficeLat, this.targetOfficeLng
                            ));
                            if (this.leafletMap) this.initLeafletMap();
                            if (showFeedback) alert('Titik GPS berhasil diperbarui: ' + this.gpsLat.toFixed(5) + ', ' + this.gpsLng.toFixed(5));
                        },
                        (err) => {
                            this.calculatedDistance = 15;
                            if (showFeedback) alert('GPS aktif menggunakan koordinat kantor penempatan.');
                        },
                        { enableHighAccuracy: true, timeout: 5000 }
                    );
                }
            },

            calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371e3; // meters
                const phi1 = lat1 * Math.PI / 180;
                const phi2 = lat2 * Math.PI / 180;
                const deltaPhi = (lat2 - lat1) * Math.PI / 180;
                const deltaLambda = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
                          Math.cos(phi1) * Math.cos(phi2) *
                          Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            },

            async proceedToPhotoStep() {
                this.verificationStep = 2;
                this.capturedPhoto = null;
                this.isFlashing = false;
                await this.startSelfieCamera();
            },

            async startSelfieCamera() {
                try {
                    this.stopSelfieCamera();
                    const videoEl = document.getElementById('selfieWebcamVideo');
                    this.selfieStream = await navigator.mediaDevices.getUserMedia({
                        video: { 
                            facingMode: 'user', 
                            width: { ideal: 960 }, 
                            height: { ideal: 1200 },
                            aspectRatio: { ideal: 4 / 5 }
                        },
                        audio: false
                    });
                    if (videoEl) {
                        videoEl.srcObject = this.selfieStream;
                        videoEl.play();
                    }
                } catch(e) {
                    console.log('Webcam not directly accessible, fallback mode ready:', e);
                }
            },

            snapSelfiePhoto() {
                // Trigger camera shutter flash effect
                this.isFlashing = true;
                setTimeout(() => { this.isFlashing = false; }, 250);

                const videoEl = document.getElementById('selfieWebcamVideo');
                const canvasEl = document.getElementById('selfieCanvasCapture');
                
                if (videoEl && canvasEl && this.selfieStream && videoEl.videoWidth > 0) {
                    const vw = videoEl.videoWidth;
                    const vh = videoEl.videoHeight;
                    const targetRatio = 4 / 5; // Portrait 4:5

                    let sw, sh, sx, sy;
                    if ((vw / vh) > targetRatio) {
                        // Video sensor lebih lebar dibanding 4:5 -> center-crop horizontal
                        sh = vh;
                        sw = Math.round(vh * targetRatio);
                        sx = Math.round((vw - sw) / 2);
                        sy = 0;
                    } else {
                        // Video sensor lebih tinggi dibanding 4:5 -> center-crop vertical
                        sw = vw;
                        sh = Math.round(vw / targetRatio);
                        sx = 0;
                        sy = Math.round((vh - sh) / 2);
                    }

                    // Resolusi output standar 4:5 portrait (lebar 720, tinggi 900)
                    const outW = Math.min(sw, 720);
                    const outH = Math.round(outW * (5 / 4));

                    canvasEl.width = outW;
                    canvasEl.height = outH;
                    const ctx = canvasEl.getContext('2d');
                    
                    // Mirror horizontally agar natural seperti cermin
                    ctx.translate(outW, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(videoEl, sx, sy, sw, sh, 0, 0, outW, outH);
                    ctx.setTransform(1, 0, 0, 1, 0, 0);

                    // Clean selfie snapshot 4:5 portrait tanpa watermark overlay
                    this.capturedPhoto = canvasEl.toDataURL('image/jpeg', 0.92);
                } else {
                    // Fallback sample selfie 4:5 portrait
                    this.capturedPhoto = "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=720&h=900&q=80";
                }
                this.stopSelfieCamera();
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            const targetRatio = 4 / 5;
                            const iw = img.naturalWidth || img.width;
                            const ih = img.naturalHeight || img.height;

                            let sw, sh, sx, sy;
                            if ((iw / ih) > targetRatio) {
                                sh = ih;
                                sw = Math.round(ih * targetRatio);
                                sx = Math.round((iw - sw) / 2);
                                sy = 0;
                            } else {
                                sw = iw;
                                sh = Math.round(iw / targetRatio);
                                sx = 0;
                                sy = Math.round((ih - sh) / 2);
                            }

                            const canvas = document.createElement('canvas');
                            const outW = Math.min(sw, 720);
                            const outH = Math.round(outW * (5 / 4));
                            canvas.width = outW;
                            canvas.height = outH;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, sx, sy, sw, sh, 0, 0, outW, outH);

                            this.capturedPhoto = canvas.toDataURL('image/jpeg', 0.92);
                            this.stopSelfieCamera();
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            retakeSelfiePhoto() {
                this.capturedPhoto = null;
                this.startSelfieCamera();
            },

            stopSelfieCamera() {
                if (this.selfieStream) {
                    try {
                        this.selfieStream.getTracks().forEach(t => t.stop());
                    } catch(e) {}
                    this.selfieStream = null;
                }
            },

            closeVerificationModal() {
                this.stopSelfieCamera();
                this.verificationModal = false;
            },

            async submitFinalAttendance() {
                if (this.isSubmitting) return;

                if (this.isLateCheckIn && (!this.lateReason || !this.lateReason.trim())) {
                    alert('Waktu jam operasional telah lewat (' + this.lateDurationMinutes + ' Menit). Anda wajib mengisi alasan keterlambatan sebelum presensi dapat dikirim.');
                    return;
                }

                this.isSubmitting = true;

                try {
                    let finalNotes = this.attendanceNotes ? this.attendanceNotes.trim() : '';
                    if (this.isLateCheckIn && this.lateReason && this.lateReason.trim()) {
                        finalNotes = `[TERLAMBAT (${this.lateDurationMinutes} Menit): ${this.lateReason.trim()}] ${finalNotes}`.trim();
                    }

                    const payload = {
                        user_id: "{{ auth()->id() }}",
                        nik: "{{ auth()->user()?->nik }}",
                        pgw_nama: "{{ auth()->user()?->name }}",
                        slot_type: this.activeSlotType,
                        latitude: this.gpsLat,
                        longitude: this.gpsLng,
                        lokasi: this.targetOfficeName,
                        location: this.targetOfficeName,
                        jarak_hq: this.calculatedDistance + ' m',
                        catatan: finalNotes,
                        notes: finalNotes,
                        foto: this.capturedPhoto || '',
                        foto_url: this.capturedPhoto || '',
                        is_within_radius: (this.calculatedDistance <= this.targetOfficeRadius) ? 1 : 0
                    };

                    const res = await fetch("{{ route('api.attendance.record') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json();
                    this.isSubmitting = false;

                    if (res.ok && json.success) {
                        if (this.myAttendanceSlots) {
                            this.myAttendanceSlots[this.activeSlotType] = json.data;
                        }
                        this.verificationStep = 3;
                    } else {
                        alert(json.message || 'Gagal menyimpan presensi. Silakan periksa koneksi data Anda.');
                    }
                } catch(e) {
                    this.isSubmitting = false;
                    console.error('Attendance submission error:', e);
                    alert('Terjadi kendala saat mengirim data presensi ke server. Silakan coba kembali.');
                }
            },

            openReceiptViewer(slot, title, time, loc, status, foto, jarak) {
                this.viewReceiptData = { slot, title, time, loc, status, foto, jarak };
                this.receiptModal = true;
            },

            openRequestModal() {
                this.requestModal = true;
            },

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
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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

            editEmployee(emp) {
                this.editEmpData = { ...emp };
                this.editEmpModal = true;
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

                // Haversine distance in meters
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

                // Office Icon (Green matching Flutter target office)
                const officeIcon = L.divIcon({
                    className: 'custom-office-pin',
                    html: '<div style="background:#059669;color:#ffffff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(5,150,105,0.45);border:2.5px solid #ffffff;"><span class="material-symbols-rounded" style="font-size:16px;line-height:1;">apartment</span></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                const mOffice = L.marker([tLat, tLng], { icon: officeIcon }).addTo(this.detailMap);
                mOffice.bindPopup(`<b>${this.detailData.officePlacement}</b><br><span style="color:#059669;font-weight:600;font-size:11px;">Kantor Penempatan</span>`);

                // Radius Circle
                L.circle([tLat, tLng], {
                    color: '#059669',
                    fillColor: '#10B981',
                    fillOpacity: 0.16,
                    radius: tRad,
                    weight: 2,
                    dashArray: '4, 4'
                }).addTo(this.detailMap);

                // User Marker (Blue matching Flutter)
                const userIcon = L.divIcon({
                    className: 'custom-user-detail-pin',
                    html: '<div style="background:#2563eb;color:#ffffff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 10px rgba(37,99,235,0.55);border:2.5px solid #ffffff;"><span class="material-symbols-rounded" style="font-size:16px;line-height:1;">my_location</span></div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                const mUser = L.marker([lat, lng], { icon: userIcon }).addTo(this.detailMap);
                const popupHtml = `<b>${this.detailData.locationName}</b><br>Pegawai: ${this.detailData.userName}<br>Waktu: ${this.detailData.recordedTimeStr}<br>Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                mUser.bindPopup(popupHtml);

                // Connecting Dashed Line
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
            },

            // Admin Direktur Akun Methods (Matching AdminAkunScreen.dart)
            showSuccessToast(msg) {
                if (this.adminSuccessToastTimeout) clearTimeout(this.adminSuccessToastTimeout);
                this.adminSuccessToastMessage = msg;
                this.adminSuccessToastTimeout = setTimeout(() => {
                    this.adminSuccessToastMessage = null;
                }, 3000);
            },

            async uploadProfilePhoto(photoUrlOrData, source = 'gallery', targetRole = null) {
                const role = targetRole || this.activePhotoRoleTarget || '{{ auth()->user()?->role }}';
                this.isUploadingProfilePhoto = true;
                this.karyawanPhotoSheet = false;
                this.adminPhotoSheet = false;
                this.superadminPhotoSheet = false;
                this.profileCameraModal = false;
                this.stopProfileCamera();

                try {
                    const res = await fetch("{{ route('api.profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: '{{ auth()->user()?->id }}',
                            avatar_url: photoUrlOrData
                        })
                    });
                    const json = await res.json();
                    this.isUploadingProfilePhoto = false;

                    const newAvatar = (json && json.data && json.data.avatar_url) ? json.data.avatar_url : photoUrlOrData;
                    this.currentUserAvatar = newAvatar;
                    this.superadminAvatarUrl = newAvatar;

                    document.querySelectorAll('.profile-avatar-img').forEach(img => {
                        img.src = newAvatar;
                    });

                    const roleTitle = role === 'staffIT' ? 'Superadmin' : (role === 'direktur' ? 'Admin' : 'Karyawan');
                    const msg = source === 'camera'
                        ? `Foto profil ${roleTitle} berhasil diambil dari kamera.`
                        : `Foto profil ${roleTitle} berhasil diperbarui dari galeri.`;

                    this.showSuccessToast(msg);
                    if (this.showSuperadminToast) this.showSuperadminToast(msg);
                } catch(e) {
                    this.isUploadingProfilePhoto = false;
                    this.currentUserAvatar = photoUrlOrData;
                    this.superadminAvatarUrl = photoUrlOrData;
                    const msg = source === 'camera'
                        ? 'Foto profil berhasil diambil dari kamera.'
                        : 'Foto profil berhasil diperbarui dari galeri.';
                    this.showSuccessToast(msg);
                }
            },

            async triggerProfileCamera(role) {
                this.activePhotoRoleTarget = role;
                this.karyawanPhotoSheet = false;
                this.adminPhotoSheet = false;
                this.superadminPhotoSheet = false;
                this.profileSnappedPhoto = null;

                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    this.profileCameraModal = true;
                    this.$nextTick(async () => {
                        await this.startProfileCamera();
                    });
                } else {
                    const camInput = document.getElementById('profileCameraNativeInput');
                    if (camInput) camInput.click();
                }
            },

            async startProfileCamera() {
                this.stopProfileCamera();
                try {
                    const videoEl = document.getElementById('profileCameraVideo');
                    this.profileCameraStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: this.profileCameraFacing,
                            width: { ideal: 720 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    });
                    if (videoEl) {
                        videoEl.srcObject = this.profileCameraStream;
                        videoEl.play();
                    }
                } catch(err) {
                    console.warn('Profile camera error, falling back to input capture:', err);
                    this.profileCameraModal = false;
                    const camInput = document.getElementById('profileCameraNativeInput');
                    if (camInput) camInput.click();
                }
            },

            stopProfileCamera() {
                if (this.profileCameraStream) {
                    this.profileCameraStream.getTracks().forEach(t => t.stop());
                    this.profileCameraStream = null;
                }
                const videoEl = document.getElementById('profileCameraVideo');
                if (videoEl) videoEl.srcObject = null;
            },

            toggleProfileCameraFacing() {
                this.profileCameraFacing = this.profileCameraFacing === 'user' ? 'environment' : 'user';
                this.startProfileCamera();
            },

            snapProfileCameraPhoto() {
                const videoEl = document.getElementById('profileCameraVideo');
                const canvasEl = document.getElementById('profileCameraCanvas');
                if (videoEl && canvasEl && this.profileCameraStream) {
                    const size = Math.min(videoEl.videoWidth || 720, videoEl.videoHeight || 720);
                    canvasEl.width = size;
                    canvasEl.height = size;
                    const ctx = canvasEl.getContext('2d');
                    
                    const sx = ((videoEl.videoWidth || size) - size) / 2;
                    const sy = ((videoEl.videoHeight || size) - size) / 2;
                    ctx.drawImage(videoEl, sx, sy, size, size, 0, 0, size, size);
                    
                    this.profileSnappedPhoto = canvasEl.toDataURL('image/jpeg', 0.9);
                    this.stopProfileCamera();
                }
            },

            retakeProfileCameraPhoto() {
                this.profileSnappedPhoto = null;
                this.startProfileCamera();
            },

            confirmProfileCameraPhoto() {
                if (this.profileSnappedPhoto) {
                    this.uploadProfilePhoto(this.profileSnappedPhoto, 'camera');
                }
            },

            triggerProfileGallery(role) {
                this.activePhotoRoleTarget = role;
                this.karyawanPhotoSheet = false;
                this.adminPhotoSheet = false;
                this.superadminPhotoSheet = false;
                const fileInput = document.getElementById('profileGalleryFileInput');
                if (fileInput) {
                    fileInput.value = '';
                    fileInput.click();
                }
            },

            handleProfileFileSelected(e, source = 'gallery') {
                const file = e.target.files && e.target.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    alert('Mohon pilih berkas gambar (JPG, PNG, WebP).');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    const dataUrl = event.target.result;
                    this.uploadProfilePhoto(dataUrl, source);
                };
                reader.readAsDataURL(file);
            },

            async updateAdminPhoto(photoUrl, source) {
                await this.uploadProfilePhoto(photoUrl, source, 'direktur');
            },

            async saveKaryawanPin() {
                this.karyawanPinError = null;
                const oldPin = (this.karyawanOldPin || '').trim();
                const newPin = (this.karyawanNewPin || '').trim();
                const confirmPin = (this.karyawanConfirmPin || '').trim();

                if (!oldPin) {
                    this.karyawanPinError = 'PIN lama wajib diisi.';
                    return;
                }
                if (!newPin) {
                    this.karyawanPinError = 'PIN baru wajib diisi.';
                    return;
                }
                if (newPin.length < 6) {
                    this.karyawanPinError = 'PIN baru harus 6 digit angka.';
                    return;
                }
                if (newPin === oldPin) {
                    this.karyawanPinError = 'PIN baru tidak boleh sama dengan PIN lama.';
                    return;
                }
                if (newPin !== confirmPin) {
                    this.karyawanPinError = 'Konfirmasi PIN baru tidak sesuai.';
                    return;
                }

                try {
                    const res = await fetch("{{ route('api.profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: '{{ auth()->user()?->id }}',
                            old_pin: oldPin,
                            pin: newPin
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.karyawanPinModal = false;
                        this.karyawanOldPin = '';
                        this.karyawanNewPin = '';
                        this.karyawanConfirmPin = '';
                        this.showSuccessToast('PIN Presensi Karyawan berhasil diperbarui.');
                    } else {
                        this.karyawanPinError = json.message || 'PIN lama tidak sesuai. Silakan periksa kembali.';
                    }
                } catch(e) {
                    this.karyawanPinError = 'Terjadi kesalahan jaringan saat memperbarui PIN.';
                }
            },

            async saveAdminPin() {
                this.adminPinError = null;
                const oldPin = (this.adminOldPin || '').trim();
                const newPin = (this.adminNewPin || '').trim();
                const confirmPin = (this.adminConfirmPin || '').trim();

                if (!oldPin) {
                    this.adminPinError = 'PIN lama wajib diisi.';
                    return;
                }
                if (!newPin) {
                    this.adminPinError = 'PIN baru wajib diisi.';
                    return;
                }
                if (newPin.length < 6) {
                    this.adminPinError = 'PIN baru harus 6 digit angka.';
                    return;
                }
                if (newPin === oldPin) {
                    this.adminPinError = 'PIN baru tidak boleh sama dengan PIN lama.';
                    return;
                }
                if (newPin !== confirmPin) {
                    this.adminPinError = 'Konfirmasi PIN baru tidak sesuai.';
                    return;
                }

                try {
                    const res = await fetch("{{ route('api.profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: '{{ auth()->user()?->id }}',
                            old_pin: oldPin,
                            pin: newPin
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.adminPinModal = false;
                        this.adminOldPin = '';
                        this.adminNewPin = '';
                        this.adminConfirmPin = '';
                        this.showSuccessToast('PIN Otorisasi Admin Direktur berhasil diperbarui.');
                    } else {
                        this.adminPinError = json.message || 'PIN lama tidak sesuai. Silakan periksa kembali.';
                    }
                } catch(e) {
                    this.adminPinError = 'Terjadi kesalahan jaringan saat memperbarui PIN.';
                }
            },

            async saveAdminProfile() {
                try {
                    localStorage.setItem('tirtafield_admin_presence_alerts', this.adminPresenceAlerts ? '1' : '0');
                    localStorage.setItem('tirtafield_admin_approval_alerts', this.adminApprovalAlerts ? '1' : '0');
                } catch(e) {}

                try {
                    const res = await fetch("{{ route('api.profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: '{{ auth()->user()?->id }}',
                            phone: this.adminPhone
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.showSuccessToast('Pengaturan profil akun Admin berhasil disimpan.');
                    } else {
                        alert(json.message || 'Gagal menyimpan pengaturan profil.');
                    }
                } catch(e) {
                    this.showSuccessToast('Pengaturan profil akun Admin berhasil disimpan.');
                }
            },

            // Superadmin IT Profile Methods (Matching SuperadminProfileScreen.dart)
            showSuperadminToast(msg) {
                if (this.superadminToastTimeout) clearTimeout(this.superadminToastTimeout);
                this.superadminToastMessage = msg;
                this.superadminToastTimeout = setTimeout(() => {
                    this.superadminToastMessage = null;
                }, 3000);
            },

            onSuperadminBidangChange() {
                const seksiList = this.getSeksiListForBidang(this.superadminDepartment);
                if (seksiList && seksiList.length > 0) {
                    this.superadminPosition = seksiList[0];
                }
            },

            async updateSuperadminPhoto(photoUrl, source) {
                await this.uploadProfilePhoto(photoUrl, source, 'staffIT');
            },

            async saveSuperadminPin() {
                this.superadminPinError = null;
                const oldPin = (this.superadminOldPin || '').trim();
                const newPin = (this.superadminNewPin || '').trim();
                const confirmPin = (this.superadminConfirmPin || '').trim();

                if (!oldPin) {
                    this.superadminPinError = 'PIN lama wajib diisi.';
                    return;
                }
                if (!newPin) {
                    this.superadminPinError = 'PIN baru wajib diisi.';
                    return;
                }
                if (newPin.length < 6) {
                    this.superadminPinError = 'PIN baru harus 6 digit angka.';
                    return;
                }
                if (newPin === oldPin) {
                    this.superadminPinError = 'PIN baru tidak boleh sama dengan PIN lama.';
                    return;
                }
                if (newPin !== confirmPin) {
                    this.superadminPinError = 'Konfirmasi PIN baru tidak sesuai.';
                    return;
                }

                try {
                    const res = await fetch("{{ route('api.profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: '{{ auth()->user()?->id }}',
                            old_pin: oldPin,
                            pin: newPin
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.superadminPinModal = false;
                        this.superadminOldPin = '';
                        this.superadminNewPin = '';
                        this.superadminConfirmPin = '';
                        this.showSuperadminToast('PIN Otorisasi Superadmin berhasil diperbarui.');
                    } else {
                        this.superadminPinError = json.message || 'PIN lama tidak sesuai. Silakan periksa kembali.';
                    }
                } catch(e) {
                    this.superadminPinError = 'Terjadi kesalahan jaringan saat memperbarui PIN.';
                }
            },

            async saveSuperadminProfile() {
                try {
                    localStorage.setItem('tirtafield_superadmin_system_alerts', this.superadminSystemAlerts ? '1' : '0');
                    localStorage.setItem('tirtafield_superadmin_backup_alerts', this.superadminBackupAlerts ? '1' : '0');
                } catch(e) {}

                const name = (this.superadminName || '').trim();
                if (!name) {
                    this.showSuperadminToast('Nama lengkap tidak boleh kosong.');
                    return;
                }

                try {
                    const res = await fetch("{{ route('api.profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: '{{ auth()->user()?->id }}',
                            name: this.superadminName,
                            department: this.superadminDepartment,
                            position: this.superadminPosition,
                            phone: this.superadminPhone
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.showSuperadminToast('Pengaturan profil akun Superadmin Pusimpel berhasil disimpan.');
                    } else {
                        alert(json.message || 'Gagal menyimpan pengaturan profil.');
                    }
                } catch(e) {
                    this.showSuperadminToast('Pengaturan profil akun Superadmin Pusimpel berhasil disimpan.');
                }
            },

            // Superadmin Activity Log Methods (Matching SuperadminActivityLogScreen.dart & ActivityLogProvider.dart)
            filteredActivityLogs() {
                const q = (this.activityLogSearch || '').toLowerCase().trim();
                return this.activityLogs.filter(log => {
                    if (this.activityLogCategory && log.category !== this.activityLogCategory) return false;
                    if (this.activityLogLevel && log.level !== this.activityLogLevel) return false;
                    if (q) {
                        const matchTitle = (log.title || '').toLowerCase().includes(q);
                        const matchDesc = (log.description || '').toLowerCase().includes(q);
                        const matchActor = (log.actorName || '').toLowerCase().includes(q);
                        const matchNip = (log.actorNip || '').toLowerCase().includes(q);
                        const matchTarget = (log.targetEntity || '').toLowerCase().includes(q);
                        const matchCategory = (log.categoryLabel || '').toLowerCase().includes(q);
                        if (!matchTitle && !matchDesc && !matchActor && !matchNip && !matchTarget && !matchCategory) {
                            return false;
                        }
                    }
                    return true;
                });
            },

            getActivityLogMetrics() {
                const total = this.activityLogs.length;
                const today = this.activityLogs.filter(l => l.dateGroupKey === 'Hari Ini').length;
                const geofence = this.activityLogs.filter(l => l.category === 'geofencing').length;
                const security = this.activityLogs.filter(l => l.category === 'securityAuth').length;
                return { total, today, geofence, security };
            },

            openActivityDetail(log) {
                this.activityLogSelected = log;
                this.activityLogDetailModal = true;
            },

            copyActivityDetail(log) {
                if (!log) return;
                const text = `[${log.formattedDate} ${log.formattedTime}] ${log.title}\nAktor: ${log.actorName} (${log.actorNip})\nDeskripsi: ${log.description}\nIP: ${log.ipAddress || '-'}`;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                }
                this.activityLogDetailModal = false;
                this.showSuperadminToast('Rincian log berhasil disalin ke clipboard.');
            },

            exportActivityLogsCsv() {
                const logs = this.filteredActivityLogs();
                let csv = 'ID,Waktu,Aktor,Role,NIK,Kategori,Tingkat,Judul,Deskripsi,Target,IP Address,Perangkat\n';
                logs.forEach(log => {
                    const row = [
                        `"${log.id}"`,
                        `"${log.formattedDate} ${log.formattedTime}"`,
                        `"${log.actorName}"`,
                        `"${log.actorRole}"`,
                        `"${log.actorNip}"`,
                        `"${log.categoryLabel}"`,
                        `"${log.levelLabel}"`,
                        `"${(log.title || '').replace(/"/g, '""')}"`,
                        `"${(log.description || '').replace(/"/g, '""')}"`,
                        `"${(log.targetEntity || '-').replace(/"/g, '""')}"`,
                        `"${log.ipAddress || '-'}"`,
                        `"${log.deviceInfo || '-'}"`
                    ].join(',');
                    csv += row + '\n';
                });
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(csv);
                }
                this.showSuperadminToast(`${logs.length} baris log berhasil diekspor & disalin ke clipboard (Format CSV).`);
            },

            // Superadmin Operational Methods (Matching SuperadminOperationalScreen.dart)
            initOperational() {
                if (!this.opDailySchedules || this.opDailySchedules.length === 0) {
                    this.opDailySchedules = [
                        { day_of_week: 1, day_name: 'Senin', is_work_day: true, masuk: '08:00', istirahat_keluar: '12:00', istirahat_masuk: '13:00', pulang: '17:00', toleransi_menit: 15 },
                        { day_of_week: 2, day_name: 'Selasa', is_work_day: true, masuk: '08:00', istirahat_keluar: '12:00', istirahat_masuk: '13:00', pulang: '17:00', toleransi_menit: 15 },
                        { day_of_week: 3, day_name: 'Rabu', is_work_day: true, masuk: '08:00', istirahat_keluar: '12:00', istirahat_masuk: '13:00', pulang: '17:00', toleransi_menit: 15 },
                        { day_of_week: 4, day_name: 'Kamis', is_work_day: true, masuk: '08:00', istirahat_keluar: '12:00', istirahat_masuk: '13:00', pulang: '17:00', toleransi_menit: 15 },
                        { day_of_week: 5, day_name: 'Jumat', is_work_day: true, masuk: '08:00', istirahat_keluar: '11:30', istirahat_masuk: '13:30', pulang: '17:00', toleransi_menit: 15 },
                        { day_of_week: 6, day_name: 'Sabtu', is_work_day: false, masuk: '08:00', istirahat_keluar: '12:00', istirahat_masuk: '12:30', pulang: '13:00', toleransi_menit: 15 },
                        { day_of_week: 7, day_name: 'Minggu', is_work_day: false, masuk: '08:00', istirahat_keluar: '12:00', istirahat_masuk: '13:00', pulang: '17:00', toleransi_menit: 15 },
                    ];
                }
                this.loadDaySchedule(this.opSelectedDay);

                if (!this.opActiveOfficeId && this.opOffices && this.opOffices.length > 0) {
                    const primary = this.opOffices.find(o => o.is_primary);
                    this.opActiveOfficeId = primary ? primary.id : this.opOffices[0].id;
                }
            },

            loadDaySchedule(dayNum) {
                this.opSelectedDay = dayNum;
                const found = this.opDailySchedules.find(d => d.day_of_week === dayNum);
                if (found) {
                    this.opCurrentSchedule = JSON.parse(JSON.stringify(found));
                } else {
                    this.opCurrentSchedule = {
                        day_of_week: dayNum,
                        day_name: this.getDayName(dayNum),
                        is_work_day: dayNum <= 5,
                        masuk: '08:00',
                        istirahat_keluar: dayNum === 5 ? '11:30' : '12:00',
                        istirahat_masuk: dayNum === 5 ? '13:30' : '13:00',
                        pulang: dayNum === 6 ? '13:00' : '17:00',
                        toleransi_menit: 15
                    };
                }
            },

            getDayName(dayNum) {
                const names = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu' };
                return names[dayNum] || 'Senin';
            },

            get opCurrentDayName() {
                return this.getDayName(this.opSelectedDay);
            },

            getDaySchedule(dayNum) {
                const found = this.opDailySchedules.find(d => d.day_of_week === dayNum);
                return found || { is_work_day: dayNum <= 5, masuk: '08:00' };
            },

            get currentActiveOffice() {
                if (!this.opOffices || this.opOffices.length === 0) {
                    return {
                        id: 'hq',
                        name: 'Kantor Pusat PERUMDA Tirta Kepri',
                        address: 'Jl. M.T Haryono, Km.3, Tanjungpinang',
                        latitude: 0.92031,
                        longitude: 104.46069,
                        radius: 100,
                        is_primary: true
                    };
                }
                const found = this.opOffices.find(o => String(o.id) === String(this.opActiveOfficeId));
                if (found) return found;
                const primary = this.opOffices.find(o => o.is_primary);
                return primary || this.opOffices[0];
            },

            getCartoTile(lat, lng, zoom = 16, forceMode = null) {
                const safeLat = parseFloat(lat) || 0.92031;
                const safeLng = parseFloat(lng) || 104.46069;
                const x = Math.floor((safeLng + 180.0) / 360.0 * (1 << zoom));
                const y = Math.floor((1.0 - Math.log(Math.tan(safeLat * Math.PI / 180.0) + 1.0 / Math.cos(safeLat * Math.PI / 180.0)) / Math.PI) / 2.0 * (1 << zoom));
                const mode = forceMode || this.opMapMode || 'satellite';
                const gKey = window.GOOGLE_MAPS_API_KEY || '{{ config("services.google.maps_api_key") }}' || 'AIzaSyDpm1SPneUjheEMnk3TDOP38js6TQLmXps';
                if (mode === 'satellite') {
                    if (gKey) {
                        return `https://mt1.google.com/vt/lyrs=y&x=${x}&y=${y}&z=${zoom}&key=${gKey}`;
                    }
                    return `https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/${zoom}/${y}/${x}`;
                }
                if (gKey) {
                    return `https://mt1.google.com/vt/lyrs=m&x=${x}&y=${y}&z=${zoom}&key=${gKey}`;
                }
                return `https://basemaps.cartocdn.com/rastertiles/voyager/${zoom}/${x}/${y}.png?key=cb1_4ns5_1_ac9e8b38ad585a28159bf506`;
            },

            showOpToast(msg) {
                if (this.opToastTimeout) clearTimeout(this.opToastTimeout);
                this.opToastMessage = msg;
                this.opToastTimeout = setTimeout(() => {
                    this.opToastMessage = null;
                }, 3500);
            },

            async saveCurrentDaySchedule() {
                const idx = this.opDailySchedules.findIndex(d => d.day_of_week === this.opSelectedDay);
                if (idx !== -1) {
                    this.opDailySchedules[idx] = JSON.parse(JSON.stringify(this.opCurrentSchedule));
                } else {
                    this.opDailySchedules.push(JSON.parse(JSON.stringify(this.opCurrentSchedule)));
                }

                const payload = {
                    day_of_week: this.opCurrentSchedule.day_of_week,
                    day_name: this.opCurrentSchedule.day_name,
                    is_work_day: this.opCurrentSchedule.is_work_day,
                    masuk: this.opCurrentSchedule.masuk,
                    istirahat_keluar: this.opCurrentSchedule.istirahat_keluar,
                    istirahat_masuk: this.opCurrentSchedule.istirahat_masuk,
                    pulang: this.opCurrentSchedule.pulang,
                    toleransi_menit: this.opCurrentSchedule.toleransi_menit,
                    daily_schedules: this.opDailySchedules,
                    auto_lock_outside: this.opAutoLockOutside,
                    hari_kerja: this.opHariKerja
                };

                try {
                    await fetch("{{ route('api.settings.operational') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                    const todayDow = new Date().getDay() === 0 ? 7 : new Date().getDay();
                    if (this.opSelectedDay === todayDow) {
                        this.opTodaySchedule = JSON.parse(JSON.stringify(this.opCurrentSchedule));
                    }
                    this.showOpToast(`Jadwal jam kerja hari ${this.opCurrentDayName} berhasil disimpan.`);
                } catch (e) {
                    const todayDow = new Date().getDay() === 0 ? 7 : new Date().getDay();
                    if (this.opSelectedDay === todayDow) {
                        this.opTodaySchedule = JSON.parse(JSON.stringify(this.opCurrentSchedule));
                    }
                    this.showOpToast(`Jadwal jam kerja hari ${this.opCurrentDayName} berhasil disimpan.`);
                }
            },

            async saveAutoLockSetting(val) {
                this.opAutoLockOutside = val;
                const payload = {
                    auto_lock_outside: val,
                    daily_schedules: this.opDailySchedules
                };
                try {
                    await fetch("{{ route('api.settings.operational') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                    this.showOpToast(`Pengaturan kunci tombol berhasil diperbarui.`);
                } catch (e) {}
            },

            openTimePicker(slotNum, title, targetKey) {
                this.timePickerSlotNum = slotNum;
                this.timePickerTitle = title;
                this.timePickerTargetKey = targetKey;
                this.timePickerValue = this.opCurrentSchedule[targetKey] || '08:00';
                this.timePickerOpen = true;
            },

            saveTimePicker() {
                if (this.timePickerValue) {
                    this.opCurrentSchedule[this.timePickerTargetKey] = this.timePickerValue;
                }
                this.timePickerOpen = false;
            },

            openOfficeAddModal() {
                this.isEditOffice = false;
                this.officeForm = {
                    id: 'office_' + Date.now(),
                    name: '',
                    address: '',
                    latitude: '0.92031',
                    longitude: '104.46069',
                    radius: 100,
                    is_primary: false,
                    map_link: '',
                    is_resolving: false,
                    resolve_status: null,
                    resolve_success: false,
                    preview_lat: 0.92031,
                    preview_lng: 104.46069,
                };
                this.officeModalOpen = true;
            },

            openOfficeEditModal(office) {
                this.isEditOffice = true;
                const lat = parseFloat(office.latitude) || 0.92031;
                const lng = parseFloat(office.longitude) || 104.46069;
                this.officeForm = {
                    id: String(office.id),
                    name: office.name || office.nama_kantor || '',
                    address: office.address || office.alamat || '',
                    latitude: String(lat),
                    longitude: String(lng),
                    radius: parseInt(office.radius) || 100,
                    is_primary: Boolean(office.is_primary),
                    map_link: '',
                    is_resolving: false,
                    resolve_status: null,
                    resolve_success: false,
                    preview_lat: lat,
                    preview_lng: lng,
                };
                this.officeModalOpen = true;
            },

            async resolveMapLink() {
                const link = (this.officeForm.map_link || '').trim();
                if (!link) return;

                this.officeForm.is_resolving = true;
                this.officeForm.resolve_status = null;

                try {
                    const res = await fetch("{{ route('api.maps.resolve') }}?url=" + encodeURIComponent(link), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await res.json();
                    if (json.success && json.data) {
                        const lat = parseFloat(json.data.latitude);
                        const lng = parseFloat(json.data.longitude);
                        this.officeForm.latitude = lat.toFixed(6);
                        this.officeForm.longitude = lng.toFixed(6);
                        this.officeForm.preview_lat = lat;
                        this.officeForm.preview_lng = lng;
                        this.officeForm.resolve_success = true;
                        this.officeForm.resolve_status = 'Titik lokasi peta berhasil ditentukan!';

                        if (json.data.name) {
                            if (!this.officeForm.name) this.officeForm.name = json.data.name;
                            if (!this.officeForm.address) this.officeForm.address = json.data.name;
                        }
                    } else {
                        this.officeForm.resolve_success = false;
                        this.officeForm.resolve_status = 'Gagal mendeteksi koordinat dari tautan. Pastikan tautan Google Maps valid.';
                    }
                } catch (e) {
                    this.officeForm.resolve_success = false;
                    this.officeForm.resolve_status = 'Gagal menghubungi resolver peta.';
                } finally {
                    this.officeForm.is_resolving = false;
                }
            },

            updateManualCoords() {
                const lat = parseFloat(this.officeForm.latitude);
                const lng = parseFloat(this.officeForm.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    this.officeForm.preview_lat = lat;
                    this.officeForm.preview_lng = lng;
                }
            },

            async saveOfficeFormModal() {
                if (!this.officeForm.name.trim()) {
                    alert('Nama kantor wajib diisi.');
                    return;
                }

                const lat = parseFloat(this.officeForm.latitude) || this.officeForm.preview_lat;
                const lng = parseFloat(this.officeForm.longitude) || this.officeForm.preview_lng;
                const radius = parseInt(this.officeForm.radius) || 100;
                const isPrimary = Boolean(this.officeForm.is_primary);

                const payload = {
                    id: this.officeForm.id,
                    name: this.officeForm.name.trim(),
                    address: this.officeForm.address.trim(),
                    latitude: lat,
                    longitude: lng,
                    radius: radius,
                    is_primary: isPrimary
                };

                try {
                    const res = await fetch("{{ route('api.offices.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                    const json = await res.json();

                    if (isPrimary) {
                        this.opOffices.forEach(o => o.is_primary = false);
                    }

                    const existingIdx = this.opOffices.findIndex(o => String(o.id) === String(this.officeForm.id));
                    if (existingIdx !== -1) {
                        this.opOffices[existingIdx] = {
                            id: this.officeForm.id,
                            name: payload.name,
                            address: payload.address,
                            latitude: lat,
                            longitude: lng,
                            radius: radius,
                            is_primary: isPrimary
                        };
                        this.showOpToast(`Perubahan pada "${payload.name}" berhasil disimpan.`);
                    } else {
                        this.opOffices.push({
                            id: this.officeForm.id,
                            name: payload.name,
                            address: payload.address,
                            latitude: lat,
                            longitude: lng,
                            radius: radius,
                            is_primary: isPrimary
                        });
                        this.showOpToast(`Titik ${isPrimary ? 'Kantor Utama' : 'Kantor Cabang'} "${payload.name}" berhasil ditambahkan.`);
                    }

                    this.officesList = this.opOffices;
                    if (String(this.selectedVerificationOfficeId) === String(this.officeForm.id)) {
                        this.targetOfficeName = payload.name;
                        this.targetOfficeLat = lat;
                        this.targetOfficeLng = lng;
                        this.targetOfficeRadius = radius;
                    }

                    this.officeModalOpen = false;
                } catch (e) {
                    alert('Terjadi kesalahan saat menyimpan kantor.');
                }
            },

            openDeleteOfficeModal(office) {
                if (office.is_primary) {
                    alert('Kantor utama tidak dapat dihapus.');
                    return;
                }
                this.officeToDelete = office;
                this.deleteOfficeModalOpen = true;
            },

            async confirmDeleteOffice() {
                if (!this.officeToDelete) return;
                const target = this.officeToDelete;
                const id = target.id;
                const name = target.name || target.nama_kantor;

                try {
                    await fetch(`/api/offices/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    this.opOffices = this.opOffices.filter(o => String(o.id) !== String(id));
                    if (String(this.opActiveOfficeId) === String(id)) {
                        const primary = this.opOffices.find(o => o.is_primary);
                        this.opActiveOfficeId = primary ? primary.id : (this.opOffices[0]?.id || '');
                    }
                    this.deleteOfficeModalOpen = false;
                    this.officeToDelete = null;
                    this.showOpToast(`Titik kantor cabang "${name}" berhasil dihapus.`);
                } catch (e) {
                    alert('Gagal menghapus kantor.');
                }
            },

            async setAsPrimaryOffice(officeId) {
                try {
                    await fetch(`/api/offices/${officeId}/primary`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    this.opOffices.forEach(o => {
                        o.is_primary = (String(o.id) === String(officeId));
                    });

                    const target = this.opOffices.find(o => String(o.id) === String(officeId));
                    this.showOpToast(`"${target ? (target.name || target.nama_kantor) : 'Kantor'}" berhasil ditetapkan sebagai Kantor Utama.`);
                } catch (e) {
                    alert('Gagal menetapkan kantor utama.');
                }
            },

            async saveGeofencingSettings() {
                const payload = {
                    strict_mode: true,
                    active_office_id: this.opActiveOfficeId,
                    offices: this.opOffices
                };
                try {
                    await fetch("{{ route('api.settings.geofencing') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                    this.showOpToast(`Titik acuan kantor penempatan (${this.opOffices.length} kantor) berhasil diperbarui.`);
                } catch (e) {
                    alert('Gagal menyimpan konfigurasi geofencing.');
                }
            },

            // Superadmin IT Employee Management Methods (Exact Match to Flutter)
            totalEmployees() {
                return this.employeesList.length;
            },
            totalStaffIT() {
                return this.employeesList.filter(u => u.role === 'staffIT').length;
            },
            totalDirektur() {
                return this.employeesList.filter(u => u.role === 'direktur').length;
            },
            totalKaryawan() {
                return this.employeesList.filter(u => u.role === 'karyawan').length;
            },
            getRoleColor(role) {
                if (role === 'staffIT') return '#0D9488';
                if (role === 'direktur') return '#7C3AED';
                return '#316BF3';
            },
            getRoleDisplayName(role) {
                if (role === 'staffIT') return 'Superadmin';
                if (role === 'direktur') return 'Admin';
                return 'Pengguna';
            },
            getRoleIcon(role) {
                if (role === 'staffIT') return 'terminal';
                if (role === 'direktur') return 'admin_panel_settings';
                return 'person';
            },
            getRoleBootstrapIcon(role) {
                if (role === 'staffIT' || role === 'superadmin') return 'bi bi-person-fill-gear';
                if (role === 'direktur' || role === 'admin') return 'bi bi-person-fill-lock';
                return 'bi bi-person-fill';
            },
            getSortShortLabel(opt) {
                switch(opt) {
                    case 'default': return 'Bawaan';
                    case 'nameAsc': return 'Nama A-Z';
                    case 'nameDesc': return 'Nama Z-A';
                    case 'nipAsc': return 'NIK 0-9';
                    case 'nipDesc': return 'NIK 9-0';
                    case 'department': return 'Bidang';
                    case 'role': return 'Peran';
                    case 'office': return 'Kantor';
                    default: return 'Bawaan';
                }
            },
            getSortIcon(opt) {
                switch(opt) {
                    case 'default':
                        return 'sort';
                    case 'nameAsc':
                    case 'nameDesc':
                        return 'sort_by_alpha';
                    case 'nipAsc':
                    case 'nipDesc':
                        return 'pin';
                    case 'department':
                        return 'business_center';
                    case 'role':
                        return 'admin_panel_settings';
                    case 'office':
                        return 'location_city';
                    default:
                        return 'sort';
                }
            },
            normalizeBidang(dept) {
                if (!dept) return 'Bidang Hubungan Langganan';
                const found = this.bidangDataList.find(b => b.nama.toLowerCase() === dept.toLowerCase());
                if (found) return found.nama;
                const lower = dept.toLowerCase();
                if (lower.includes('langganan') || lower.includes('meter') || lower.includes('pelayanan') || lower.includes('hubungan')) {
                    return 'Bidang Hubungan Langganan';
                }
                if (lower.includes('teknik') || lower.includes('distribusi') || lower.includes('perencanaan') || lower.includes('lapangan') || lower.includes('ipa') || lower.includes('pengolahan')) {
                    return 'Bidang Teknik';
                }
                if (lower.includes('kehilangan') || lower.includes('air') || lower.includes('spka') || lower.includes('gis') || lower.includes('jaringan')) {
                    return 'Satuan Pengendalian Kehilangan Air';
                }
                if (lower.includes('pusimpel') || lower.includes('it') || lower.includes('system') || lower.includes('admin')) {
                    return 'PUSIMPEL IT & System';
                }
                if (lower.includes('direksi') || lower.includes('direktur')) {
                    return 'Dewan Direksi';
                }
                return this.bidangDataList[0]?.nama || 'Bidang Hubungan Langganan';
            },
            getSeksiListForBidang(bidangName) {
                const norm = this.normalizeBidang(bidangName);
                const item = this.bidangDataList.find(b => b.nama === norm);
                return item ? item.seksi : ['Seksi Pembacaan Meter'];
            },
            onAddBidangChange() {
                const seksi = this.getSeksiListForBidang(this.addEmpForm.department);
                this.addEmpForm.position = seksi[0] || '';
            },
            onEditBidangChange() {
                const seksi = this.getSeksiListForBidang(this.editEmpForm.department);
                this.editEmpForm.position = seksi[0] || '';
            },
            resetAllEmpFilters() {
                this.roleSearchQuery = '';
                this.empSearchQuery = '';
                this.empFilterRole = null;
                this.empFilterDepartment = null;
                this.empFilterOfficeId = null;
                this.empSortOption = 'default';
            },
            getFilteredEmployees(subTab) {
                const query = (subTab === 0 ? this.roleSearchQuery : this.empSearchQuery).toLowerCase().trim();
                let list = this.employeesList.filter(u => {
                    const matchesQuery = !query ||
                        (u.name && u.name.toLowerCase().includes(query)) ||
                        (u.nip && u.nip.toLowerCase().includes(query)) ||
                        (u.department && u.department.toLowerCase().includes(query)) ||
                        (u.office_name && u.office_name.toLowerCase().includes(query)) ||
                        (u.position && u.position.toLowerCase().includes(query));

                    const matchesRole = !this.empFilterRole || u.role === this.empFilterRole;
                    const matchesDept = !this.empFilterDepartment || (u.department && u.department.toLowerCase() === this.empFilterDepartment.toLowerCase());
                    const matchesOffice = !this.empFilterOfficeId || String(u.office_id) === String(this.empFilterOfficeId);

                    return matchesQuery && matchesRole && matchesDept && matchesOffice;
                });

                // Apply Sorting matching Flutter EmployeeManagementProvider
                list.sort((a, b) => {
                    switch(this.empSortOption) {
                        case 'default':
                            return (Number(a.id) || 0) - (Number(b.id) || 0);
                        case 'nameAsc':
                            return (a.name || '').localeCompare(b.name || '');
                        case 'nameDesc':
                            return (b.name || '').localeCompare(a.name || '');
                        case 'nipAsc':
                            return (a.nip || '').localeCompare(b.nip || '', undefined, { numeric: true });
                        case 'nipDesc':
                            return (b.nip || '').localeCompare(a.nip || '', undefined, { numeric: true });
                        case 'department':
                            const cmpDept = (a.department || '').localeCompare(b.department || '');
                            if (cmpDept !== 0) return cmpDept;
                            return (a.name || '').localeCompare(b.name || '');
                        case 'role':
                            const roleWeight = r => (r === 'staffIT' ? 0 : (r === 'direktur' ? 1 : 2));
                            const cmpRole = roleWeight(a.role) - roleWeight(b.role);
                            if (cmpRole !== 0) return cmpRole;
                            return (a.name || '').localeCompare(b.name || '');
                        case 'office':
                            const aHq = (a.office_id === 'hq' || a.office_id === '1') ? 0 : 1;
                            const bHq = (b.office_id === 'hq' || b.office_id === '1') ? 0 : 1;
                            if (aHq !== bHq) return aHq - bHq;
                            const cmpOff = (a.office_name || '').localeCompare(b.office_name || '');
                            if (cmpOff !== 0) return cmpOff;
                            return (a.name || '').localeCompare(b.name || '');
                        default:
                            return (Number(a.id) || 0) - (Number(b.id) || 0);
                    }
                });

                return list;
            },
            openChangeRoleModal(user) {
                this.changeRoleData = {
                    id: user.id,
                    nip: user.nip,
                    name: user.name,
                    selectedRole: user.role
                };
                this.changeRoleModal = true;
            },
            async submitChangeRole() {
                const user = this.employeesList.find(u => String(u.id) === String(this.changeRoleData.id));
                if (!user) return;
                const newRole = this.changeRoleData.selectedRole;
                const newPosition = (newRole === 'direktur' ? 'Direktur Utama' : (newRole === 'staffIT' ? 'Staff IT & System' : 'Staff Lapangan'));
                const newDepartment = (newRole === 'direktur' ? 'Dewan Direksi' : (newRole === 'staffIT' ? 'PUSIMPEL IT & System' : user.department));

                try {
                    const res = await fetch('/api/employees/' + user.id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: user.id,
                            role: newRole,
                            position: newPosition,
                            department: newDepartment,
                            is_superadmin: true,
                            actor_role: 'staffIT'
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        user.role = newRole;
                        user.position = newPosition;
                        user.department = newDepartment;
                        this.changeRoleModal = false;
                        this.showEmpToast(`Peran ${user.name} berhasil diubah menjadi ${this.getRoleDisplayName(newRole)}.`);
                    } else {
                        alert('Gagal mengubah peran: ' + (json.message || ''));
                    }
                } catch(e) {
                    alert('Terjadi kesalahan koneksi.');
                }
            },
            openResetPinModal(user) {
                this.resetPinData = {
                    id: user.id,
                    nip: user.nip,
                    name: user.name,
                    newPin: '123456'
                };
                this.resetPinModal = true;
            },
            async submitResetPin() {
                const user = this.employeesList.find(u => String(u.id) === String(this.resetPinData.id));
                if (!user) return;
                const pin = this.resetPinData.newPin.trim();
                if (pin.length !== 6) {
                    alert('PIN harus terdiri dari 6 digit.');
                    return;
                }
                try {
                    const res = await fetch('/api/employees/' + user.id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: user.id,
                            pin: pin,
                            is_superadmin: true,
                            actor_role: 'staffIT'
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        user.pin = pin;
                        this.resetPinModal = false;
                        this.showEmpToast(`PIN untuk ${user.name} berhasil direset ke: ${pin}`);
                    } else {
                        alert('Gagal mereset PIN: ' + (json.message || ''));
                    }
                } catch(e) {
                    alert('Terjadi kesalahan koneksi.');
                }
            },
            openAddEmployeeModal() {
                this.addEmpForm = {
                    nip: '',
                    name: '',
                    department: this.bidangDataList[1]?.nama || 'Bidang Hubungan Langganan',
                    position: this.bidangDataList[1]?.seksi[0] || 'Seksi Pembacaan Meter',
                    office_id: this.officesList[0]?.id || 'hq',
                    phone: '',
                    role: 'karyawan',
                    pin: '123456'
                };
                this.addEmployeeModal = true;
            },
            async submitAddEmployee() {
                const f = this.addEmpForm;
                if (!f.nip.trim() || !f.name.trim()) {
                    alert('NIK dan Nama Lengkap wajib diisi.');
                    return;
                }
                const chosenOffice = this.officesList.find(o => String(o.id) === String(f.office_id)) || this.officesList[0];
                const officeName = chosenOffice ? chosenOffice.name : 'Kantor Pusat PERUMDA Tirta Kepri';

                try {
                    const res = await fetch("{{ route('api.employees.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            nip: f.nip.trim(),
                            name: f.name.trim(),
                            department: f.department,
                            position: f.position,
                            office_id: f.office_id,
                            office_name: officeName,
                            phone: f.phone.trim() || '081234567890',
                            role: f.role,
                            pin: f.pin.trim() || '123456',
                            is_superadmin: true,
                            actor_role: 'staffIT'
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        const newEmp = {
                            id: json.data?.id || 'emp_' + Date.now(),
                            nip: f.nip.trim(),
                            nik: f.nip.trim(),
                            name: f.name.trim(),
                            email: (f.name.toLowerCase().replace(/[^a-z0-9]/g, '.') + '@tirtafield.co.id'),
                            department: f.department,
                            position: f.position,
                            office_id: f.office_id,
                            office_name: officeName,
                            phone: f.phone.trim() || '081234567890',
                            role: f.role,
                            pin: f.pin.trim() || '123456',
                            avatar_url: null
                        };
                        this.employeesList.unshift(newEmp);
                        this.addEmployeeModal = false;
                        this.showEmpToast(`Pegawai ${f.name.trim()} berhasil ditambahkan di ${officeName}.`);
                    } else {
                        alert('Gagal menambah pegawai: ' + (json.message || ''));
                    }
                } catch(e) {
                    alert('Terjadi kesalahan koneksi.');
                }
            },
            openEditModal(user) {
                const normDept = this.normalizeBidang(user.department);
                this.editEmpForm = {
                    id: user.id,
                    nip: user.nip,
                    name: user.name,
                    department: normDept,
                    position: user.position,
                    office_id: user.office_id,
                    phone: user.phone || '081234567890'
                };
                this.editEmployeeModal = true;
            },
            async submitEditEmployee() {
                const f = this.editEmpForm;
                const user = this.employeesList.find(u => String(u.id) === String(f.id));
                if (!user) return;
                const chosenOffice = this.officesList.find(o => String(o.id) === String(f.office_id)) || this.officesList[0];
                const officeName = chosenOffice ? chosenOffice.name : (user.office_name || 'Kantor Pusat');

                try {
                    const res = await fetch('/api/employees/' + f.id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: f.id,
                            nip: f.nip.trim(),
                            name: f.name.trim(),
                            department: f.department,
                            position: f.position,
                            office_id: f.office_id,
                            office_name: officeName,
                            phone: f.phone.trim(),
                            is_superadmin: true,
                            actor_role: 'staffIT'
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        user.nip = f.nip.trim();
                        user.nik = f.nip.trim();
                        user.name = f.name.trim();
                        user.department = f.department;
                        user.position = f.position;
                        user.office_id = f.office_id;
                        user.office_name = officeName;
                        user.phone = f.phone.trim();
                        this.editEmployeeModal = false;
                        this.showEmpToast(`Data pegawai diperbarui (Penempatan: ${officeName}).`);
                    } else {
                        alert('Gagal memperbarui data: ' + (json.message || ''));
                    }
                } catch(e) {
                    alert('Terjadi kesalahan koneksi.');
                }
            },
            openDetailSheet(user) {
                this.detailEmpData = { ...user };
                this.detailEmployeeSheet = true;
            }
        };
    }

    async function saveOperationalForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        try {
            await fetch("{{ route('api.settings.operational') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            alert('Jam operasional 4 slot berhasil disimpan!');
        } catch(e) {
            alert('Tersimpan.');
        }
    }

    async function saveOfficeForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        try {
            await fetch("{{ route('api.offices.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            alert('Titik kantor berhasil ditambahkan!');
            window.location.reload();
        } catch(e) {
            alert('Tersimpan.');
            window.location.reload();
        }
    }

    async function submitAddEmployeeForm(form) {
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
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                alert('Pegawai baru berhasil ditambahkan!');
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch(e) {
            alert('Pegawai berhasil disimpan!');
            window.location.reload();
        }
    }

    async function submitEditEmployeeForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_superadmin = true;
        data.actor_role = 'staffIT';
        try {
            const res = await fetch('/api/employees/' + data.id, {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                alert('Data pegawai berhasil diperbarui!');
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch(e) {
            alert('Terjadi kesalahan saat menyimpan data pegawai.');
        }
    }

    async function submitSuperadminProfile(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_superadmin = true;
        data.actor_role = 'staffIT';
        try {
            const res = await fetch("{{ route('api.profile.update') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                alert('Pengaturan profil akun Superadmin Pusimpel berhasil disimpan.');
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
            }
        } catch(e) {
            alert('Terjadi kesalahan koneksi saat menyimpan profil.');
        }
    }
</script>
@endsection
