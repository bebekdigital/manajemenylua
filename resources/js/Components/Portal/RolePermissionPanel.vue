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

const selectedBidang = ref('superadmin');
const selectedJabatan = ref('superadmin');
const isSaving = ref(false);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('all');

// Local cache for matrix per role
const localMatrices = ref({ ...props.permissionsData });

// Current matrix being edited
const currentMatrix = ref([]);

const isSuperadminActive = computed(() => {
    return selectedBidang.value === 'superadmin' || selectedJabatan.value === 'superadmin';
});

const isAllInDeptActive = computed(() => {
    return !isSuperadminActive.value && selectedJabatan.value === 'ALL_IN_DEPT';
});

const currentDept = computed(() => {
    if (selectedBidang.value === 'superadmin') return null;
    return (props.departments || []).find((d) => String(d.id) === String(selectedBidang.value)) || null;
});

const currentDeptPositions = computed(() => {
    return currentDept.value?.positions || [];
});

const selectedBidangName = computed(() => {
    if (selectedBidang.value === 'superadmin') return 'Sistem Administrator';
    return currentDept.value ? currentDept.value.name : '';
});

function saveCurrentToCache() {
    if (!currentMatrix.value || currentMatrix.value.length === 0) return;

    if (selectedJabatan.value && selectedJabatan.value !== 'ALL_IN_DEPT') {
        localMatrices.value[selectedJabatan.value] = JSON.parse(JSON.stringify(currentMatrix.value));
    }
}

function loadRoleMatrix(role) {
    if (!role) return;

    if (role === 'superadmin' && props.defaultFeatures && props.defaultFeatures.length > 0) {
        const fullAccess = props.defaultFeatures.map((feat) => ({
            group: feat.group,
            name: feat.name,
            category: feat.category,
            description: feat.description,
            parent_group: feat.parent_group || null,
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

    if (localMatrices.value[role] && localMatrices.value[role].length > 0) {
        currentMatrix.value = JSON.parse(JSON.stringify(localMatrices.value[role]));
        return;
    }

    if (props.permissionsData && props.permissionsData[role] && props.permissionsData[role].length > 0) {
        localMatrices.value[role] = props.permissionsData[role];
        currentMatrix.value = JSON.parse(JSON.stringify(props.permissionsData[role]));
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
                if (
                    selectedJabatan.value === role ||
                    (selectedJabatan.value === 'ALL_IN_DEPT' && currentDeptPositions.value.some((p) => p.name === role))
                ) {
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

function onBidangChange(e) {
    const val = e.target.value;
    saveCurrentToCache();
    selectedBidang.value = val;

    if (val === 'superadmin') {
        selectedJabatan.value = 'superadmin';
        loadRoleMatrix('superadmin');
        return;
    }

    const dept = (props.departments || []).find((d) => String(d.id) === String(val));
    const positions = dept?.positions || [];

    if (positions.length > 0) {
        selectedJabatan.value = 'ALL_IN_DEPT';
        loadRoleMatrix(positions[0].name);
    } else {
        selectedJabatan.value = '';
        currentMatrix.value = (props.defaultFeatures || []).map((feat) => ({
            group: feat.group,
            name: feat.name,
            category: feat.category,
            description: feat.description,
            parent_group: feat.parent_group || null,
            supported_actions: feat.supported_actions,
            can_view: false,
            can_create: false,
            can_update: false,
            can_delete: false,
            can_export: false,
            can_import: false,
        }));
    }
}

function onJabatanChange(e) {
    const val = e.target.value;
    if (!val) return;

    saveCurrentToCache();
    selectedJabatan.value = val;

    if (val === 'ALL_IN_DEPT') {
        const positions = currentDeptPositions.value;
        if (positions.length > 0) {
            loadRoleMatrix(positions[0].name);
        }
    } else {
        loadRoleMatrix(val);
    }
}

// Watch props in case server updates permissionsData
watch(
    () => props.permissionsData,
    (newVal) => {
        if (newVal) {
            localMatrices.value = { ...localMatrices.value, ...newVal };
            const currentTarget = selectedJabatan.value === 'ALL_IN_DEPT'
                ? currentDeptPositions.value[0]?.name
                : selectedJabatan.value;

            if (currentTarget && newVal[currentTarget] && newVal[currentTarget].length > 0) {
                currentMatrix.value = JSON.parse(JSON.stringify(newVal[currentTarget]));
            } else if (currentTarget) {
                loadRoleMatrix(currentTarget);
            }
        }
    },
    { deep: true, immediate: true }
);

// Initial load
loadRoleMatrix(selectedJabatan.value);

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

// Hierarchy & Parent-Child Helpers
function getChildren(item) {
    if (!item || !item.group) return [];
    return currentMatrix.value.filter((m) => m.parent_group === item.group);
}

function hasChildren(item) {
    return getChildren(item).length > 0;
}

function isChild(item) {
    return Boolean(item && item.parent_group);
}

function getParent(item) {
    if (!item || !item.parent_group) return null;
    return currentMatrix.value.find((m) => m.group === item.parent_group) || null;
}

/**
 * Menghitung status checklist sebuah fitur dan aksi:
 * - 'checked': Tercentang penuh (parent & seluruh child aktif)
 * - 'indeterminate': Tercentang sebagian (strip [-])
 * - 'unchecked': Kosong / tidak tercentang sama sekali
 */
function getActionState(item, act) {
    if (!hasChildren(item)) {
        return item[`can_${act}`] ? 'checked' : 'unchecked';
    }

    const children = getChildren(item);
    const checkedChildren = children.filter((c) => Boolean(c[`can_${act}`])).length;
    const isParentChecked = Boolean(item[`can_${act}`]);

    if (checkedChildren === children.length && isParentChecked) {
        return 'checked';
    }
    if (checkedChildren === 0 && !isParentChecked) {
        return 'unchecked';
    }
    return 'indeterminate';
}

function getActionTitle(item, act) {
    const state = getActionState(item, act);
    if (state === 'checked') return 'Semua tercentang penuh. Klik untuk mengosongkan.';
    if (state === 'indeterminate') return 'Checklist sebagian (strip). Klik untuk mencentang semua.';
    return 'Kosong. Klik untuk mencentang semua.';
}

function toggleParentAction(item, act) {
    if (isSuperadminActive.value) return;

    const currentState = getActionState(item, act);
    // Jika penuh -> uncheck; jika strip atau kosong -> check penuh
    const targetState = currentState !== 'checked';

    item[`can_${act}`] = targetState;
    const children = getChildren(item);
    children.forEach((c) => {
        c[`can_${act}`] = targetState;
    });
}

function onChildActionChange(item, act) {
    if (isSuperadminActive.value) return;

    // Jika child dicentang, pastikan parent juga tercentang
    if (item[`can_${act}`]) {
        const parent = getParent(item);
        if (parent) {
            parent[`can_${act}`] = true;
        }
    }
}

// Filtered and grouped matrix
const filteredMatrix = computed(() => {
    return currentMatrix.value.filter((item) => {
        const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value;
        const q = searchQuery.value.trim().toLowerCase();

        if (!matchesCategory) return false;
        if (!q) return true;

        const selfMatch =
            item.name.toLowerCase().includes(q) ||
            item.group.toLowerCase().includes(q) ||
            (item.description && item.description.toLowerCase().includes(q)) ||
            (item.category && item.category.toLowerCase().includes(q));

        if (selfMatch) return true;

        if (hasChildren(item)) {
            const children = getChildren(item);
            return children.some((c) =>
                c.name.toLowerCase().includes(q) ||
                c.group.toLowerCase().includes(q) ||
                (c.description && c.description.toLowerCase().includes(q))
            );
        }

        if (isChild(item)) {
            const parent = getParent(item);
            return parent && (
                parent.name.toLowerCase().includes(q) ||
                parent.group.toLowerCase().includes(q) ||
                (parent.description && parent.description.toLowerCase().includes(q))
            );
        }

        return false;
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

    if (hasChildren(item)) {
        const children = getChildren(item);
        const allParentChecked = item.supported_actions.every((act) => Boolean(item[`can_${act}`]));
        const allChildrenChecked = children.every((child) =>
            child.supported_actions.every((act) => Boolean(child[`can_${act}`]))
        );
        const allChecked = allParentChecked && allChildrenChecked;
        const targetState = !allChecked;

        item.supported_actions.forEach((act) => {
            item[`can_${act}`] = targetState;
        });
        children.forEach((child) => {
            child.supported_actions.forEach((act) => {
                child[`can_${act}`] = targetState;
            });
        });
    } else {
        const allChecked = item.supported_actions.every((act) => Boolean(item[`can_${act}`]));
        const targetState = !allChecked;

        item.supported_actions.forEach((act) => {
            item[`can_${act}`] = targetState;
        });

        if (isChild(item) && targetState) {
            const parent = getParent(item);
            if (parent) {
                item.supported_actions.forEach((act) => {
                    if (item[`can_${act}`]) {
                        parent[`can_${act}`] = true;
                    }
                });
            }
        }
    }
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
    if (isSuperadminActive.value) return;

    isSaving.value = true;

    if (isAllInDeptActive.value) {
        const roles = currentDeptPositions.value.map((p) => p.name);
        if (roles.length === 0) {
            isSaving.value = false;
            return;
        }

        router.post(
            '/portal/permissions',
            {
                roles: roles,
                permissions: currentMatrix.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    roles.forEach((r) => {
                        localMatrices.value[r] = JSON.parse(JSON.stringify(currentMatrix.value));
                    });
                    emit('saved', `Hak akses untuk seluruh (${roles.length}) jabatan di bidang "${selectedBidangName.value}" berhasil disimpan.`);
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    } else {
        const targetRole = selectedJabatan.value;
        if (!targetRole) {
            isSaving.value = false;
            return;
        }

        router.post(
            '/portal/permissions',
            {
                role: targetRole,
                permissions: currentMatrix.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    localMatrices.value[targetRole] = JSON.parse(JSON.stringify(currentMatrix.value));
                    emit('saved', `Hak akses untuk role "${targetRole}" berhasil disimpan.`);
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    }
}
</script>

<template>
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-xs overflow-hidden">
        <!-- Panel Header -->
        <div class="p-6 border-b border-outline-variant/30 bg-surface-container-low/20">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-primary text-xl">shield_person</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-on-surface">Matriks Pengaturan Hak Akses (Role Permissions)</h3>
                        <p class="text-xs text-on-surface-variant">
                            Atur izin fitur modul Database Pegawai (Lihat, Tambah, Ubah, Hapus, Ekspor, Impor) untuk setiap bidang dan jabatan.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="savePermissions"
                        :disabled="isSaving || isLoading || isSuperadminActive || (isAllInDeptActive && currentDeptPositions.length === 0)"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isSaving" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        <span>{{ isSaving ? 'Menyimpan...' : (isAllInDeptActive ? `Simpan untuk Semua Jabatan (${currentDeptPositions.length})` : 'Simpan Hak Akses') }}</span>
                    </button>
                </div>
            </div>

            <!-- Role Selector Controls: Dua Dropdown (Bidang & Jabatan) -->
            <div class="mt-6 p-4 sm:p-5 rounded-2xl bg-surface-container-low border border-outline-variant/40">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dropdown 1: Pilih Bidang -->
                    <div>
                        <label for="select-bidang" class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 block">
                            1. Pilih Bidang / Unit:
                        </label>
                        <select
                            id="select-bidang"
                            :value="selectedBidang"
                            @change="onBidangChange"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-lowest text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-medium cursor-pointer shadow-xs"
                        >
                            <option value="superadmin">Sistem (Superadmin)</option>
                            <option disabled>────────── DAFTAR BIDANG / UNIT ──────────</option>
                            <option v-for="dept in departments" :key="'dept_' + dept.id" :value="dept.id">
                                {{ dept.name }} ({{ (dept.positions || []).length }} Jabatan)
                            </option>
                        </select>
                    </div>

                    <!-- Dropdown 2: Pilih Jabatan -->
                    <div>
                        <label for="select-jabatan" class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 block">
                            2. Pilih Jabatan:
                        </label>
                        <select
                            id="select-jabatan"
                            :value="selectedJabatan"
                            @change="onJabatanChange"
                            :disabled="selectedBidang === 'superadmin' || currentDeptPositions.length === 0"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-lowest text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-medium cursor-pointer shadow-xs disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <!-- Superadmin Case -->
                            <option v-if="selectedBidang === 'superadmin'" value="superadmin">
                                Superadmin (Akses Penuh Seluruh Sistem)
                            </option>

                            <!-- Empty Positions Case -->
                            <option v-else-if="currentDeptPositions.length === 0" value="" disabled>
                                (Belum ada jabatan pada bidang ini)
                            </option>

                            <!-- Department Positions Case -->
                            <template v-else>
                                <option value="ALL_IN_DEPT" class="font-bold text-primary">
                                    Semua Jabatan di Bidang Ini (Total {{ currentDeptPositions.length }} Jabatan)
                                </option>
                                <option disabled>────────── JABATAN SPESIFIK ──────────</option>
                                <option v-for="pos in currentDeptPositions" :key="'pos_' + pos.id" :value="pos.name">
                                    {{ pos.name }}
                                </option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="px-6 py-3 bg-surface-container-low/40 border-b border-outline-variant/30 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 flex-wrap">
                <span v-if="isSuperadminActive" class="text-on-surface font-medium">
                    Role <strong>Superadmin</strong> memiliki hak akses penuh ke seluruh fitur dan tidak dibatasi oleh matriks perizinan.
                </span>
                <span v-else-if="isAllInDeptActive" class="text-on-surface">
                    Sedang mengatur hak akses untuk <strong>Semua Jabatan</strong> ({{ currentDeptPositions.length }} jabatan) di bidang: <strong class="text-primary font-bold">{{ selectedBidangName }}</strong>
                </span>
                <span v-else class="text-on-surface">
                    Sedang mengatur hak akses jabatan: <strong class="text-primary font-bold ml-1">{{ selectedJabatan }}</strong>
                    <span v-if="selectedBidangName" class="ml-2 text-xs text-on-surface-variant px-2 py-0.5 rounded-md bg-surface-container-high font-medium">
                        Bidang: {{ selectedBidangName }}
                    </span>
                </span>

                <!-- Tri-State Indicator Legend -->
                <div v-if="!isSuperadminActive" class="flex items-center gap-2.5 text-[11px] text-on-surface-variant font-medium pt-1 sm:pt-0 sm:border-l sm:border-outline-variant/40 sm:pl-3">
                    <span class="flex items-center gap-1" title="Semua sub-modul tercentang penuh">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-primary text-white text-[10px] font-bold leading-none">✓</span>
                        Penuh
                    </span>
                    <span class="flex items-center gap-1" title="Checklist sebagian: beberapa sub-modul aktif, beberapa tidak">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-primary/20 border border-primary text-primary text-[11px] font-black leading-none">−</span>
                        Sebagian (Strip)
                    </span>
                    <span class="flex items-center gap-1" title="Tidak ada yang tercentang">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-surface-container-lowest border border-outline-variant text-[10px]"></span>
                        Kosong
                    </span>
                </div>
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
                            :class="{ 'bg-surface-container-low/20': isChild(item) }"
                        >
                            <!-- Nama Fitur & Deskripsi -->
                            <td
                                class="border border-outline-variant/60 p-3"
                                :class="isChild(item) ? 'pl-8 sm:pl-10' : 'pl-5'"
                            >
                                <div class="flex items-center gap-2">
                                    <span v-if="isChild(item)" class="material-symbols-outlined text-[16px] text-primary/70 shrink-0">subdirectory_arrow_right</span>
                                    <div class="font-bold text-on-surface text-xs sm:text-sm flex items-center gap-1.5 flex-wrap">
                                        <span>{{ item.name }}</span>
                                        <span v-if="hasChildren(item)" class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-primary/10 text-primary border border-primary/20">
                                            Modul Utama
                                        </span>
                                        <span v-else-if="isChild(item)" class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-surface-container-high text-on-surface-variant border border-outline-variant/40">
                                            Sub-Modul
                                        </span>
                                        <code class="text-[10px] text-on-surface-variant font-mono bg-surface-container-high px-1 rounded">{{ item.group }}</code>
                                    </div>
                                </div>
                                <div class="text-[11px] text-on-surface-variant leading-relaxed mt-0.5" :class="isChild(item) ? 'pl-6' : ''">{{ item.description }}</div>
                            </td>

                            <!-- Kategori Badge -->
                            <td class="border border-outline-variant/60 p-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold border" :class="getCategoryMeta(item.category).color">
                                    {{ item.category }}
                                </span>
                            </td>

                            <!-- Aksi-Aksi: View, Create, Update, Delete, Export, Import -->
                            <td
                                v-for="act in ['view', 'create', 'update', 'delete', 'export', 'import']"
                                :key="act"
                                class="border border-outline-variant/60 p-2.5 text-center align-middle"
                            >
                                <template v-if="item.supported_actions.includes(act)">
                                    <!-- Parent Item with Children: Tri-State Indicator Button -->
                                    <button
                                        v-if="hasChildren(item)"
                                        type="button"
                                        :disabled="isSuperadminActive"
                                        @click="toggleParentAction(item, act)"
                                        class="inline-flex items-center justify-center w-5 h-5 rounded border transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed select-none mx-auto"
                                        :class="{
                                            'bg-primary border-primary text-white shadow-2xs': getActionState(item, act) === 'checked',
                                            'bg-primary/20 border-primary text-primary font-bold shadow-2xs': getActionState(item, act) === 'indeterminate',
                                            'bg-surface-container-lowest border-outline-variant hover:border-primary/60': getActionState(item, act) === 'unchecked'
                                        }"
                                        :title="getActionTitle(item, act)"
                                    >
                                        <span v-if="getActionState(item, act) === 'checked'" class="material-symbols-outlined text-[15px] leading-none font-bold">check</span>
                                        <span v-else-if="getActionState(item, act) === 'indeterminate'" class="material-symbols-outlined text-[15px] leading-none font-black">remove</span>
                                    </button>

                                    <!-- Leaf / Child Item: Standard Checkbox -->
                                    <input
                                        v-else
                                        type="checkbox"
                                        v-model="item['can_' + act]"
                                        @change="onChildActionChange(item, act)"
                                        :disabled="isSuperadminActive"
                                        class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-60 w-4 h-4"
                                    />
                                </template>
                                <span v-else class="text-outline-variant">-</span>
                            </td>

                            <!-- Aksi Cepat Baris -->
                            <td class="border border-outline-variant/60 p-3 text-center" v-if="!isSuperadminActive">
                                <button
                                    type="button"
                                    @click="toggleRow(item)"
                                    class="p-1 rounded-lg hover:bg-surface-container-high text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                                    :title="hasChildren(item) ? 'Beralih centang semua aksi pada modul utama dan sub-modul ini' : 'Beralih centang semua aksi pada fitur ini'"
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
                :disabled="isSaving || isLoading || isSuperadminActive || (isAllInDeptActive && currentDeptPositions.length === 0)"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-semibold text-xs transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="isSaving" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                <span v-else class="material-symbols-outlined text-[16px]">save</span>
                <span>{{ isSaving ? 'Menyimpan...' : (isAllInDeptActive ? `Simpan untuk Semua Jabatan (${currentDeptPositions.length})` : 'Simpan Hak Akses') }}</span>
            </button>
        </div>
    </div>
</template>
