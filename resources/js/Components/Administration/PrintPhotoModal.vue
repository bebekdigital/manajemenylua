<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    count: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'print']);

const settings = ref({
    photoSize: '3x4',
    paperSize: 'A4',
    showName: true,
});

function onPrint() {
    emit('print', { ...settings.value });
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="$emit('close')"
            >
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95"
                    leave-active-class="transition-all duration-150"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        class="relative w-full max-w-md bg-surface rounded-3xl shadow-2xl overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30 bg-surface-container-low">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                                    <span class="material-symbols-outlined text-lg">print</span>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-on-surface">Pengaturan Cetak</h3>
                                    <p class="text-xs text-on-surface-variant">{{ count }} foto dipilih</p>
                                </div>
                            </div>
                            <button
                                @click="$emit('close')"
                                class="p-1.5 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6 space-y-6">
                            <!-- Ukuran Foto -->
                            <div>
                                <h4 class="text-sm font-bold text-on-surface mb-3">Ukuran Foto</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        @click="settings.photoSize = '3x4'"
                                        :class="[
                                            'px-4 py-3 border rounded-2xl flex flex-col items-center gap-2 transition-all cursor-pointer',
                                            settings.photoSize === '3x4'
                                                ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                                : 'border-outline-variant/50 hover:bg-surface-container text-on-surface-variant'
                                        ]"
                                    >
                                        <div class="w-6 h-8 bg-current opacity-20 rounded shadow-sm"></div>
                                        <span class="text-sm font-bold">3 x 4</span>
                                        <span class="text-[10px] font-medium opacity-70">2.8 x 3.8 cm</span>
                                    </button>
                                    <button
                                        @click="settings.photoSize = '4x6'"
                                        :class="[
                                            'px-4 py-3 border rounded-2xl flex flex-col items-center gap-2 transition-all cursor-pointer',
                                            settings.photoSize === '4x6'
                                                ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                                : 'border-outline-variant/50 hover:bg-surface-container text-on-surface-variant'
                                        ]"
                                    >
                                        <div class="w-8 h-12 bg-current opacity-20 rounded shadow-sm"></div>
                                        <span class="text-sm font-bold">4 x 6</span>
                                        <span class="text-[10px] font-medium opacity-70">3.8 x 5.8 cm</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Ukuran Kertas -->
                            <div>
                                <h4 class="text-sm font-bold text-on-surface mb-3">Ukuran Kertas</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        @click="settings.paperSize = 'A4'"
                                        :class="[
                                            'px-4 py-3 border rounded-2xl flex flex-col gap-0.5 transition-all cursor-pointer text-left',
                                            settings.paperSize === 'A4'
                                                ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                                : 'border-outline-variant/50 hover:bg-surface-container text-on-surface-variant'
                                        ]"
                                    >
                                        <span class="text-sm font-bold">Kertas A4</span>
                                        <span class="text-[10px] font-medium opacity-70">21 x 29.7 cm</span>
                                    </button>
                                    <button
                                        @click="settings.paperSize = 'F4'"
                                        :class="[
                                            'px-4 py-3 border rounded-2xl flex flex-col gap-0.5 transition-all cursor-pointer text-left',
                                            settings.paperSize === 'F4'
                                                ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                                : 'border-outline-variant/50 hover:bg-surface-container text-on-surface-variant'
                                        ]"
                                    >
                                        <span class="text-sm font-bold">F4 / Folio</span>
                                        <span class="text-[10px] font-medium opacity-70">21.5 x 33 cm</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Opsi Tampilan -->
                            <div>
                                <label class="flex items-center gap-3 p-3 bg-surface-container-low rounded-xl border border-outline-variant/30 cursor-pointer hover:bg-surface-container transition-colors">
                                    <input
                                        type="checkbox"
                                        v-model="settings.showName"
                                        class="w-5 h-5 rounded text-primary focus:ring-primary border-outline-variant/50 bg-surface cursor-pointer"
                                    />
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-on-surface">Tampilkan Nama & NISN</span>
                                        <span class="text-[11px] text-on-surface-variant">Menambahkan label teks kecil di bawah setiap foto</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low">
                            <button
                                @click="$emit('close')"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                @click="onPrint"
                                class="flex items-center gap-2 px-5 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded-xl text-sm font-bold transition-all active:scale-95 cursor-pointer shadow-sm"
                            >
                                <span class="material-symbols-outlined text-base">print</span>
                                Cetak Sekarang
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
