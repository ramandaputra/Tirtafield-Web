<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Absensi Lapangan') - Presensi Mobile</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/app.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('images/app.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/app.jpg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/app.jpg') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('images/app.jpg') }}">

    <!-- PWA Manifest & Device Notification Support -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#00288E">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi Lapangan">

    <!-- Tirtafield Alarm & Device Notification Engine -->
    <script src="{{ asset('js/tirtafield-alarm.js') }}"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Google Material Symbols Rounded (Matching Flutter Icons exactly) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Font Awesome (for fallback compatibility) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @php
        $gMapsApiKey = config('services.google.maps_api_key') ?: env('GOOGLE_MAPS_API_KEY', 'AIzaSyDpm1SPneUjheEMnk3TDOP38js6TQLmXps');
    @endphp
    @if($gMapsApiKey)
    <!-- Google Maps JavaScript API -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $gMapsApiKey }}&libraries=places" async defer></script>
    <script>
        window.GOOGLE_MAPS_API_KEY = "{{ $gMapsApiKey }}";
    </script>
    @endif

    <!-- Tailwind CSS with Exact Flutter AppColors -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brandPrimary: '#00288E',
                        brandPrimaryContainer: '#1E40AF',
                        brandSecondary: '#0051D5',
                        brandSecondaryContainer: '#316BF3',
                        brandBg: '#FAF8FF',
                        brandSurface: '#FAF8FF',
                        surfaceLow: '#F2F3FF',
                        surfaceContainer: '#EAEDFF',
                        surfaceHigh: '#E2E7FF',
                        surfaceHighest: '#DAE2FD',
                        textOnSurface: '#131B2E',
                        textVariant: '#64748B',
                        roleKaryawan: '#316BF3',
                        roleDirektur: '#7C3AED',
                        roleStaffIT: '#0D9488',
                        checkIn: '#059669',
                        breakOut: '#316BF3',
                        breakIn: '#6366F1',
                        checkOut: '#EF4444',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
            color: #131B2E;
        }

        /* Material Symbols Rounded (Matching Flutter Icons) */
        .material-symbols-rounded {
            font-family: 'Material Symbols Rounded';
            font-weight: 500;
            font-style: normal;
            font-size: 20px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            vertical-align: middle;
            font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
            -webkit-font-smoothing: antialiased;
        }
        .material-symbols-rounded.fill {
            font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        ::-webkit-scrollbar-track {
            background: #F2F3FF;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }
        /* High-Contrast, Clean Distance Line Tooltip (matching Flutter Leaflet Map) */
        .distance-tooltip {
            background: #ffffff !important;
            color: #0f172a !important;
            border: 2px solid #2563eb !important;
            border-radius: 20px !important;
            font-size: 11.5px !important;
            padding: 2px 8px !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.2) !important;
            font-weight: 700 !important;
            white-space: nowrap !important;
        }
        .distance-tooltip b {
            color: #1d4ed8 !important;
        }
        .leaflet-tooltip-top:before {
            border-top-color: #2563eb !important;
        }
        .leaflet-tooltip-bottom:before {
            border-bottom-color: #2563eb !important;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-textOnSurface min-h-screen antialiased flex flex-col items-center justify-start selection:bg-brandSecondary/20 selection:text-brandSecondary">

    <!-- Mobile-First App Shell (Max width 430px centered, 100% on mobile devices) -->
    <div x-data="{
        currentTab: '{{ auth()->user()?->isKaryawan() ? 'beranda' : 'dashboard' }}',
        setTab(t) {
            this.currentTab = t;
            window.dispatchEvent(new CustomEvent('switch-tab', { detail: t }));
        }
    }" x-on:switch-tab.window="currentTab = $event.detail"
        class="w-full max-w-[430px] min-h-screen bg-[#FAF8FF] shadow-[0_0_30px_rgba(0,0,0,0.06)] sm:border-x sm:border-[#DAE2FD] relative flex flex-col">

        @auth
        @if (!request()->routeIs(['login', 'web.login', 'splash', 'web.splash']))
        <!-- Top App Bar (Mirrors Flutter Top App Bar exactly per Role and Tab) -->
        <header class="bg-[#FAF8FF]/95 backdrop-blur-md sticky top-0 z-40 border-b border-[#DAE2FD]/80 shadow-[0_2px_10px_rgba(15,23,42,0.03)] w-full pt-[env(safe-area-inset-top,0px)]">
            <div class="px-4 min-h-[60px] py-2.5 flex items-center w-full">
                <!-- Left Content: Dynamic according to Role and Tab -->
                <div class="flex items-center space-x-3 w-full min-w-0">
                    
                    <!-- If on Akun tab for Direktur or Staff IT, show Back Arrow -->
                    @if (auth()->user()?->isDirektur() || auth()->user()?->isStaffIT())
                        <template x-if="currentTab === 'akun'">
                            <div class="flex items-center space-x-2 w-full min-w-0">
                                <button type="button" @click="setTab('dashboard')" class="p-1.5 -ml-1.5 rounded-full text-slate-700 hover:text-slate-900 hover:bg-slate-100 flex items-center shrink-0 transition-colors">
                                    <span class="material-symbols-rounded text-xl">arrow_back</span>
                                </button>
                                <span class="font-bold text-sm sm:text-base text-[#131B2E] truncate">
                                    {{ auth()->user()?->isDirektur() ? 'Akun & Profil Admin' : 'Akun & Profil Superadmin' }}
                                </span>
                            </div>
                        </template>
                    @endif

                    <!-- If on Akun tab for Karyawan, show Header Title -->
                    @if (auth()->user()?->isKaryawan())
                        <template x-if="currentTab === 'akun'">
                            <div class="flex items-center space-x-2 w-full min-w-0">
                                <button type="button" @click="setTab('beranda')" class="p-1.5 -ml-1.5 rounded-full text-slate-700 hover:text-slate-900 hover:bg-slate-100 flex items-center shrink-0 transition-colors">
                                    <span class="material-symbols-rounded text-xl">arrow_back</span>
                                </button>
                                <span class="font-bold text-sm sm:text-base text-[#131B2E] truncate">Akun & Profil</span>
                            </div>
                        </template>
                    @endif

                    <!-- Default Main Tab Header (Avatar + Title + Subtitle) -->
                    <template x-if="currentTab !== 'akun'">
                        <div class="flex items-center space-x-3 w-full min-w-0">
                            <!-- Avatar with Online Ring Dot -->
                            <div class="relative shrink-0">
                                <div class="w-9 h-9 rounded-full {{ auth()->user()?->isDirektur() ? 'ring-2 ring-purple-500' : (auth()->user()?->isStaffIT() ? 'ring-2 ring-teal-500' : 'ring-2 ring-blue-500') }} bg-gradient-to-tr from-brandPrimary to-brandSecondary text-white font-bold flex items-center justify-center text-xs shadow-xs overflow-hidden shrink-0">
                                    @if (auth()->user()?->avatar_url)
                                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="profile-avatar-img w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                                    @endif
                                </div>
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-white ring-1 ring-emerald-400"></span>
                            </div>

                            <!-- Corporate Title & Subtitle Matching Flutter -->
                            <div class="min-w-0 flex-1">
                                @if (auth()->user()?->isKaryawan())
                                    <div class="font-extrabold text-[15px] tracking-tight text-[#131B2E] font-sans truncate">
                                        ABSENSI LAPANGAN
                                    </div>
                                    <div class="text-[11.5px] text-[#64748B] font-medium truncate">
                                        {{ auth()->user()?->name }} · {{ auth()->user()?->nik }}
                                    </div>
                                @else
                                    <div class="font-bold text-[14px] tracking-tight text-[#131B2E] font-sans truncate">
                                        {{ auth()->user()?->name }}
                                    </div>
                                    <div class="text-[11px] text-[#64748B] font-medium truncate">
                                        {{ auth()->user()?->nik }} • {{ auth()->user()?->position }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </header>
        @endif
        @endauth

        <!-- Flash Alerts -->
        <div class="px-4 mt-2.5 w-full">
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-2.5 rounded-r-xl shadow-xs text-xs font-semibold flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-rounded text-emerald-600 text-base">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-2.5 rounded-r-xl shadow-xs text-xs font-semibold">
                    <div class="flex items-center space-x-1.5 mb-1">
                        <span class="material-symbols-rounded text-rose-600 text-base">warning</span>
                        <span>Perhatian:</span>
                    </div>
                    <ul class="list-disc list-inside text-rose-700 text-[11px]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Mobile Content Area -->
        <main class="flex-1 w-full px-4 py-3 {{ auth()->check() ? 'pb-24' : 'pb-8' }}">
            @yield('content')
        </main>

        @auth
        <!-- Floating Bottom Navigation Bar (Matching Flutter BottomNavigationBar exactly for each role) -->
        <div class="fixed bottom-3 left-1/2 -translate-x-1/2 w-full max-w-[430px] z-40 px-3 pointer-events-none">
            <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-[#DAE2FD] pointer-events-auto p-1 flex items-center justify-around h-15">
                
                @if (auth()->user()?->isKaryawan())
                    {{-- KARYAWAN: ONLY 2 TABS (Beranda & Akun) with active dot indicator --}}
                    <!-- Tab 1: Beranda -->
                    <button type="button" @click="setTab('beranda')"
                        :class="currentTab === 'beranda' ? 'text-brandSecondary font-bold' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[22px]">home</span>
                        <span class="text-[11px] mt-0.5">Beranda</span>
                        <div x-show="currentTab === 'beranda'" class="w-1 h-1 rounded-full bg-brandSecondary mt-0.5"></div>
                    </button>
                    <!-- Tab 2: Akun -->
                    <button type="button" @click="setTab('akun')"
                        :class="currentTab === 'akun' ? 'text-brandSecondary font-bold' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[22px]">person</span>
                        <span class="text-[11px] mt-0.5">Akun</span>
                        <div x-show="currentTab === 'akun'" class="w-1 h-1 rounded-full bg-brandSecondary mt-0.5"></div>
                    </button>

                @elseif (auth()->user()?->isDirektur())
                    {{-- DIREKTUR: ONLY 2 TABS (Dashboard & Akun Saya) --}}
                    <!-- Tab 1: Dashboard -->
                    <button type="button" @click="setTab('dashboard')"
                        :class="currentTab === 'dashboard' ? 'text-roleDirektur font-bold bg-purple-50/70' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <div class="relative flex items-center justify-center">
                            <span class="material-symbols-rounded text-[22px]">dashboard</span>
                            @php
                                $pendingCount = \App\Models\Presensi::where('approval_status', 'pending')->count();
                            @endphp
                            @if ($pendingCount > 0)
                            <span class="absolute -top-1 -right-2 px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[9px] font-bold">
                                {{ $pendingCount }}
                            </span>
                            @endif
                        </div>
                        <span class="text-[11px] mt-0.5">Dashboard</span>
                    </button>
                    <!-- Tab 2: Akun Saya -->
                    <button type="button" @click="setTab('akun')"
                        :class="currentTab === 'akun' ? 'text-roleDirektur font-bold bg-purple-50/70' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[22px]">person</span>
                        <span class="text-[11px] mt-0.5">Akun Saya</span>
                    </button>

                @else
                    {{-- STAFF IT: 4 TABS (Dashboard, Operasional, Pegawai, Akun) --}}
                    <!-- Tab 1: Dashboard -->
                    <button type="button" @click="setTab('dashboard')"
                        :class="currentTab === 'dashboard' ? 'text-roleStaffIT font-bold bg-teal-50/70' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[20px]">dashboard</span>
                        <span class="text-[10px] mt-0.5">Dashboard</span>
                    </button>
                    <!-- Tab 2: Operasional -->
                    <button type="button" @click="setTab('operasional')"
                        :class="currentTab === 'operasional' ? 'text-roleStaffIT font-bold bg-teal-50/70' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[20px]">tune</span>
                        <span class="text-[10px] mt-0.5">Operasional</span>
                    </button>
                    <!-- Tab 3: Pegawai -->
                    <button type="button" @click="setTab('pegawai')"
                        :class="currentTab === 'pegawai' ? 'text-roleStaffIT font-bold bg-teal-50/70' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[20px]">people</span>
                        <span class="text-[10px] mt-0.5">Pegawai</span>
                    </button>
                    <!-- Tab 4: Akun -->
                    <button type="button" @click="setTab('akun')"
                        :class="currentTab === 'akun' ? 'text-roleStaffIT font-bold bg-teal-50/70' : 'text-[#64748B] hover:text-slate-800 font-medium'"
                        class="flex-1 flex flex-col items-center py-1 rounded-xl transition-all relative">
                        <span class="material-symbols-rounded text-[20px]">person</span>
                        <span class="text-[10px] mt-0.5">Akun</span>
                    </button>
                @endif
            </div>
        </div>
        @endauth

    </div>

    @yield('scripts')
</body>
</html>
