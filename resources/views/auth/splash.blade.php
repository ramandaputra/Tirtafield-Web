<!DOCTYPE html>
<html lang="id" class="h-full w-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#00288E">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi Lapangan">
    <title>Absensi Lapangan - Sistem Presensi Mobile</title>

    <!-- Mobile Shortcut & App Icons (public/images/app.jpg) -->
    <link rel="icon" type="image/jpeg" href="{{ asset('images/app.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('images/app.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/app.jpg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/app.jpg') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('images/app.jpg') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Google Fonts: Inter & Material Symbols Rounded -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #F8FAFC;
            margin: 0;
            padding: 0;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
        }

        #splashContainer {
            opacity: 1;
            transition: opacity 400ms ease-out;
        }

        /* Flutter Entrance Animation (Scale: 0.82 -> 1.0 with Curves.easeOutBack, Fade: 0.0 -> 1.0 with Curves.easeIn, 1200ms) */
        @keyframes flutterEntrance {
            0% {
                opacity: 0;
                transform: scale(0.82);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-flutter-center {
            animation: flutterEntrance 1200ms cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            will-change: transform, opacity;
        }

        /* Flutter CircularProgressIndicator replica: stroke 2.5px, 28x28px, white with alpha 0.8 */
        .flutter-circular-progress {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2.5px solid rgba(255, 255, 255, 0.2);
            border-top-color: rgba(255, 255, 255, 0.85);
            animation: flutterSpinnerRotate 0.85s linear infinite;
        }

        @keyframes flutterSpinnerRotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen antialiased flex flex-col items-center justify-start select-none cursor-pointer">

    <!-- Mobile-First App Shell (Max width 430px centered, persis seperti layout halaman lainnya) -->
    <div id="splashContainer" 
         class="w-full max-w-[430px] min-h-screen sm:min-h-[100dvh] bg-gradient-to-b from-[#001453] via-[#00288E] to-[#0043B3] shadow-[0_0_40px_rgba(0,0,0,0.15)] sm:border-x sm:border-[#DAE2FD]/30 relative flex flex-col justify-between items-center text-white px-5 py-8"
         style="padding-top: max(env(safe-area-inset-top, 0px), 36px); padding-bottom: max(env(safe-area-inset-bottom, 0px), 28px);">

        <!-- Top Spacer (SizedBox 40) -->
        <div class="w-full h-8 shrink-0 pointer-events-none" aria-hidden="true"></div>

        <!-- Center Logo & Branding (Entrance Animation) -->
        <div class="animate-flutter-center flex flex-col items-center text-center max-w-[340px] select-none">
            <!-- App Icon: public/images/app.jpg tanpa background putih -->
            <div class="w-[96px] h-[96px] rounded-[24px] overflow-hidden flex items-center justify-center relative shrink-0 shadow-[0_12px_36px_rgba(0,0,0,0.40)] border border-white/20">
                <img src="{{ asset('images/app.jpg') }}" 
                     alt="Logo Absensi Lapangan" 
                     class="w-full h-full object-cover select-none pointer-events-none"
                     onerror="this.style.display='none'; document.getElementById('emblemFallbackIcon').style.display='flex';">
                <div id="emblemFallbackIcon" style="display: none;" class="w-full h-full items-center justify-center text-white">
                    <span class="material-symbols-rounded text-[48px] select-none">lock_person</span>
                </div>
            </div>

            <!-- Title: ABSENSI LAPANGAN (Menggantikan TIRTAFIELD) -->
            <h1 class="text-[22px] sm:text-[24px] font-extrabold text-white tracking-[2.5px] mt-6 leading-tight select-none text-center">
                ABSENSI LAPANGAN
            </h1>

            <!-- Subtitle Pill: PERUMDA AIR MINUM TIRTA KEPRI -->
            <div class="mt-2 px-3.5 py-1 rounded-full bg-white/[0.12] inline-flex items-center justify-center max-w-full">
                <span class="text-[11px] font-bold tracking-[1.5px] text-[#6FFBBE] uppercase leading-none select-none text-center">
                    PERUMDA AIR MINUM TIRTA KEPRI
                </span>
            </div>

            <!-- Description: Presensi Kehadiran Jarak Jauh -->
            <p class="text-[12px] font-normal text-white/70 mt-3 leading-normal select-none">
                Presensi Kehadiran Jarak Jauh
            </p>
        </div>

        <!-- Bottom Loading & Version -->
        <div class="shrink-0 flex flex-col items-center text-center select-none">
            <!-- CircularProgressIndicator -->
            <div class="flutter-circular-progress"></div>

            <!-- Space: 16px -->
            <div class="text-[12px] text-white/65 font-medium mt-4 leading-none select-none">
                Memuat Data Sistem...
            </div>

            <!-- Space: 4px -->
            <div class="text-[11px] text-white/40 font-normal mt-1 leading-none select-none">
                Dikembangkan Oleh PUSIMPEL TIRTA KEPRI
            </div>
        </div>

    </div>

    <script>
        const targetUrl = "{{ Auth::check() ? route('web.dashboard') : route('login') }}";
        let navigated = false;

        function navigateNext() {
            if (navigated) return;
            navigated = true;
            const container = document.getElementById('splashContainer');
            if (container) {
                container.style.opacity = '0';
            }
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 400); // 400ms transitionDuration
        }

        // Auto navigate after 2.2 seconds
        const autoTimer = setTimeout(navigateNext, 2200);

        // Allow instant 1-click skip on tap/click
        document.body.addEventListener('click', () => {
            clearTimeout(autoTimer);
            navigateNext();
        });
    </script>
</body>
</html>
