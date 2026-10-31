<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

function submit() {
    form.post('/auth/force-change-password', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

function handleLogout() {
    router.post('/logout');
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 overflow-y-auto"
                aria-modal="true"
                role="dialog"
            >
                <!-- Locked Backdrop (no click-to-close) -->
                <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm pointer-events-auto"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200/80 w-full max-w-lg overflow-hidden z-10 my-auto animate-in fade-in zoom-in-95 duration-300">
                    
                    <!-- Header with Lock Theme -->
                    <div class="bg-gradient-to-r from-emerald-800 via-emerald-700 to-emerald-900 text-white p-6 relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/5 pointer-events-none blur-xl"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center text-amber-300 shadow-inner shrink-0">
                                <span class="material-symbols-outlined text-2xl font-bold">lock_reset</span>
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-200 text-[10px] font-bold uppercase tracking-wider mb-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    Tindakan Wajib
                                </div>
                                <h3 class="text-xl font-extrabold text-white leading-tight">Ubah Kata Sandi</h3>
                                <p class="text-xs text-emerald-100/90 mt-0.5">Sistem Manajemen Terpadu Ulil Albab</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notice & Form Body -->
                    <div class="p-6 sm:p-7 space-y-5">
                        
                        <!-- Notice Alert Box -->
                        <div class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-200/80 text-xs text-amber-950 flex items-start gap-3">
                            <span class="material-symbols-outlined text-amber-600 text-xl shrink-0 mt-0.5">info</span>
                            <div class="leading-relaxed">
                                Ini adalah login pertama Anda atau kata sandi Anda baru saja di-reset oleh SuperAdmin. Demi keamanan dan privasi data akun, Anda diwajibkan membuat kata sandi baru untuk melanjutkan penggunaan sistem.
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-4">
                            
                            <!-- Input Password Baru -->
                            <div>
                                <label for="new_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Kata Sandi Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <span class="material-symbols-outlined text-[19px]">lock</span>
                                    </div>
                                    <input
                                        id="new_password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Minimal 8 karakter"
                                        autocomplete="new-password"
                                        required
                                        class="w-full pl-10 pr-11 py-2.5 bg-slate-50 hover:bg-white text-sm font-medium rounded-xl border border-slate-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all text-slate-800 placeholder:text-slate-400"
                                    >
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer"
                                        tabindex="-1"
                                    >
                                        <span class="material-symbols-outlined text-[19px]">
                                            {{ showPassword ? 'visibility' : 'visibility_off' }}
                                        </span>
                                    </button>
                                </div>
                                <div v-if="form.errors.password" class="text-rose-600 text-xs mt-1.5 font-medium ml-1">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Input Konfirmasi Password Baru -->
                            <div>
                                <label for="confirm_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <span class="material-symbols-outlined text-[19px]">lock_clock</span>
                                    </div>
                                    <input
                                        id="confirm_password"
                                        v-model="form.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        placeholder="Ketik ulang kata sandi baru"
                                        autocomplete="new-password"
                                        required
                                        class="w-full pl-10 pr-11 py-2.5 bg-slate-50 hover:bg-white text-sm font-medium rounded-xl border border-slate-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all text-slate-800 placeholder:text-slate-400"
                                    >
                                    <button
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer"
                                        tabindex="-1"
                                    >
                                        <span class="material-symbols-outlined text-[19px]">
                                            {{ showConfirmPassword ? 'visibility' : 'visibility_off' }}
                                        </span>
                                    </button>
                                </div>
                                <div v-if="form.errors.password_confirmation" class="text-rose-600 text-xs mt-1.5 font-medium ml-1">
                                    {{ form.errors.password_confirmation }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-3 space-y-2.5">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-3 px-5 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-emerald-900/10 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-lg animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-lg">check_circle</span>
                                    <span>{{ form.processing ? 'Menyimpan Kata Sandi...' : 'Simpan Kata Sandi & Lanjutkan' }}</span>
                                </button>

                                <button
                                    type="button"
                                    @click="handleLogout"
                                    class="w-full py-2 px-4 rounded-xl text-xs font-semibold text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-sm">logout</span>
                                    <span>Keluar dari Akun</span>
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

