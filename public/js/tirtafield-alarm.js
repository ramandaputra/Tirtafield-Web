/**
 * Tirtafield Presensi Mobile - Engine Alarm & Notifikasi Perangkat
 * Mendukung:
 * 1. Banner Notification (Heads-Up Banner)
 * 2. Layar Terkunci (Lock Screen Notification)
 * 3. Pusat Pemberitahuan (Notification Center / Shade)
 * 4. Alarm Suara Keras & Getaran (Web Audio API & Vibration API)
 */

window.TirtafieldAlarm = {
    swRegistration: null,
    permission: 'default',
    audioCtx: null,
    alarmTimer: null,
    isAlarmActive: false,
    activeSlot: null,

    async init() {
        if ('Notification' in window) {
            this.permission = Notification.permission;
        }

        // Daftarkan Service Worker untuk integrasi notifikasi sistem operasi
        if ('serviceWorker' in navigator) {
            try {
                this.swRegistration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
                console.log('[Tirtafield] Service Worker terdaftar untuk Notifikasi & Layar Terkunci.');

                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && event.data.type === 'NOTIFICATION_OPENED') {
                        window.dispatchEvent(new CustomEvent('tirtafield:notification-clicked', {
                            detail: event.data.data
                        }));
                    }
                });
            } catch (err) {
                console.warn('[Tirtafield] Service Worker gagal didaftarkan:', err);
            }
        }
    },

    isStandalone() {
        return (window.navigator.standalone === true) || 
               (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
               (window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches);
    },

    unlockAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            if (!this.audioCtx) this.audioCtx = new AudioContext();
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            const buffer = this.audioCtx.createBuffer(1, 1, 22050);
            const source = this.audioCtx.createBufferSource();
            source.buffer = buffer;
            source.connect(this.audioCtx.destination);
            source.start(0);
        } catch(e) {}
    },

    async requestPermission() {
        this.unlockAudio();
        if (!('Notification' in window)) {
            alert('Perangkat atau browser Anda belum mengizinkan Web Notification API.');
            return false;
        }

        try {
            const perm = await Notification.requestPermission();
            this.permission = perm;
            if (perm === 'granted') {
                console.log('[Tirtafield] Izin notifikasi berhasil diberikan.');
                return true;
            } else {
                console.warn('[Tirtafield] Izin notifikasi ditolak/diblokir.');
                return false;
            }
        } catch (e) {
            console.error('[Tirtafield] Error saat meminta izin notifikasi:', e);
            return false;
        }
    },

    isPermissionGranted() {
        return ('Notification' in window) && Notification.permission === 'granted';
    },

    /**
     * Membunyikan suara alarm nyaring menggunakan Web Audio API
     * (Bekerja di semua browser mobile & desktop tanpa memerlukan file eksternal)
     */
    playAlarmSound() {
        this.stopAlarmSound();
        this.isAlarmActive = true;

        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            this.audioCtx = new AudioContext();

            // Pola melodi alarm presensi 4 nada bergantian (E5 -> G#5 -> B5 -> E6)
            const playCycle = () => {
                if (!this.isAlarmActive || !this.audioCtx) return;

                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }

                const now = this.audioCtx.currentTime;
                const freqs = [659.25, 830.61, 987.77, 1318.51, 987.77, 1318.51];
                
                freqs.forEach((freq, idx) => {
                    const osc = this.audioCtx.createOscillator();
                    const gain = this.audioCtx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + (idx * 0.12));

                    gain.gain.setValueAtTime(0, now + (idx * 0.12));
                    gain.gain.linearRampToValueAtTime(0.35, now + (idx * 0.12) + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + (idx * 0.12) + 0.11);

                    osc.connect(gain);
                    gain.connect(this.audioCtx.destination);

                    osc.start(now + (idx * 0.12));
                    osc.stop(now + (idx * 0.12) + 0.12);
                });

                // Getar perangkat jika didukung
                if ('vibrate' in navigator) {
                    navigator.vibrate([400, 150, 400, 150, 400]);
                }
            };

            // Mainkan siklus pertama
            playCycle();
            // Ulangi alarm setiap 1.5 detik sampai dihentikan pengguna
            this.alarmTimer = setInterval(playCycle, 1500);
        } catch (err) {
            console.warn('[Tirtafield] Gagal memutar Web Audio Alarm:', err);
        }
    },

    stopAlarmSound() {
        this.isAlarmActive = false;
        if (this.alarmTimer) {
            clearInterval(this.alarmTimer);
            this.alarmTimer = null;
        }
        if (this.audioCtx) {
            try {
                this.audioCtx.close();
            } catch (e) {}
            this.audioCtx = null;
        }
        if ('vibrate' in navigator) {
            navigator.vibrate(0);
        }
    },

    /**
     * Memunculkan notifikasi sistem di perangkat pengguna:
     * - Banner Notification (Heads-Up Notification di layar)
     * - Layar Terkunci (Lock Screen Notification)
     * - Pusat Pemberitahuan (Notification Center / Drawer)
     */
    async showDeviceNotification({ title, body, slotType = 'masuk', slotTitle = 'Presensi Masuk', url = '/dashboard' }) {
        if (!('Notification' in window)) return false;

        if (Notification.permission !== 'granted') {
            const granted = await this.requestPermission();
            if (!granted) return false;
        }

        const notifTitle = title || `⏰ ALARM PRESENSI: ${slotTitle.toUpperCase()}`;
        const notifBody = body || `Waktunya melakukan presensi ${slotTitle}! Buka aplikasi untuk verifikasi titik lokasi dan swafoto.`;

        const options = {
            body: notifBody,
            icon: '/images/app.jpg',
            badge: '/images/app.jpg',
            tag: `tirtafield-alarm-${slotType}`,
            renotify: true,
            requireInteraction: true, // WAJIB: Memastikan banner notifikasi tetap ada dan tampil di Layar Terkunci & Pusat Pemberitahuan
            silent: false,
            vibrate: [500, 200, 500, 200, 500],
            timestamp: Date.now(),
            data: {
                url: url,
                slotType: slotType,
                slotTitle: slotTitle,
                time: new Date().toLocaleTimeString('id-ID')
            },
            actions: [
                { action: 'open_presensi', title: 'Buka Presensi' },
                { action: 'dismiss_alarm', title: 'Tutup Alarm' }
            ]
        };

        // 1. Prioritas: Gunakan Service Worker Registration agar dapat tampil di Lock Screen & Notification Center
        if (this.swRegistration && 'showNotification' in this.swRegistration) {
            try {
                await this.swRegistration.showNotification(notifTitle, options);
                return true;
            } catch (e) {
                console.warn('[Tirtafield] Gagal via Service Worker, fallback ke window Notification:', e);
            }
        }

        // 2. Fallback jika Service Worker belum ready
        if ('serviceWorker' in navigator) {
            try {
                const reg = await navigator.serviceWorker.ready;
                if (reg && 'showNotification' in reg) {
                    await reg.showNotification(notifTitle, options);
                    return true;
                }
            } catch (e) {}

            try {
                if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({
                        type: 'SHOW_NOTIFICATION',
                        payload: Object.assign({}, options, { title: notifTitle })
                    });
                }
            } catch (e) {}
        }

        // 3. Fallback standard Web Notification
        try {
            const notif = new Notification(notifTitle, {
                body: options.body,
                icon: options.icon,
                badge: options.badge,
                tag: options.tag,
                requireInteraction: true
            });
            notif.onclick = () => {
                window.focus();
                window.location.href = url;
            };
            return true;
        } catch (err) {
            console.error('[Tirtafield] Gagal memunculkan notifikasi:', err);
            return false;
        }
    },

    /**
     * Memicu alur alarm lengkap:
     * Suara Keras + Getar + Notifikasi Perangkat (Banner, Lock Screen, Pusat Pemberitahuan) + In-App Banner
     */
    async triggerFullAlarm({ title, body, slotType = 'masuk', slotTitle = 'Presensi Masuk', playSound = true, vibrate = true, showDeviceNotif = true }) {
        this.activeSlot = slotType;

        // 1. Munculkan notifikasi ke sistem operasi perangkat (Banner, Lock Screen, Pusat Pemberitahuan)
        if (showDeviceNotif) {
            await this.showDeviceNotification({ title, body, slotType, slotTitle });
        }

        // 2. Bunyikan audio alarm dan getaran perangkat
        if (playSound) {
            this.playAlarmSound();
        } else if (vibrate && ('vibrate' in navigator)) {
            navigator.vibrate([400, 150, 400, 150, 400]);
        }

        // 3. Kirim event internal ke UI halaman web untuk menampilkan Banner In-App & Dialog Interaktif
        window.dispatchEvent(new CustomEvent('tirtafield:alarm', {
            detail: {
                title: title || `Alarm Presensi: ${slotTitle}`,
                body: body || `Waktunya melakukan ${slotTitle}. Silakan lakukan verifikasi lokasi dan bukti swafoto.`,
                slotType: slotType,
                slotTitle: slotTitle
            }
        }));
    },

    /**
     * Menguji fungsi alarm dan notifikasi secara langsung
     */
    async testAlarm(slotType = 'masuk', slotTitle = 'Presensi Masuk') {
        const title = `⏰ UJI ALARM PRESENSI: ${slotTitle.toUpperCase()}`;
        const body = `Pengingat presensi ${slotTitle} aktif! Notifikasi ini muncul di Banner, Layar Terkunci, dan Pusat Pemberitahuan perangkat Anda.`;

        await this.triggerFullAlarm({
            title: title,
            body: body,
            slotType: slotType,
            slotTitle: slotTitle,
            playSound: true
        });
    }
};

// Inisialisasi otomatis saat script dimuat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.TirtafieldAlarm.init());
} else {
    window.TirtafieldAlarm.init();
}

// Buka kunci AudioContext secara otomatis pada sentuhan atau klik pertama pengguna (Mobile Safari / Android Chrome)
const unlockOnUserGesture = () => {
    if (window.TirtafieldAlarm && typeof window.TirtafieldAlarm.unlockAudio === 'function') {
        window.TirtafieldAlarm.unlockAudio();
    }
};
['click', 'touchstart', 'touchend', 'keydown'].forEach((evt) => {
    window.addEventListener(evt, unlockOnUserGesture, { once: true, passive: true });
});

