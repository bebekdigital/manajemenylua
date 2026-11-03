<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    approvals: {
        type: Array,
        default: () => [],
    },
});

// Search & Filter State
const searchQuery = ref('');
const selectedUnit = ref('all');
const selectedJenis = ref('all');

// Quick Action Modal State
const showActionModal = ref(false);
const activeItem = ref(null);
const actionType = ref('disetujui'); // 'disetujui' | 'ditolak'
const actionCatatan = ref('');
const isSubmitting = ref(false);

// Extract available units
const availableUnits = computed(() => {
    const units = new Set();
    props.approvals.forEach((item) => {
        if (item.employee_unit && item.employee_unit !== '-') {
            units.add(item.employee_unit);
        }
    });
    return Array.from(units);
});

// Extract available jenis
const availableJenis = computed(() => {
    const types = new Set();
    props.approvals.forEach((item) => {
        if (item.jenis) {
            types.add(item.jenis);
        }
    });
    return Array.from(types);
});

// Filtered approvals
const filteredApprovals = computed(() => {
    return props.approvals.filter((item) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            item.employee_nama?.toLowerCase().includes(query) ||
            item.employee_nipy?.toLowerCase().includes(query) ||
            item.employee_jabatan?.toLowerCase().includes(query) ||
            item.jenis?.toLowerCase().includes(query) ||
            item.detail?.toLowerCase().includes(query) ||
            item.keterangan?.toLowerCase().includes(query);

        const matchesUnit =
            selectedUnit.value === 'all' || item.employee_unit === selectedUnit.value;

        const matchesJenis =
            selectedJenis.value === 'all' || item.jenis === selectedJenis.value;

        return matchesSearch && matchesUnit && matchesJenis;
    });
});

function getJenisBadgeClass(jenis) {
    const j = (jenis || '').toLowerCase();
    if (j.includes('sakit')) return 'bg-amber-50 text-amber-800 border-amber-200';
    if (j.includes('cuti')) return 'bg-blue-50 text-blue-800 border-blue-200';
    if (j.includes('dinas') || j.includes('tugas')) return 'bg-purple-50 text-purple-800 border-purple-200';
    return 'bg-emerald-50 text-emerald-800 border-emerald-200';
}

function openActionModal(item, type) {
    activeItem.value = item;
    actionType.value = type;
    actionCatatan.value = '';
    showActionModal.value = true;
}

function closeActionModal() {
    showActionModal.value = false;
    activeItem.value = null;
    actionCatatan.value = '';
    isSubmitting.value = false;
}

function submitAction() {
    if (!activeItem.value) return;

    isSubmitting.value = true;
    router.patch(
        route('staff.permits.note', [activeItem.value.employee_id, activeItem.value.id]),
        {
            status: actionType.value,
            catatan: actionCatatan.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeActionModal();
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        }
    );
}
</script>

<template>
    <Head title="Persetujuan Perizinan - Yayasan Li Ulil Albab" />

    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-700/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs border border-white/20 text-xs font-semibold text-emerald-200">
                        <span class="material-symbols-outlined text-[16px]">task_alt</span>
                        <span>Hierarki Persetujuan Atasan</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Persetujuan Perizinan</h1>
                    <p class="text-emerald-100 text-sm sm:text-base max-w-2xl">
                        Daftar permohonan izin, sakit, dan cuti pegawai di bawah naungan unit kerja Anda yang membutuhkan verifikasi dan persetujuan.
                    </p>
                </div>

                <!-- Stats Counter Badge -->
                <div class="flex items-center gap-3 shrink-0 bg-white/10 backdrop-blur-md px-5 py-4 rounded-2xl border border-white/20 self-start md:self-auto">
                    <div class="w-12 h-12 rounded-xl bg-amber-400 text-emerald-950 flex items-center justify-center font-black text-xl shadow-sm shrink-0">
                        {{ approvals.length }}
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-emerald-200 font-medium">Permohonan</span>
                        <span class="text-sm font-bold text-white leading-tight">Menunggu Tindakan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs space-y-3">
            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">
                        search
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama pegawai, NIPY, jabatan, atau keterangan..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition-all"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-semibold"
                    >
                        ✕
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Filter Unit -->
                    <select
                        v-model="selectedUnit"
                        class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 cursor-pointer"
                    >
                        <option value="all">Semua Unit ({{ approvals.length }})</option>
                        <option v-for="unit in availableUnits" :key="unit" :value="unit">
                            {{ unit }}
                        </option>
                    </select>

                    <!-- Filter Jenis -->
                    <select
                        v-model="selectedJenis"
                        class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 cursor-pointer"
                    >
                        <option value="all">Semua Jenis</option>
                        <option v-for="j in availableJenis" :key="j" :value="j">
                            {{ j }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Approvals List / Cards -->
        <div v-if="filteredApprovals.length > 0" class="space-y-3.5">
            <div
                v-for="item in filteredApprovals"
                :key="item.id"
                class="bg-white rounded-2xl border border-slate-200 hover:border-emerald-500/80 transition-all p-5 shadow-2xs group flex flex-col md:flex-row md:items-center justify-between gap-4"
            >
                <!-- Left: Pegawai Profile & Permit Details -->
                <div class="flex items-start gap-4 min-w-0 flex-1">
                    <!-- Avatar Initials -->
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-900 border border-emerald-200 flex items-center justify-center font-bold text-base shrink-0 shadow-2xs group-hover:scale-105 transition-transform">
                        {{ item.employee_nama ? item.employee_nama.substring(0, 2).toUpperCase() : 'PG' }}
                    </div>

                    <!-- Details -->
                    <div class="space-y-1.5 min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-950 transition-colors">
                                {{ item.employee_nama }}
                            </h3>
                            <span class="text-xs px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold">
                                NIPY: {{ item.employee_nipy }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                                {{ item.employee_unit }}
                            </span>
                            <span v-if="item.employee_jabatan" class="text-xs text-slate-400 font-medium">
                                • {{ item.employee_jabatan }}
                            </span>
                        </div>

                        <!-- Permit Info -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span :class="['px-2.5 py-0.5 rounded-lg text-xs font-bold border', getJenisBadgeClass(item.jenis)]">
                                {{ item.jenis }}
                            </span>
                            <span v-if="item.detail" class="text-xs text-slate-600 font-medium">
                                {{ item.detail }}
                            </span>
                        </div>

                        <!-- Date & Notes -->
                        <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                            <span class="inline-flex items-center gap-1 font-medium text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                <span class="material-symbols-outlined text-[15px] text-emerald-700">calendar_month</span>
                                {{ item.tanggal }}
                            </span>
                            <span v-if="item.created_at_human" class="text-slate-400">
                                Diajukan {{ item.created_at_human }} ({{ item.created_at_formatted }})
                            </span>
                        </div>

                        <!-- Keterangan -->
                        <p v-if="item.keterangan" class="text-xs text-slate-600 bg-slate-50/80 rounded-xl p-2.5 border border-slate-100 max-w-3xl">
                            <span class="font-semibold text-slate-700">Alasan/Keterangan:</span> {{ item.keterangan }}
                        </p>

                        <!-- Lampiran -->
                        <div v-if="item.lampiran_nama" class="flex items-center gap-2 pt-1">
                            <a
                                :href="item.preview_url"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-900 hover:underline bg-emerald-50/60 px-2.5 py-1 rounded-lg border border-emerald-200/80 transition-colors"
                            >
                                <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                <span class="truncate max-w-xs">{{ item.lampiran_nama }}</span>
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex items-center gap-2 md:flex-col lg:flex-row shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                    <!-- Quick Approve -->
                    <button
                        type="button"
                        @click="openActionModal(item, 'disetujui')"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-800 hover:bg-emerald-900 text-white transition-all shadow-2xs cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[17px]">check_circle</span>
                        <span>Setujui</span>
                    </button>

                    <!-- Quick Reject -->
                    <button
                        type="button"
                        @click="openActionModal(item, 'ditolak')"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[17px]">cancel</span>
                        <span>Tolak</span>
                    </button>

                    <!-- View in Staff Tab -->
                    <Link
                        :href="item.url"
                        class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-emerald-900 hover:bg-slate-100 border border-slate-200 transition-all cursor-pointer"
                        title="Buka detail profil pegawai tab perizinan"
                    >
                        <span class="material-symbols-outlined text-[18px]">launch</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-2xs space-y-3">
            <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto border border-emerald-100">
                <span class="material-symbols-outlined text-3xl">fact_check</span>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Persetujuan Tertunda</h3>
            <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                Semua pengajuan perizinan pegawai di bawah wewenang Anda telah diproses atau belum ada pengajuan baru.
            </p>
            <div class="pt-2">
                <Link
                    href="/beranda"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                >
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Kembali ke Beranda</span>
                </Link>
            </div>
        </div>

        <!-- Quick Action Modal (Setujui / Tolak) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showActionModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                @click.self="closeActionModal"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div
                                :class="[
                                    'w-9 h-9 rounded-xl flex items-center justify-center shrink-0',
                                    actionType === 'disetujui' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ actionType === 'disetujui' ? 'check_circle' : 'cancel' }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">
                                    {{ actionType === 'disetujui' ? 'Konfirmasi Persetujuan' : 'Tolak Pengajuan' }}
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Pegawai: <strong class="text-slate-700">{{ activeItem?.employee_nama }}</strong>
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeActionModal"
                            class="text-slate-400 hover:text-slate-600 rounded-full p-1 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1">
                            <p><span class="font-semibold text-slate-700">Jenis Perizinan:</span> {{ activeItem?.jenis }} {{ activeItem?.detail ? `(${activeItem.detail})` : '' }}</p>
                            <p><span class="font-semibold text-slate-700">Tanggal:</span> {{ activeItem?.tanggal }}</p>
                            <p v-if="activeItem?.keterangan"><span class="font-semibold text-slate-700">Alasan:</span> {{ activeItem.keterangan }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Catatan Atasan {{ actionType === 'ditolak' ? '(Wajib/Disarankan)' : '(Opsional)' }}
                            </label>
                            <textarea
                                v-model="actionCatatan"
                                rows="3"
                                :placeholder="actionType === 'disetujui' ? 'Berikan catatan atau instruksi tambahan jika ada...' : 'Alasan penolakan perizinan ini...'"
                                class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="closeActionModal"
                            :disabled="isSubmitting"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitAction"
                            :disabled="isSubmitting"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-2xs cursor-pointer flex items-center gap-1.5',
                                actionType === 'disetujui'
                                    ? 'bg-emerald-800 hover:bg-emerald-900'
                                    : 'bg-rose-700 hover:bg-rose-800'
                            ]"
                        >
                            <span v-if="isSubmitting" class="material-symbols-outlined animate-spin text-[16px]">sync</span>
                            <span>{{ actionType === 'disetujui' ? 'Ya, Setujui Sekarang' : 'Tolak Pengajuan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

