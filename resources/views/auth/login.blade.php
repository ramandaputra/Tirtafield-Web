@extends('layouts.app')

@section('title', 'Masuk ke Sistem')

@section('content')
<div x-data="flutterLoginApp()" x-init="init()" class="py-4 flex flex-col items-center justify-center select-none">

    <!-- Header Logo & Title (Matching Flutter LoginScreen Header) -->
    <div class="flex flex-col items-center text-center mb-6">
        <a href="{{ route('web.splash') }}" title="Lihat Splash Screen"
            class="w-[68px] h-[68px] rounded-2xl overflow-hidden border border-[#DAE2FD] shadow-md shadow-[#00288E]/10 flex items-center justify-center hover:scale-105 transition-transform">
            <img src="{{ asset('images/app.jpg') }}" alt="Logo" class="w-full h-full object-cover rounded-2xl"
                onerror="this.style.display='none'; document.getElementById('loginFallbackIcon').style.display='block';">
            <div id="loginFallbackIcon" style="display: none;" class="text-[#00288E] flex items-center justify-center">
                <span class="material-symbols-rounded text-3xl">lock</span>
            </div>
        </a>
        <h1 class="text-[22px] font-extrabold text-[#131B2E] tracking-tight mt-3 font-sans">ABSENSI LAPANGAN</h1>
        <p class="text-xs text-[#64748B] mt-0.5">Masukkan NIK dan PIN untuk masuk ke sistem</p>
    </div>

    <!-- 1. KOLOM UNTUK MEMASUKKAN NIK -->
    <div class="w-full max-w-[320px] mb-4 text-left">
        <label for="nikInput" class="block text-xs font-bold text-[#131B2E] mb-1.5 font-sans">
            NIK
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#00288E]">
                <span class="material-symbols-rounded text-xl">badge</span>
            </div>
            <input type="text" id="nikInput" x-model="nik" @input="errorMessage = null" placeholder="Masukkan NIK anda"
                class="w-full pl-10 pr-9 py-2.5 bg-white border border-[#DAE2FD] focus:border-[#316BF3] focus:ring-2 focus:ring-[#316BF3]/20 rounded-xl text-xs font-medium text-[#131B2E] uppercase placeholder:normal-case placeholder:text-slate-400 outline-none transition-all">
            <button type="button" x-show="nik.length > 0" @click="nik = ''; pin = ''; errorMessage = null;"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                <span class="material-symbols-rounded text-lg">close</span>
            </button>
        </div>
    </div>

    <!-- 2. PIN SECTION & 6 DOTS (Matching Flutter Animated 6 Pin Dots) -->
    <div class="w-full max-w-[320px] text-center mb-5">
        <div class="text-xs font-bold text-[#131B2E] mb-3 font-sans">
            PIN Keamanan (6 Digit)
        </div>

        <!-- 6 PIN Dots Indicator -->
        <div :class="{ 'animate-shake': hasErrorShake }" class="flex items-center justify-center space-x-3.5 my-1">
            <template x-for="i in 6" :key="i">
                <div class="w-4 h-4 rounded-full transition-all duration-150"
                    :class="errorMessage ? 'bg-[#BA1A1A] border-2 border-[#BA1A1A]' : (i <= pin.length ? 'bg-[#00288E] border-2 border-[#00288E] shadow-[0_0_8px_rgba(0,40,142,0.35)] scale-105' : 'bg-transparent border-2 border-[#C4C5D5]')">
                </div>
            </template>
        </div>

        <!-- Status / Error / Loading Message -->
        <div class="h-6 flex items-center justify-center mt-2">
            <template x-if="isLoading">
                <div class="flex items-center space-x-2 text-xs font-semibold text-[#0051D5]">
                    <div class="w-3.5 h-3.5 border-2 border-[#0051D5] border-t-transparent rounded-full animate-spin"></div>
                    <span>Memverifikasi kredensial...</span>
                </div>
            </template>
            <template x-if="!isLoading && errorMessage">
                <div class="text-xs font-bold text-[#BA1A1A]" x-text="errorMessage"></div>
            </template>
            <template x-if="!isLoading && !errorMessage && pin.length === 0">
                <span class="text-[11px] text-[#64748B]">Ketik 6 digit PIN</span>
            </template>
        </div>
    </div>

    <!-- 3. PURE NUMERIC KEYPAD (3x4 Grid of Circular Round Buttons - Exactly Matching Flutter) -->
    <div class="w-full max-w-[320px] mb-5">
        <div class="grid grid-cols-3 gap-y-3 gap-x-5 justify-items-center">
            <!-- Row 1 -->
            <button type="button" @click="pressDigit('1')" class="keypad-btn">1</button>
            <button type="button" @click="pressDigit('2')" class="keypad-btn">2</button>
            <button type="button" @click="pressDigit('3')" class="keypad-btn">3</button>
            <!-- Row 2 -->
            <button type="button" @click="pressDigit('4')" class="keypad-btn">4</button>
            <button type="button" @click="pressDigit('5')" class="keypad-btn">5</button>
            <button type="button" @click="pressDigit('6')" class="keypad-btn">6</button>
            <!-- Row 3 -->
            <button type="button" @click="pressDigit('7')" class="keypad-btn">7</button>
            <button type="button" @click="pressDigit('8')" class="keypad-btn">8</button>
            <button type="button" @click="pressDigit('9')" class="keypad-btn">9</button>
            <!-- Row 4 -->
            <div class="w-[66px] h-[66px]"></div>
            <button type="button" @click="pressDigit('0')" class="keypad-btn">0</button>
            <button type="button" @click="pressBackspace()" class="keypad-btn text-[#131B2E] flex items-center justify-center" title="Hapus">
                <span class="material-symbols-rounded text-2xl">backspace</span>
            </button>
        </div>
    </div>

    <!-- Hidden standard fallback form for accessibility -->
    <form id="hiddenLoginForm" action="{{ route('web.login.submit') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="nik" :value="nik">
        <input type="hidden" name="pin" :value="pin">
    </form>
</div>

<style>
    .keypad-btn {
        width: 66px;
        height: 66px;
        border-radius: 9999px;
        background-color: #ffffff;
        border: 1.2px solid #DAE2FD;
        color: #131B2E;
        font-size: 26px;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        transition: all 0.12s ease-in-out;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    .keypad-btn:active {
        transform: scale(0.92);
        background-color: #EAEDFF;
        border-color: #316BF3;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-8px); }
        40%, 80% { transform: translateX(8px); }
    }
    .animate-shake {
        animation: shake 0.35s ease-in-out;
    }
</style>
@endsection

@section('scripts')
<script>
    function flutterLoginApp() {
        return {
            nik: '',
            pin: '',
            isLoading: false,
            errorMessage: null,
            hasErrorShake: false,

            init() {
                // Focus NIK input on start
                this.$nextTick(() => {
                    const el = document.getElementById('nikInput');
                    if (el) el.focus();
                });

                // Listen to physical keyboard events (0-9, backspace, enter)
                window.addEventListener('keydown', (e) => {
                    if (document.activeElement && document.activeElement.id === 'nikInput') {
                        if (e.key === 'Enter') {
                            document.activeElement.blur();
                        }
                        return;
                    }

                    if (e.key >= '0' && e.key <= '9') {
                        this.pressDigit(e.key);
                    } else if (e.key === 'Backspace') {
                        this.pressBackspace();
                    }
                });
            },

            pressDigit(d) {
                if (this.isLoading) return;

                if (!this.nik.trim()) {
                    this.errorMessage = 'Silakan masukkan NIK terlebih dahulu.';
                    const el = document.getElementById('nikInput');
                    if (el) el.focus();
                    return;
                }

                if (this.pin.length < 6) {
                    this.errorMessage = null;
                    this.pin += d;

                    if (this.pin.length === 6) {
                        this.executeLogin();
                    }
                }
            },

            pressBackspace() {
                if (this.isLoading) return;
                if (this.pin.length > 0) {
                    this.errorMessage = null;
                    this.pin = this.pin.substring(0, this.pin.length - 1);
                }
            },

            async executeLogin() {
                if (!this.nik.trim()) {
                    this.errorMessage = 'Silakan masukkan NIK terlebih dahulu.';
                    this.pin = '';
                    return;
                }

                this.isLoading = true;
                this.errorMessage = null;

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('nik', this.nik.trim());
                    formData.append('pin', this.pin);

                    const res = await fetch("{{ route('web.login.submit') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json, text/html, */*',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    this.isLoading = false;

                    if (res.ok || res.redirected) {
                        window.location.href = "{{ route('web.dashboard') }}";
                    } else {
                        let errText = 'NIK atau PIN yang Anda masukkan tidak sesuai.';
                        try {
                            const json = await res.json();
                            if (json.errors && json.errors.nik) {
                                errText = json.errors.nik[0];
                            } else if (json.message) {
                                errText = json.message;
                            }
                        } catch(e) {}

                        this.triggerShakeError(errText);
                    }
                } catch(e) {
                    this.isLoading = false;
                    document.getElementById('hiddenLoginForm').submit();
                }
            },

            triggerShakeError(msg) {
                this.errorMessage = msg;
                this.pin = '';
                this.hasErrorShake = true;
                setTimeout(() => {
                    this.hasErrorShake = false;
                }, 400);
            }
        };
    }
</script>
@endsection
