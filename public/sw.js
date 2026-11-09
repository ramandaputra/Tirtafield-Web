/**
 * Tirtafield Mobile Presensi - Service Worker
 * Mengelola Push Notification, Banner Notifikasi, Layar Terkunci & Pusat Pemberitahuan
 */

const CACHE_NAME = 'tirtafield-pwa-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Listener untuk pesan internal dari web page (show notification on demand)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        const payload = event.data.payload || {};
        const title = payload.title || 'Alarm Presensi Tirtafield';
        const options = {
            body: payload.body || 'Waktunya melakukan presensi mobile Tirtafield.',
            icon: payload.icon || '/images/app.jpg',
            badge: payload.badge || '/images/app.jpg',
            image: payload.image || null,
            tag: payload.tag || 'tirtafield-alarm-' + Date.now(),
            renotify: true,
            requireInteraction: true, // Banner tetap muncul dan menetap di Pusat Pemberitahuan
            silent: false,
            vibrate: payload.vibrate || [500, 200, 500, 200, 500],
            timestamp: payload.timestamp || Date.now(),
            data: payload.data || { url: '/dashboard' },
            actions: [
                { action: 'open_presensi', title: 'Buka Presensi' },
                { action: 'dismiss_alarm', title: 'Tutup Alarm' }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(title, options)
        );
    }
});

// Listener untuk Push Notification dari server (Web Push)
self.addEventListener('push', (event) => {
    let data = {
        title: 'Pengingat Presensi Tirtafield',
        body: 'Waktunya melakukan presensi mobile Tirtafield.',
        icon: '/images/app.jpg',
        badge: '/images/app.jpg',
        url: '/dashboard'
    };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/images/app.jpg',
        badge: data.badge || '/images/app.jpg',
        image: data.image || null,
        tag: data.tag || 'tirtafield-notification-' + Date.now(),
        renotify: true,
        requireInteraction: true,
        vibrate: [500, 200, 500, 200, 500],
        timestamp: Date.now(),
        data: { url: data.url || '/dashboard' },
        actions: [
            { action: 'open_presensi', title: 'Buka Presensi' },
            { action: 'dismiss_alarm', title: 'Tutup' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Listener saat notifikasi diklik pengguna di Banner, Layar Terkunci, atau Pusat Pemberitahuan
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'dismiss_alarm') {
        return;
    }

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    client.postMessage({ type: 'NOTIFICATION_OPENED', data: event.notification.data });
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

