<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const dismissedFlash = ref(false);

const isEditing = ref(false);

const form = useForm({
    name: props.profile.name || '',
    jk: props.profile.jk || '',
    ttl: props.profile.ttl || '',
    no_wa: props.profile.no_wa || '',
});

const photoForm = useForm({
    foto: null,
});

const photoPreview = ref(null);
const photoInput = ref(null);

function startEditing() {
    form.name = props.profile.name || '';
    form.jk = props.profile.jk || '';
    form.ttl = props.profile.ttl || '';
    form.no_wa = props.profile.no_wa || '';
    isEditing.value = true;
    dismissedFlash.value = true;
}

function cancelEditing() {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    dismissedFlash.value = false;
    form.put('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
}

function selectPhoto() {
    photoInput.value?.click();
}

function onPhotoSelected(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Preview
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);

    // Upload
    photoForm.foto = file;
    dismissedFlash.value = false;
    photoForm.post('/profile/photo', {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            photoForm.reset();
        },
        onError: () => {
            photoPreview.value = null;
        },
    });
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'PTY': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'PTTY': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Honorer': return 'bg-amber-100 text-amber-700 border-amber-200';
        default: return 'bg-slate-100 text-slate-500 border-slate-200';
    }
}

function getRoleBadgeClass(role) {
    switch (role) {
        case 'superadmin': return 'bg-red-100 text-red-700 border-red-200';
        case 'admin': return 'bg-purple-100 text-purple-700 border-purple-200';
        default: return 'bg-slate-100 text-slate-600 border-slate-200';
    }
}
</script>

<template>
    <Head title="Profil Saya" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
        <!-- Back Button -->
        <div class="mb-4">
            <Link href="/beranda" class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Beranda
            </Link>
        </div>

        <!-- Flash Messages -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="flashSuccess && !dismissedFlash" class="mb-4 flex items-center gap-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                <span class="text-sm font-medium flex-1">{{ flashSuccess }}</span>
                <button @click="dismissedFlash = true" class="p-1 rounded-full hover:bg-emerald-100 transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>
        </Transition>

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Profile Header Card -->
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
                <!-- Cover gradient -->
                <div class="h-28 sm:h-36 bg-gradient-to-br from-primary via-primary/80 to-emerald-400 relative">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMiIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA4KSIvPjwvc3ZnPg==')] opacity-60"></div>
                </div>

                <!-- Profile info overlapping cover -->
                <div class="px-4 sm:px-6 pb-6 -mt-14 sm:-mt-16 relative">
                    <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4">
                        <!-- Avatar -->
                        <div class="relative group shrink-0">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full border-4 border-white shadow-lg overflow-hidden bg-primary/10 flex items-center justify-center">
                                <img
                                    v-if="photoPreview || profile.foto"
                                    :src="photoPreview || profile.foto"
                                    class="w-full h-full object-cover"
                                    alt="Profile photo"
                                />
                                <span v-else class="text-primary font-bold text-3xl sm:text-4xl">{{ getInitials(profile.name) }}</span>
                            </div>
                            <!-- Photo upload overlay -->
                            <button
                                @click="selectPhoto"
                                class="absolute inset-0 rounded-full bg-black/0 group-hover:bg-black/40 flex items-center justify-center transition-all cursor-pointer"
                                title="Ubah foto profil"
                            >
                                <span class="material-symbols-outlined text-white text-[24px] opacity-0 group-hover:opacity-100 transition-opacity">photo_camera</span>
                            </button>
                            <input ref="photoInput" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" @change="onPhotoSelected" />
                            <!-- Upload progress -->
                            <div v-if="photoForm.processing" class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-[24px] animate-spin">sync</span>
                            </div>
                        </div>

                        <!-- Name & badges -->
                        <div class="flex-1 text-center sm:text-left sm:pb-1">
                            <h1 class="text-xl sm:text-2xl font-bold text-on-surface">{{ profile.name }}</h1>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-1.5">
                                <span
                                    v-if="profile.role"
                                    :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border capitalize', getRoleBadgeClass(profile.role)]"
                                >
                                    <span class="material-symbols-outlined text-[12px]">shield</span>
                                    {{ profile.role }}
                                </span>
                                <span
                                    v-if="profile.status_kepegawaian"
                                    :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border', getStatusBadgeClass(profile.status_kepegawaian)]"
                                >
                                    <span class="material-symbols-outlined text-[12px]">badge</span>
                                    {{ profile.status_kepegawaian }}
                                </span>
                                <span v-if="profile.unit" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-primary/10 text-primary border border-primary/20">
                                    <span class="material-symbols-outlined text-[12px]">school</span>
                                    {{ profile.unit }}
                                </span>
                            </div>
                        </div>

                        <!-- Edit Button -->
                        <div class="shrink-0">
                            <button
                                v-if="!isEditing"
                                @click="startEditing"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:bg-primary/90 shadow-xs transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                Edit Profil
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Card: Informasi Pribadi -->
                <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-outline-variant/30 bg-surface-container-low flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">person</span>
                        <h3 class="text-sm font-bold text-on-surface">Informasi Pribadi</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Nama (editable) -->
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Nama Lengkap</label>
                            <template v-if="isEditing">
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full px-3 py-2 text-sm bg-surface-container border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                                    placeholder="Masukkan nama lengkap"
                                />
                                <p v-if="form.errors.name" class="text-xs text-error mt-1">{{ form.errors.name }}</p>
                            </template>
                            <p v-else class="text-sm font-medium text-on-surface mt-1">{{ profile.name || '-' }}</p>
                        </div>

                        <!-- JK (editable) -->
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Jenis Kelamin</label>
                            <template v-if="isEditing">
                                <select
                                    v-model="form.jk"
                                    class="mt-1 block w-full px-3 py-2 text-sm bg-surface-container border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all cursor-pointer"
                                >
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </template>
                            <p v-else class="text-sm font-medium text-on-surface mt-1">
                                {{ profile.jk === 'L' ? 'Laki-laki' : profile.jk === 'P' ? 'Perempuan' : '-' }}
                            </p>
                        </div>

                        <!-- TTL (editable) -->
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Tempat, Tanggal Lahir</label>
                            <template v-if="isEditing">
                                <input
                                    v-model="form.ttl"
                                    type="text"
                                    class="mt-1 block w-full px-3 py-2 text-sm bg-surface-container border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                                    placeholder="Contoh: Bandung, 1 Januari 1990"
                                />
                            </template>
                            <p v-else class="text-sm font-medium text-on-surface mt-1">{{ profile.ttl || '-' }}</p>
                        </div>

                        <!-- No WA (editable) -->
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">No. WhatsApp</label>
                            <template v-if="isEditing">
                                <input
                                    v-model="form.no_wa"
                                    type="text"
                                    class="mt-1 block w-full px-3 py-2 text-sm bg-surface-container border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                                    placeholder="08xxxxxxxxxx"
                                />
                            </template>
                            <p v-else class="text-sm font-medium text-on-surface mt-1">{{ profile.no_wa || '-' }}</p>
                        </div>

                        <!-- Edit action buttons -->
                        <div v-if="isEditing" class="flex items-center gap-3 pt-2 border-t border-outline-variant/30">
                            <button
                                @click="submitForm"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:bg-primary/90 shadow-xs transition-all cursor-pointer disabled:opacity-50"
                            >
                                <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                Simpan
                            </button>
                            <button
                                @click="cancelEditing"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container text-on-surface-variant rounded-xl text-sm font-semibold hover:bg-surface-container-high transition-all cursor-pointer"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card: Informasi Kepegawaian -->
                <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-outline-variant/30 bg-surface-container-low flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">work</span>
                        <h3 class="text-sm font-bold text-on-surface">Informasi Kepegawaian</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Username</label>
                            <p class="text-sm font-medium text-on-surface mt-1 font-mono">{{ profile.username || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">NIPY</label>
                            <p class="text-sm font-medium text-on-surface mt-1 font-mono">{{ profile.nipy || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Status Kepegawaian</label>
                            <p class="mt-1">
                                <span
                                    v-if="profile.status_kepegawaian"
                                    :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border', getStatusBadgeClass(profile.status_kepegawaian)]"
                                >
                                    {{ profile.status_kepegawaian }}
                                </span>
                                <span v-else class="text-sm font-medium text-on-surface">-</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Jabatan</label>
                            <p class="text-sm font-medium text-on-surface mt-1">{{ profile.jabatan || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Unit</label>
                            <p class="text-sm font-medium text-on-surface mt-1">{{ profile.unit || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider font-medium">Email</label>
                            <p class="text-sm font-medium text-on-surface mt-1">{{ profile.email || '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Note -->
            <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-700">
                <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">info</span>
                <div>
                    <p class="text-sm font-medium">Data NIPY, Status Kepegawaian, Unit, dan Jabatan dikelola oleh admin melalui Data Pegawai.</p>
                    <p class="text-xs mt-1 text-amber-600">Hubungi admin jika ada perubahan data kepegawaian.</p>
                </div>
            </div>

        </div>
    </div>
</template>
