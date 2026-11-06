// Emergency cache recovery script: unregisters service workers, clears Cache Storage, and reloads
(async function() {
    console.warn('[PWA] Stale asset detected. Clearing service workers and cache storage...');
    try {
        if ('serviceWorker' in navigator) {
            const regs = await navigator.serviceWorker.getRegistrations();
            for (const reg of regs) {
                await reg.unregister();
            }
        }
        if ('caches' in window) {
            const names = await caches.keys();
            for (const name of names) {
                await caches.delete(name);
            }
        }
    } catch (e) {
        console.error('[PWA] Cache purge error:', e);
    }
    window.location.reload();
})();
export default {};
