import { ref, computed } from 'vue';

// Global singleton state across all pages and components
const deferredPrompt = ref(null);
const isInstalled = ref(false);
const isPromptReady = ref(false);
const showGuideModal = ref(false);
const guideType = ref('manual'); // 'ios' | 'manual' | 'installed'
let isInitialized = false;

export function usePwaInstall() {
    const isStandalone = () => {
        if (typeof window === 'undefined') return false;
        return (
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true ||
            document.referrer.includes('android-app://')
        );
    };

    const isIos = computed(() => {
        if (typeof window === 'undefined') return false;
        const ua = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(ua);
    });

    const isAndroid = computed(() => {
        if (typeof window === 'undefined') return false;
        const ua = window.navigator.userAgent.toLowerCase();
        return /android/.test(ua);
    });

    const canPromptDirectly = computed(() => Boolean(deferredPrompt.value));

    const checkInstalledStatus = () => {
        if (typeof window === 'undefined') return;
        if (isStandalone()) {
            isInstalled.value = true;
            isPromptReady.value = false;
        }
    };

    const initPwa = () => {
        if (isInitialized || typeof window === 'undefined') return;
        isInitialized = true;

        checkInstalledStatus();

        // 1. Tangkap event beforeinstallprompt (Chrome, Edge, Android)
        window.addEventListener('beforeinstallprompt', (e) => {
            // Cegah banner default mini-infobar browser agar kita bisa memicu dari tombol custom
            e.preventDefault();
            deferredPrompt.value = e;
            isPromptReady.value = true;
        });

        // 2. Event saat aplikasi berhasil dipasang
        window.addEventListener('appinstalled', () => {
            isInstalled.value = true;
            isPromptReady.value = false;
            deferredPrompt.value = null;
            showGuideModal.value = false;
        });

        // 3. Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker
                    .register('/sw.js', { scope: '/' })
                    .then((reg) => {
                        // Cek update
                        reg.onupdatefound = () => {
                            const installingWorker = reg.installing;
                            if (installingWorker) {
                                installingWorker.onstatechange = () => {
                                    if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        console.log('[PWA] Versi baru tersedia, silakan refresh.');
                                    }
                                };
                            }
                        };
                    })
                    .catch((err) => {
                        console.warn('[PWA] Service worker registration failed:', err);
                    });
            });
        }
    };

    const installPwa = async () => {
        checkInstalledStatus();

        if (isInstalled.value) {
            guideType.value = 'installed';
            showGuideModal.value = true;
            return { status: 'already_installed' };
        }

        // Jika browser mendukung native prompt dan prompt siap
        if (deferredPrompt.value) {
            try {
                const promptEvent = deferredPrompt.value;
                await promptEvent.prompt();
                const choiceResult = await promptEvent.userChoice;
                
                if (choiceResult.outcome === 'accepted') {
                    isInstalled.value = true;
                    isPromptReady.value = false;
                    deferredPrompt.value = null;
                    return { status: 'accepted' };
                } else {
                    return { status: 'dismissed' };
                }
            } catch (err) {
                console.error('[PWA] Error showing prompt:', err);
            }
        }

        // Jika di perangkat iOS (Safari tidak mendukung beforeinstallprompt)
        if (isIos.value) {
            guideType.value = 'ios';
            showGuideModal.value = true;
            return { status: 'ios_guide' };
        }

        // Jika di desktop/android tapi prompt belum tertangkap atau sudah pernah diabaikan
        guideType.value = 'manual';
        showGuideModal.value = true;
        return { status: 'manual_guide' };
    };

    const closeGuideModal = () => {
        showGuideModal.value = false;
    };

    return {
        deferredPrompt,
        isPromptReady,
        isInstalled,
        isIos,
        isAndroid,
        canPromptDirectly,
        showGuideModal,
        guideType,
        initPwa,
        installPwa,
        closeGuideModal,
    };
}
