<script setup>
import { computed } from 'vue';
import { usePwaInstall } from '@/Composables/usePwaInstall';

const {
    showGuideModal,
    guideType,
    isIos,
    isPromptReady,
    canPromptDirectly,
    installPwa,
    closeGuideModal,
} = usePwaInstall();

const isIosDevice = computed(() => guideType.value === 'ios' || isIos.value);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showGuideModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
                @click.self="closeGuideModal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pwa-modal-title"
            >
                <div
                    class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all p-6 sm:p-7 text-slate-800"
                    @click.stop
                >
                    <!-- Close button -->
                    <button
                        type="button"
                        @click="closeGuideModal"
                        class="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup dialog"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>

                    <!-- Icon & Header -->
                    <div class="flex items-center gap-3.5 mb-5">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200/80 p-1 flex items-center justify-center shadow-xs shrink-0">
                            <img
                                src="/images/icons/icon-192x192.png"
                                alt="Logo Yayasan Li Ulil Albab"
                                class="w-full h-full object-contain rounded-xl"
                            />
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                Aplikasi Web Progresif (PWA)
                            </span>
                            <h3 id="pwa-modal-title" class="text-base sm:text-lg font-extrabold text-slate-900 leading-tight mt-1">
                                {{ guideType === 'installed' ? 'Aplikasi Sudah Terpasang' : 'Pasang Aplikasi di Perangkat' }}
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Yayasan Li Ulil Albab Karanganyar</p>
                        </div>
                    </div>

                    <!-- KONTEN: JIKA SUDAH TERPASANG -->
                    <div v-if="guideType === 'installed'" class="space-y-4">
                        <div class="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200/70 text-emerald-950 text-xs sm:text-[13px] leading-relaxed flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-700 text-[22px] shrink-0">check_circle</span>
                            <div>
                                <p class="font-bold text-emerald-900 mb-0.5">Aplikasi telah terpasang!</p>
                                <p class="text-emerald-800">
                                    Sistem ini sudah dapat Anda buka langsung dari layar utama (Home Screen) atau daftar aplikasi perangkat Anda tanpa melalui browser.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="closeGuideModal"
                            class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm transition-all cursor-pointer shadow-md"
                        >
                            Tutup
                        </button>
                    </div>

                    <!-- KONTEN: JIKA PERANGKAT iOS (SAFARI) -->
                    <div v-else-if="isIosDevice" class="space-y-4">
                        <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                            Di perangkat <strong>iPhone / iPad</strong>, aplikasi dapat dipasang melalui peramban <strong>Safari</strong> dengan beberapa langkah mudah:
                        </p>

                        <div class="space-y-2.5">
                            <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                                <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center shrink-0 text-[11px]">1</span>
                                <div class="flex-1">
                                    <p class="font-semibold text-slate-900">Ketuk tombol Bagikan (Share)</p>
                                    <p class="text-slate-500 text-[11.5px] mt-0.5 flex items-center gap-1">
                                        Ikon kotak panah atas
                                        <span class="material-symbols-outlined text-[15px] text-sky-700">ios_share</span>
                                        di bilah bawah Safari.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                                <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center shrink-0 text-[11px]">2</span>
                                <div class="flex-1">
                                    <p class="font-semibold text-slate-900">Pilih "Tambahkan ke Layar Utama"</p>
                                    <p class="text-slate-500 text-[11.5px] mt-0.5 flex items-center gap-1">
                                        Gulir ke bawah dan ketuk opsi <strong>Add to Home Screen</strong>
                                        <span class="material-symbols-outlined text-[15px] text-slate-700">add_box</span>.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                                <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center shrink-0 text-[11px]">3</span>
                                <div class="flex-1">
                                    <p class="font-semibold text-slate-900">Ketuk "Tambah" (Add)</p>
                                    <p class="text-slate-500 text-[11.5px] mt-0.5">
                                        Di pojok kanan atas layar untuk menyelesaikan pemasangan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="closeGuideModal"
                            class="w-full py-3 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs sm:text-sm transition-all cursor-pointer shadow-md"
                        >
                            Saya Mengerti
                        </button>
                    </div>

                    <!-- KONTEN: JIKA BROWSER MENDUKUNG PROMPT LANGSUNG ATAU MANUAL CHROME/EDGE -->
                    <div v-else class="space-y-4">
                        <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                            Pasang aplikasi di layar perangkat Anda untuk akses lebih cepat, tampilan layar penuh tanpa bilah peramban, dan kinerja optimal:
                        </p>

                        <!-- Jika native prompt tersedia -->
                        <div v-if="canPromptDirectly" class="pt-1">
                            <button
                                type="button"
                                @click="installPwa"
                                class="w-full py-3.5 px-5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-emerald-700/25 flex items-center justify-center gap-2 transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">download</span>
                                <span>Pasang Aplikasi Sekarang</span>
                            </button>
                        </div>

                        <!-- Petunjuk alternatif jika manual -->
                        <div class="space-y-2.5 pt-1">
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                                <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-700">smartphone</span>
                                    <span>Di HP Android (Chrome):</span>
                                </p>
                                <p class="text-slate-600 text-[11.5px] leading-relaxed">
                                    Ketuk menu titik tiga (<strong>⋮</strong>) di pojok kanan atas browser, lalu pilih <strong>"Instal aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.
                                </p>
                            </div>

                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                                <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-sky-700">laptop_chromebook</span>
                                    <span>Di Komputer / Laptop (Chrome/Edge):</span>
                                </p>
                                <p class="text-slate-600 text-[11.5px] leading-relaxed">
                                    Klik ikon <strong>Instal</strong> (komputer dengan tanda panah ke bawah) di sebelah kanan bilah alamat URL browser Anda.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="closeGuideModal"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

