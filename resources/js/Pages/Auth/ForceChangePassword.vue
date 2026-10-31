<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: null });

const props = defineProps({
    user: {
        type: Object,
        default: () => ({ name: '', username: '' }),
    },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post('/auth/force-change-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
  <Head title="Buat Kata Sandi Baru - Yayasan Li Ulil Albab Karanganyar" />

  <div class="bg-foundation-bg min-h-screen text-foundation-text flex flex-col justify-between antialiased selection:bg-primary/20 selection:text-primary font-sans">
    <main class="flex-grow flex items-center justify-center p-4 sm:p-6 lg:p-10">
      <div class="w-full max-w-4xl bg-foundation-surface rounded-2xl shadow-xl shadow-slate-200/60 border border-foundation-border/70 overflow-hidden flex flex-col md:flex-row min-h-0 md:min-h-[520px]">
        
        <!-- Section Kiri: Informasi Keamanan -->
        <section class="md:w-5/12 bg-gradient-to-br from-[#005a30] via-[#006837] to-[#044426] text-white p-6 md:p-10 flex flex-col justify-between relative overflow-hidden">
          <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-white/5 pointer-events-none blur-2xl hidden md:block"></div>
          <div class="absolute -left-12 -bottom-12 w-64 h-64 rounded-full bg-accent-gold/10 pointer-events-none blur-3xl hidden md:block"></div>
          <div class="absolute inset-0 opacity-[0.04] pointer-events-none" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 24px 24px;"></div>

          <div class="relative z-10">
            <div class="flex items-center gap-3.5 mb-6">
              <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-accent-gold shadow-inner shrink-0">
                <span class="material-symbols-outlined text-xl md:text-2xl font-bold">lock_reset</span>
              </div>
              <div>
                <span class="text-[10px] md:text-xs uppercase tracking-widest text-emerald-200/80 font-semibold block">Keamanan Akun</span>
                <h2 class="font-bold text-base md:text-lg leading-tight tracking-tight text-white">Yayasan Li Ulil Albab</h2>
                <span class="text-xs text-emerald-200/90">Karanganyar</span>
              </div>
            </div>

            <div class="space-y-3">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/20 border border-amber-300/30 text-xs text-amber-200 font-medium font-inter">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                Aktivasi Akun / Reset Sandi
              </div>
              <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight leading-tight text-white">
                Buat Kata Sandi <br>
                <span class="text-accent-gold font-normal italic">Baru Anda</span>
              </h1>
              <p class="text-xs sm:text-sm font-inter text-emerald-100/80 leading-relaxed pt-1">
                Demi keamanan data pribadi dan institusi, akun yang baru dibuat atau direset oleh Superadmin diwajibkan memperbarui kata sandi sebelum dapat masuk ke sistem.
              </p>
            </div>
          </div>

          <div class="relative z-10 pt-6 mt-6 border-t border-white/15">
            <div class="flex items-center gap-2.5 text-emerald-100/90 text-xs font-inter">
              <span class="material-symbols-outlined text-accent-gold text-lg">shield</span>
              <span>Gunakan kombinasi minimal 8 karakter yang aman.</span>
            </div>
          </div>
        </section>

        <!-- Section Kanan: Form Ganti Sandi -->
        <section class="md:w-7/12 p-8 sm:p-10 flex flex-col justify-center bg-foundation-surface">
          <div class="max-w-md w-full mx-auto">
            
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-foundation-text tracking-tight">Atur Kata Sandi Baru</h2>
              <p class="text-xs sm:text-sm font-inter text-foundation-muted mt-1">
                Akun: <strong class="text-on-surface">{{ props.user.name }}</strong> ({{ props.user.username }})
              </p>
            </div>

            <!-- Warning Box -->
            <div class="p-3.5 mb-5 rounded-xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900 flex items-start gap-2.5">
              <span class="material-symbols-outlined text-amber-600 text-lg shrink-0 mt-0.5">info</span>
              <div>
                Silakan masukkan kata sandi baru yang hanya diketahui oleh Anda sendiri.
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              
              <!-- Input Password Baru -->
              <div>
                <label for="password" class="block text-xs font-semibold text-foundation-text uppercase tracking-wider mb-1.5">
                  Kata Sandi Baru <span class="text-error">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-foundation-muted">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                  </div>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    placeholder="Minimal 8 karakter"
                    autocomplete="new-password"
                    required
                    class="w-full pl-10 pr-11 py-2.5 bg-slate-50 hover:bg-white text-sm font-inter rounded-lg border border-foundation-border focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400 text-foundation-text"
                  >
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-foundation-muted hover:text-foundation-text cursor-pointer"
                  >
                    <span class="material-symbols-outlined text-[20px]">{{ showPassword ? 'visibility' : 'visibility_off' }}</span>
                  </button>
                </div>
                <div v-if="form.errors.password" class="text-error text-xs mt-1.5 ml-1">{{ form.errors.password }}</div>
              </div>

              <!-- Input Konfirmasi Password Baru -->
              <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-foundation-text uppercase tracking-wider mb-1.5">
                  Ulangi Kata Sandi Baru <span class="text-error">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-foundation-muted">
                    <span class="material-symbols-outlined text-[20px]">lock_clock</span>
                  </div>
                  <input
                    v-model="form.password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    id="password_confirmation"
                    placeholder="Ulangi kata sandi baru"
                    autocomplete="new-password"
                    required
                    class="w-full pl-10 pr-11 py-2.5 bg-slate-50 hover:bg-white text-sm font-inter rounded-lg border border-foundation-border focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400 text-foundation-text"
                  >
                  <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-foundation-muted hover:text-foundation-text cursor-pointer"
                  >
                    <span class="material-symbols-outlined text-[20px]">{{ showConfirmPassword ? 'visibility' : 'visibility_off' }}</span>
                  </button>
                </div>
                <div v-if="form.errors.password_confirmation" class="text-error text-xs mt-1.5 ml-1">{{ form.errors.password_confirmation }}</div>
              </div>

              <!-- Submit Button -->
              <div class="pt-3">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="w-full flex items-center justify-center gap-2 py-3 px-5 rounded-lg bg-primary hover:bg-[#00552d] active:scale-[0.99] text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed"
                >
                  <span v-if="!form.processing">Simpan Kata Sandi &amp; Masuk</span>
                  <span v-else>Menyimpan...</span>
                  <span class="material-symbols-outlined text-[18px]">check_circle</span>
                </button>
              </div>

              <!-- Logout Button -->
              <div class="pt-2 text-center">
                <button
                  type="button"
                  @click="handleLogout"
                  class="text-xs font-inter text-foundation-muted hover:text-error transition-colors cursor-pointer inline-flex items-center gap-1"
                >
                  <span class="material-symbols-outlined text-sm">logout</span>
                  <span>Keluar dari Akun</span>
                </button>
              </div>

            </form>

          </div>
        </section>

      </div>
    </main>
  </div>
</template>

<style scoped>
.font-inter {
  font-family: 'Inter', sans-serif;
}
</style>
