<script setup>
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: null });

const form = useForm({
    user_id: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
<template>
  <Head title="Login Portal - Yayasan Li Ulil Albab Karanganyar" />

  <div class="bg-foundation-bg min-h-screen text-foundation-text flex flex-col justify-between antialiased selection:bg-primary/20 selection:text-primary font-sans">
    <!-- Main Container: Centered Institutional Two-Section Container -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-6 lg:p-10">
      <div class="w-full max-w-5xl bg-foundation-surface rounded-2xl shadow-xl shadow-slate-200/60 border border-foundation-border/70 overflow-hidden flex flex-col md:flex-row min-h-[580px]">
        
        <!-- Section Kiri: Judul Portal & Identitas Yayasan -->
        <section class="md:w-5/12 bg-gradient-to-br from-[#005a30] via-[#006837] to-[#044426] text-white p-8 md:p-12 flex flex-col justify-between relative overflow-hidden">
          <!-- Subtle Decorative Background Pattern -->
          <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-white/5 pointer-events-none blur-2xl"></div>
          <div class="absolute -left-12 -bottom-12 w-64 h-64 rounded-full bg-accent-gold/10 pointer-events-none blur-3xl"></div>
          <div class="absolute inset-0 opacity-[0.04] pointer-events-none" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 24px 24px;"></div>

          <!-- Top Header & Logo Identity -->
          <div class="relative z-10">
            <div class="flex items-center gap-3.5 mb-8">
              <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-accent-gold shadow-inner">
                <span class="material-symbols-outlined text-2xl font-bold">account_balance</span>
              </div>
              <div>
                <span class="text-xs uppercase tracking-widest text-emerald-200/80 font-semibold block">Sistem Terpadu</span>
                <h2 class="font-bold text-lg leading-tight tracking-tight text-white">Yayasan Li Ulil Albab</h2>
                <span class="text-xs text-emerald-200/90">Karanganyar</span>
              </div>
            </div>

            <!-- Judul Portal & Deskripsi -->
            <div class="mt-4 space-y-3">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-xs text-emerald-100 font-medium font-inter">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Portal Resmi Akademik &amp; SDM
              </div>
              <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight text-white">
                Pusat Data <br>
                <span class="text-accent-gold font-normal italic">Terintegrasi</span>
              </h1>
              <p class="text-sm font-inter text-emerald-100/80 leading-relaxed pt-2">
                Gerbang autentikasi terpusat untuk monitoring akademik, pengelolaan data siswa, kepegawaian, dan tata kelola yayasan.
              </p>
            </div>
          </div>

          <!-- Bottom Informational / Trust Badge -->
          <div class="relative z-10 pt-8 mt-8 border-t border-white/15">
            <div class="flex items-center gap-3 text-emerald-100/90 text-xs font-inter">
              <span class="material-symbols-outlined text-accent-gold text-lg">verified_user</span>
              <span class="">Enkripsi Institusional 256-bit aman &amp; terverifikasi.</span>
            </div>
          </div>
        </section>

        <!-- Section Kanan: Form Login -->
        <section class="md:w-7/12 p-8 sm:p-10 lg:p-12 flex flex-col justify-center bg-foundation-surface">
          <div class="max-w-md w-full mx-auto">
            
            <!-- Heading Form -->
            <div class="mb-8">
              <h2 class="text-2xl font-bold text-foundation-text tracking-tight">Masuk ke Portal</h2>
              <p class="text-sm font-inter text-foundation-muted mt-1">
                Silakan pilih peran dan masukkan kredensial akun Anda.
              </p>
            </div>

            <!-- Form Element -->
            <form @submit.prevent="submit" class="space-y-5">
              
              <!-- Input Identitas / NIP / Username -->
              <div>
                <label for="user-id" class="block text-xs font-semibold text-foundation-text uppercase tracking-wider mb-2">
                  Nomor Induk / Username / Email
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-foundation-muted">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                  </div>
                  <input v-model="form.user_id" type="text" id="user-id" placeholder="Contoh: 19850312-001 atau username" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-white text-sm font-inter rounded-lg border border-foundation-border focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400 text-foundation-text">
                </div>
                <div v-if="form.errors.user_id" class="text-error text-xs mt-1.5 ml-1">{{ form.errors.user_id }}</div>
              </div>

              <!-- Input Password -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label for="password" class="block text-xs font-semibold text-foundation-text uppercase tracking-wider">
                    Kata Sandi
                  </label>
                  <a href="#" class="text-xs font-inter font-medium text-primary hover:text-primary-dark transition-colors">
                    Lupa kata sandi?
                  </a>
                </div>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-foundation-muted">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                  </div>
                  <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password" placeholder="••••••••••••" class="w-full pl-10 pr-11 py-2.5 bg-slate-50 hover:bg-white text-sm font-inter rounded-lg border border-foundation-border focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400 text-foundation-text">
                  <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-foundation-muted hover:text-foundation-text">
                    <span class="material-symbols-outlined text-[20px]">{{ showPassword ? 'visibility' : 'visibility_off' }}</span>
                  </button>
                </div>
                <div v-if="form.errors.password" class="text-error text-xs mt-1.5 ml-1">{{ form.errors.password }}</div>
              </div>

              <!-- Remember Me & Fast Session Checkbox -->
              <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2.5 cursor-pointer">
                  <input v-model="form.remember" type="checkbox" id="remember" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary/30 focus:ring-offset-0 cursor-pointer">
                  <span class="text-xs font-inter text-foundation-muted select-none">Ingat perangkat ini selama 30 hari</span>
                </label>
              </div>

              <!-- CTA Button -->
              <div class="pt-2">
                <button type="submit" :disabled="form.processing" class="w-full flex items-center justify-center gap-2 py-3 px-5 rounded-lg bg-primary hover:bg-[#00552d] active:scale-[0.99] text-white font-semibold text-sm shadow-md shadow-primary/20 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                  <span class="" v-if="!form.processing">Masuk ke Portal</span>
                  <span class="" v-else>Memproses...</span>
                  <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
              </div>

            </form>

            <!-- Security note / Footer hint within card -->
            <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between text-xs font-inter text-slate-500">
              <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-emerald-600">shield</span>
                Akses Dilindungi RBAC
              </span>
              <a href="#" class="hover:text-primary transition-colors">Bantuan Masuk?</a>
            </div>

          </div>
        </section>

      </div>
    </main>

    <!-- Global Footer -->
    <footer class="w-full py-4 border-t border-foundation-border/60 bg-white/70 backdrop-blur-sm">
      <div class="max-w-5xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-inter text-foundation-muted">
        <p class="">© 2024 Yayasan Li Ulil Albab Karanganyar. Seluruh hak cipta dilindungi.</p>
        <div class="flex items-center gap-5">
          <a href="#" class="hover:text-primary transition-colors">Kebijakan Privasi</a>
          <span class="text-slate-300">•</span>
          <a href="#" class="hover:text-primary transition-colors">Syarat Penggunaan</a>
          <span class="text-slate-300">•</span>
          <a href="#" class="hover:text-primary transition-colors">Pusat Bantuan IT</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.font-inter {
  font-family: 'Inter', sans-serif;
}
</style>
