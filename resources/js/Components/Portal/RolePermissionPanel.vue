<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    permissionsData: {
        type: Object,
        default: () => ({}),
    },
    defaultFeatures: {
        type: Array,
        default: () => [],
    },
    primaryRoles: {
        type: Array,
        default: () => ['superadmin', 'Kepala Bidang SDM', 'Guru'],
    },
    availableRoles: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    organizationPositions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['saved']);

const activeRole = ref('superadmin');
const isSaving = ref(false);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('all');

// Local cache for matrix per role
const localMatrices = ref({ ...props.permissionsData });

// Current matrix being edited
const currentMatrix = ref([]);

function loadRoleMatrix(role) {
    if (localMatrices.value[role] && localMatrices.value[role].length > 0) {
        currentMatrix.value = JSON.parse(JSON.stringify(localMatrices.value[role]));
        return;
    }

    if (props.permissionsData && props.permissionsData[role] && props.permissionsData[role].length > 0) {
        localMatrices.value[role] = props.permissionsData[role];
        currentMatrix.value = JSON.parse(JSON.stringify(props.permissionsData[role]));
        return;
    }

    if (role === 'superadmin' && props.defaultFeatures && props.defaultFeatures.length > 0) {
        const fullAccess = props.defaultFeatures.map((feat) => ({
            group: feat.group,
            name: feat.name,
            category: feat.category,
            description: feat.description,
            supported_actions: feat.supported_actions,
            can_view: true,
            can_create: true,
            can_update: true,
            can_delete: true,
            can_export: true,
            can_import: true,
        }));
        localMatrices.value[role] = fullAccess;
        currentMatrix.value = JSON.parse(JSON.stringify(fullAccess));
        return;
    }

    isLoading.value = true;
    fetch(`/portal/permissions/matrix?role=${encodeURIComponent(role)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((res) => {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then((data) => {
            if (data && data.matrix && data.matrix.length > 0) {
                localMatrices.value[role] = data.matrix;
                if (activeRole.value === role) {
                    currentMatrix.value = JSON.parse(JSON.stringify(data.matrix));
                }
            }
        })
        .catch((err) => {
            console.error('Gagal memuat hak akses role:', err);
        })
        .finally(() => {
            isLoading.value = false;
        });
}

// Watch props in case server updates permissionsData
watch(
    () => props.permissionsData,
    (newVal) => {
        if (newVal) {
            localMatrices.value = { ...localMatrices.value, ...newVal };
            if (newVal[activeRole.value] && newVal[activeRole.value].length > 0) {
                currentMatrix.value = JSON.parse(JSON.stringify(newVal[activeRole.value]));
            } else {
                loadRoleMatrix(activeRole.value);
            }
        }
    },
    { deep: true, immediate: true }
);

// Initial load
loadRoleMatrix(activeRole.value);

function setRole(role) {
    if (activeRole.value && currentMatrix.value && currentMatrix.value.length > 0) {
        localMatrices.value[activeRole.value] = JSON.parse(JSON.stringify(currentMatrix.value));
    }
    activeRole.value = role;
    loadRoleMatrix(role);
}

const isSuperadminActive = computed(() => activeRole.value === 'superadmin');

const selectedBidangFilter = ref('all');

// Daftar bidang yang memiliki jabatan di diagram
const bidangOptions = computed(() => {
    return (props.departments || []).map((d) => ({
        id: d.id,
        name: d.name,
        positions: d.positions || [],
    }));
});

// Jabatan yang ditampilkan sesuai bidang yang dipilih
const displayedPositions = computed(() => {
    if (selectedBidangFilter.value === 'all') {
        const posMap = new Map();
        (props.departments || []).forEach((d) => {
            (d.positions || []).forEach((p) => {
                if (!posMap.has(p.name)) {
                    posMap.set(p.name, {
                        ...p,
                        deptName: d.name,
                    });
                }
            });
        });
        (props.organizationPositions || []).forEach((p) => {
            if (!posMap.has(p.name)) {
                posMap.set(p.name, {
                    ...p,
                    deptName: p.department_name || '',
                });
            }
        });
        return Array.from(posMap.values());
    }

    const dept = (props.departments || []).find((d) => String(d.id) === String(selectedBidangFilter.value));
    if (!dept) return [];

    return (dept.positions || []).map((p) => ({
        ...p,
        deptName: dept.name,
    }));
});

// Bidang dari role yang sedang aktif
const activeRoleDept = computed(() => {
    if (activeRole.value === 'superadmin') return 'Sistem Administrator';
    for (const dept of props.departments || []) {
        if (dept.positions && dept.positions.some((p) => p.name === activeRole.value)) {
            return dept.name;
        }
    }
    const foundOrg = (props.organizationPositions || []).find((p) => p.name === activeRole.value);
    if (foundOrg && foundOrg.department_name) return foundOrg.department_name;
    return '';
});

// Categories list
const categories = computed(() => {
    const set = new Set();
    currentMatrix.value.forEach((item) => {
        if (item.category) set.add(item.category);
    });
    return Array.from(set);
});

// Category Styling & Icons
const categoryMeta = {
    'Dashboard': { icon: 'dashboard', color: 'bg-emerald-50 text-emerald-800 border-emerald-200' },
    'Database Siswa': { icon: 'groups', color: 'bg-blue-50 text-blue-800 border-blue-200' },
    'Database Pegawai': { icon: 'badge', color: 'bg-indigo-50 text-indigo-800 border-indigo-200' },
    'Administrasi': { icon: 'description', color: 'bg-amber-50 text-amber-800 border-amber-200' },
    'Statistik': { icon: 'bar_chart', color: 'bg-purple-50 text-purple-800 border-purple-200' },
    'Pembelajaran': { icon: 'menu_book', color: 'bg-teal-50 text-teal-800 border-teal-200' },
    'Pengaturan Master': { icon: 'settings', color: 'bg-rose-50 text-rose-800 border-rose-200' },
};

function getCategoryMeta(cat) {
    return categoryMeta[cat] || { icon: 'folder', color: 'bg-surface-container-high text-on-surface border-outline-variant/40' };
}

// Filtered and grouped matrix
const filteredMatrix = computed(() => {
    return currentMatrix.value.filter((item) => {
        const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value;
        const q = searchQuery.value.trim().toLowerCase();
        const matchesSearch =
            !q ||
            item.name.toLowerCase().includes(q) ||
            item.group.toLowerCase().includes(q) ||
            item.description.toLowerCase().includes(q) ||
            item.category.toLowerCase().includes(q);

        return matchesCategory && matchesSearch;
    });
});

// Group filtered matrix by category
const groupedMatrix = computed(() => {
    const groups = {};
    filteredMatrix.value.forEach((item) => {
        const cat = item.category || 'Lainnya';
        if (!groups[cat]) {
            groups[cat] = [];
        }
        groups[cat].push(item);
    });
    return groups;
});

// Toggle entire row (all actions in a feature)
function toggleRow(item) {
    if (isSuperadminActive.value) return;

    const allChecked = item.supported_actions.every((act) => item[`can_${act}`]);
    const targetState = !allChecked;

    item.supported_actions.forEach((act) => {
        item[`can_${act}`] = targetState;
    });
}

// Toggle all in a category
function toggleCategory(catName, value) {
    if (isSuperadminActive.value) return;

    currentMatrix.value.forEach((item) => {
        if (item.category === catName) {
            item.supported_actions.forEach((act) => {
                item[`can_${act}`] = value;
            });
        }
    });
}

// Toggle all checkboxes in the entire matrix
function toggleAll(value) {
    if (isSuperadminActive.value) return;

    currentMatrix.value.forEach((item) => {
        item.supported_actions.forEach((act) => {
            item[`can_${act}`] = value;
        });
    });
}

// Save changes
function savePermissions() {
    isSaving.value = true;
    router.post(
        '/portal/permissions',
        {
            role: activeRole.value,
            permissions: currentMatrix.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                localMatrices.value[activeRole.value] = JSON.parse(JSON.stringify(currentMatrix.value));
                emit('saved', `Hak akses untuk role "${activeRole.value}" berhasil disimpan.`);
            },
            onFinish: () => {
                isSaving.value = false;
            },
        }
    );
}
</script>

<template>
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-xs overflow-hidden">
        <!-- Panel Header -->
        <div class="p-6 border-b border-outline-variant/30 bg-surface-container-low/20">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">shield_person</span>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-on-surface">Matriks Pengaturan Hak Akses (Role Permissions)</h3>
                        <p class="text-xs text-on-surface-variant">
                            Atur izin fitur modul Database Pegawai (Lihat, Tambah, Ubah, Hapus, Ekspor, Impor) untuk setiap role.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="savePermissions"
                        :disabled="isSaving || isLoading || isSuperadminActive"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isSaving" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Hak Akses' }}</span>
                    </button>
                </div>
            </div>

            <!-- Role Selector: Sesuai Bidang dan Jabatan di Diagram Susunan Organisasi -->
            <div class="mt-6 space-y-4">
                <!-- Baris 1: Filter Tab Bidang & Dropdown Cari Cepat -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-outline-variant/30">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mr-1">Filter Bidang:</span>

                        <button
                            type="button"
                            @click="selectedBidangFilter = 'all'"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer border"
                            :class="selectedBidangFilter === 'all'
                                ? 'bg-secondary-container text-on-secondary-container border-secondary/40 shadow-xs'
                                : 'bg-surface-container-low text-on-surface-variant hover:text-on-surface border-outline-variant/40'"
                        >
                            Semua Bidang
                        </button>

                        <button
                            v-for="dept in bidangOptions"
                            :key="dept.id"
                            type="button"
                            @click="selectedBidangFilter = dept.id"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer border flex items-center gap-1.5"
                            :class="selectedBidangFilter === dept.id
                                ? 'bg-secondary-container text-on-secondary-container border-secondary/40 shadow-xs'
                                : 'bg-surface-container-low text-on-surface-variant hover:text-on-surface border-outline-variant/40'"
                        >
                            <span>{{ dept.name }}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/5 dark:bg-white/10 font-bold">
                                {{ dept.positions.length }}
                            </span>
                        </button>
                    </div>

                    <!-- Dropdown Pencarian Cepat Per Bidang -->
                    <div class="flex items-center gap-2 shrink-0">
                        <label for="quick-role-select" class="text-xs text-on-surface-variant font-medium">Pilih Cepat Role:</label>
                        <select
                            id="quick-role-select"
                            :value="activeRole"
                            @change="(e) => e.target.value && setRole(e.target.value)"
                            class="px-3 py-1.5 rounded-xl border border-outline-variant/60 bg-surface-container-lowest text-xs text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-medium"
                        >
                            <option value="superadmin">Superadmin (Akses Penuh)</option>
                            <optgroup v-for="dept in bidangOptions" :key="'opt_' + dept.id" :label="dept.name">
                                <option v-for="pos in dept.positions" :key="'opt_pos_' + pos.id" :value="pos.name">
                                    {{ pos.name }} ({{ dept.name }})
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Baris 2: Tombol Pilihan Jabatan dari Diagram -->
                <div>
                    <div class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-primary">badge</span>
                        <span>Pilih Jabatan untuk Diatur Hak Aksesnya:</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Role Utama: Superadmin -->
                        <button
                            type="button"
                            @click="setRole('superadmin')"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer border"
                            :class="activeRole === 'superadmin' ? 'bg-primary text-on-primary border-primary shadow-xs' : 'bg-surface-container-low text-on-surface-variant hover:text-on-surface border-outline-variant/50'"
                        >
                            <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
                            <span>Superadmin</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full" :class="activeRole === 'superadmin' ? 'bg-white/20 text-white' : 'bg-outline-variant/40 text-on-surface-variant'">Akses Penuh</span>
                        </button>

                        <!-- Tombol Jabatan dari Diagram -->
                        <button
                            v-for="pos in displayedPositions"
                            :key="pos.id + '_' + pos.name"
                            type="button"
                            @click="setRole(pos.name)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer border"
                            :class="activeRole === pos.name ? 'bg-primary text-on-primary border-primary shadow-xs' : 'bg-surface-container-low text-on-surface hover:border-primary/40 border-outline-variant/50'"
                        >
                            <span>{{ pos.name }}</span>
                            <span v-if="pos.deptName" class="text-[10px] px-1.5 py-0.2 rounded-full font-normal opacity-80" :class="activeRole === pos.name ? 'bg-white/20 text-white' : 'bg-outline-variant/30 text-on-surface-variant'">
                                {{ pos.deptName }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="px-6 py-3 bg-surface-container-low/40 border-b border-outline-variant/30 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-primary">info</span>
                <span v-if="isSuperadminActive" class="text-on-surface font-medium">
                    Role <strong>Superadmin</strong> memiliki hak akses penuh ke seluruh fitur dan tidak dibatasi oleh matriks perizinan.
                </span>
                <span v-else class="text-on-surface">
                    Sedang mengatur hak akses jabatan: <strong class="text-primary text-sm font-bold ml-1">{{ activeRole }}</strong>
                    <span v-if="activeRoleDept" class="ml-2 text-xs text-on-surface-variant px-2 py-0.5 rounded-md bg-surface-container-high font-medium">
                        Bidang: {{ activeRoleDept }}
                    </span>
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Search Input -->
                <div class="relative w-full sm:w-56">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-on-surface-variant">search</span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari modul..."
                        class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-outline-variant/60 bg-surface-container-lowest text-xs text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                </div>

                <!-- Category Dropdown Filter -->
                <select
                    v-model="selectedCategory"
                    class="px-3 py-1.5 rounded-xl border border-outline-variant/60 bg-surface-container-lowest text-xs text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-medium"
                >
                    <option value="all">Semua Kategori ({{ currentMatrix.length }})</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </select>

                <!-- Quick Action Helpers -->
                <div v-if="!isSuperadminActive" class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="toggleAll(true)"
                        class="px-2.5 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-variant text-on-surface font-semibold transition-colors cursor-pointer text-[11px]"
                    >
                        Centang Semua
                    </button>
                    <button
                        type="button"
                        @click="toggleAll(false)"
                        class="px-2.5 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-variant text-on-surface font-semibold transition-colors cursor-pointer text-[11px]"
                    >
                        Kosongkan Semua
                    </button>
                </div>
            </div>
        </div>

        <!-- Matrix Table -->
        <div class="overflow-x-auto p-6">
            <table class="w-full text-xs text-left border-collapse border border-outline-variant/60">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-bold text-[10px] uppercase tracking-wider">
                        <th class="border border-outline-variant/60 p-3.5 w-72">Modul & Fitur Web</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-28">Kategori</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-24">Lihat (View)</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-24">Tambah (Create)</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-24">Ubah (Update)</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-24">Hapus (Delete)</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-24">Ekspor</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-24">Impor</th>
                        <th class="border border-outline-variant/60 p-3 text-center w-20" v-if="!isSuperadminActive">Pilih Baris</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(items, catName) in groupedMatrix" :key="catName">
                        <!-- Category Header Row -->
                        <tr class="bg-surface-container-low/80">
                            <td colspan="9" class="border border-outline-variant/60 px-4 py-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-primary">{{ getCategoryMeta(catName).icon }}</span>
                                        <span class="font-bold text-on-surface text-xs tracking-wide uppercase">{{ catName }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="getCategoryMeta(catName).color">
                                            {{ items.length }} modul
                                        </span>
                                    </div>

                                    <div v-if="!isSuperadminActive" class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="toggleCategory(catName, true)"
                                            class="text-[10px] text-primary hover:underline font-semibold cursor-pointer"
                                        >
                                            + Centang Kategori Ini
                                        </button>
                                        <span class="text-outline-variant">|</span>
                                        <button
                                            type="button"
                                            @click="toggleCategory(catName, false)"
                                            class="text-[10px] text-on-surface-variant hover:underline font-semibold cursor-pointer"
                                        >
                                            - Kosongkan Kategori Ini
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Feature Rows under this Category -->
                        <tr
                            v-for="item in items"
                            :key="item.group"
                            class="hover:bg-surface-container-low/30 transition-colors"
                        >
                            <!-- Nama Fitur & Deskripsi -->
                            <td class="border border-outline-variant/60 p-3 pl-5">
                                <div class="font-bold text-on-surface text-xs sm:text-sm flex items-center gap-2">
                                    <span>{{ item.name }}</span>
                                    <code class="text-[10px] text-on-surface-variant font-mono bg-surface-container-high px-1 rounded">{{ item.group }}</code>
                                </div>
                                <div class="text-[11px] text-on-surface-variant leading-relaxed mt-0.5">{{ item.description }}</div>
                            </td>

                            <!-- Kategori Badge -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold border" :class="getCategoryMeta(item.category).color">
                                    {{ item.category }}
                                </span>
                            </td>

                            <!-- Aksi: View -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <input
                                    v-if="item.supported_actions.includes('view')"
                                    type="checkbox"
                                    v-model="item.can_view"
                                    :disabled="isSuperadminActive"
                                    class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                />
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi: Create -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <input
                                    v-if="item.supported_actions.includes('create')"
                                    type="checkbox"
                                    v-model="item.can_create"
                                    :disabled="isSuperadminActive"
                                    class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                />
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi: Update -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <input
                                    v-if="item.supported_actions.includes('update')"
                                    type="checkbox"
                                    v-model="item.can_update"
                                    :disabled="isSuperadminActive"
                                    class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                />
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi: Delete -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <input
                                    v-if="item.supported_actions.includes('delete')"
                                    type="checkbox"
                                    v-model="item.can_delete"
                                    :disabled="isSuperadminActive"
                                    class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                />
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi: Export -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <input
                                    v-if="item.supported_actions.includes('export')"
                                    type="checkbox"
                                    v-model="item.can_export"
                                    :disabled="isSuperadminActive"
                                    class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                />
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi: Import -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <input
                                    v-if="item.supported_actions.includes('import')"
                                    type="checkbox"
                                    v-model="item.can_import"
                                    :disabled="isSuperadminActive"
                                    class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                />
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi Cepat Baris -->
                            <td class="border border-outline-variant/60 p-3 text-center" v-if="!isSuperadminActive">
                                <button
                                    type="button"
                                    @click="toggleRow(item)"
                                    class="p-1 rounded-lg hover:bg-surface-container-high text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                                    title="Beralih centang semua aksi pada fitur ini"
                                >
                                    <span class="material-symbols-outlined text-[16px]">select_all</span>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="Object.keys(groupedMatrix).length === 0">
                        <td colspan="9" class="border border-outline-variant/60 p-8 text-center text-on-surface-variant italic">
                            <span class="material-symbols-outlined text-3xl text-outline/40 block mb-1">search_off</span>
                            Tidak ada modul fitur yang sesuai dengan pencarian atau filter kategori.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Info -->
        <div class="p-4 bg-surface-container-low/30 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-on-surface-variant">
            <div>
                Menampilkan <strong>{{ filteredMatrix.length }}</strong> dari <strong>{{ currentMatrix.length }}</strong> modul fitur web terdaftar.
            </div>
            <button
                type="button"
                @click="savePermissions"
                :disabled="isSaving || isLoading || isSuperadminActive"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-semibold text-xs transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="isSaving" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                <span v-else class="material-symbols-outlined text-[16px]">save</span>
                <span>Simpan Perubahan Role Ini</span>
            </button>
        </div>
    </div>
</template>
