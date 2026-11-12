<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    editing: {
        type: Object,
        default: null,
    },
    schoolUnit: {
        type: Object,
        default: null,
    },
    existingPrograms: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    name: '',
});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => isEditing.value ? 'Edit Program' : 'Tambah Program');

// State Dropdown & Combobox
const isDropdownOpen = ref(false);
const inputContainerRef = ref(null);

const defaultPrograms = ['Umum', 'Boarding', 'Fullday', 'Tahfidz', 'Reguler', 'Kitab'];

// Kumpulan seluruh program yang sudah pernah ditambahkan di seluruh unit
const allAvailablePrograms = computed(() => {
    const set = new Set();

    (props.existingPrograms || []).forEach(p => {
        const str = typeof p === 'string' ? p : p?.name;
        if (str && typeof str === 'string' && str.trim()) {
            set.add(str.trim());
        }
    });

    defaultPrograms.forEach(p => set.add(p));

    return Array.from(set).sort((a, b) => a.localeCompare(b));
});

// Daftar nama program yang sudah ada pada unit yang sedang dipilih saat ini
const currentUnitProgramNames = computed(() => {
    if (!props.schoolUnit?.programs) return new Set();
    return new Set(
        props.schoolUnit.programs.map(p => (typeof p === 'string' ? p : p.name)?.trim().toLowerCase())
    );
});

function isProgramAlreadyInUnit(name) {
    if (!name) return false;
    if (isEditing.value && props.editing?.name?.toLowerCase() === name.toLowerCase()) {
        return false;
    }
    return currentUnitProgramNames.value.has(name.trim().toLowerCase());
}

// Program yang difilter saat user mengetik di input
const filteredPrograms = computed(() => {
    const q = (form.name || '').trim().toLowerCase();
    if (!q) return allAvailablePrograms.value;
    return allAvailablePrograms.value.filter(p => p.toLowerCase().includes(q));
});

const hasExactMatch = computed(() => {
    const q = (form.name || '').trim().toLowerCase();
    if (!q) return true;
    return allAvailablePrograms.value.some(p => p.toLowerCase() === q);
});

function openDropdown() {
    isDropdownOpen.value = true;
}

function toggleDropdown() {
    isDropdownOpen.value = !isDropdownOpen.value;
}

function selectProgram(name) {
    if (isProgramAlreadyInUnit(name)) return;
    form.name = name;
    isDropdownOpen.value = false;
    form.clearErrors('name');
}

function handleClickOutside(event) {
    if (inputContainerRef.value && !inputContainerRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(
    () => props.show,
    (val) => {
        if (val) {
            if (props.editing) {
                form.name = props.editing.name;
            } else {
                form.reset();
            }
            form.clearErrors();
            isDropdownOpen.value = false;
        }
    }
);

function submit() {
    isDropdownOpen.value = false;
    if (props.editing) {
        form.put(`/portal/school-programs/${props.editing.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(`/portal/school-units/${props.schoolUnit.id}/programs`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-backdrop">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="$emit('close')"
            >
                <Transition name="modal-content">
                    <div
                        v-if="show"
                        class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-lg border border-outline-variant/30 overflow-hidden"
                    >
                        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-lg">
                                        {{ isEditing ? 'edit' : 'add_circle' }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-on-surface">{{ modalTitle }}</h3>
                            </div>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="p-2 rounded-full text-on-surface-variant hover:bg-surface-variant hover:text-on-surface transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="px-6 py-5 space-y-5">
                            <div v-if="schoolUnit && !isEditing" class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/40 text-sm">
                                <span class="text-on-surface-variant">Menambahkan program untuk unit: </span>
                                <strong class="text-on-surface">{{ schoolUnit.name }}</strong>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                        Nama Program <span class="text-error">*</span>
                                    </label>
                                    <span class="text-[11px] text-on-surface-variant font-medium">
                                        Pilih dari dropdown atau isi sendiri
                                    </span>
                                </div>

                                <!-- Combobox Container: Input Text + Dropdown Pilihan -->
                                <div ref="inputContainerRef" class="relative">
                                    <div class="relative flex items-center">
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            required
                                            autocomplete="off"
                                            @focus="openDropdown"
                                            class="w-full pl-4 pr-10 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            placeholder="Pilih dari dropdown atau ketik nama program..."
                                            :class="{ 'border-error': form.errors.name }"
                                        />
                                        <button
                                            type="button"
                                            @click.stop="toggleDropdown"
                                            class="absolute right-2 p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-variant transition-colors cursor-pointer"
                                            title="Buka pilihan program yang pernah ditambahkan"
                                        >
                                            <span
                                                class="material-symbols-outlined text-[20px] transition-transform duration-200"
                                                :class="{ 'rotate-180': isDropdownOpen }"
                                            >
                                                expand_more
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Floating Dropdown Menu -->
                                    <div
                                        v-if="isDropdownOpen"
                                        class="absolute left-0 right-0 top-full mt-1.5 bg-surface-container-lowest border border-outline-variant/50 rounded-2xl shadow-xl z-50 overflow-hidden max-h-60 overflow-y-auto animate-in fade-in duration-100"
                                    >
                                        <!-- Header Dropdown -->
                                        <div class="px-3.5 py-2 bg-surface-container-low/70 border-b border-outline-variant/30 flex items-center justify-between text-[11px] font-semibold text-on-surface-variant">
                                            <span>PROGRAM YANG PERNAH DITAMBAHKAN</span>
                                            <span class="text-[10px] text-outline font-normal">Klik untuk memilih</span>
                                        </div>

                                        <!-- List Programs -->
                                        <div class="p-1 space-y-0.5">
                                            <button
                                                v-for="prog in filteredPrograms"
                                                :key="prog"
                                                type="button"
                                                @click="selectProgram(prog)"
                                                :disabled="isProgramAlreadyInUnit(prog)"
                                                class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors cursor-pointer"
                                                :class="[
                                                    isProgramAlreadyInUnit(prog)
                                                        ? 'opacity-40 cursor-not-allowed bg-surface-container-low/40 text-on-surface-variant'
                                                        : (form.name === prog
                                                            ? 'bg-primary/10 text-primary font-bold'
                                                            : 'text-on-surface hover:bg-surface-variant/60')
                                                ]"
                                            >
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="material-symbols-outlined text-[16px] text-primary" v-if="form.name === prog">
                                                        check
                                                    </span>
                                                    <span class="material-symbols-outlined text-[16px] text-outline" v-else>
                                                        school
                                                    </span>
                                                    <span class="truncate">{{ prog }}</span>
                                                </div>

                                                <span v-if="isProgramAlreadyInUnit(prog)" class="text-[10px] text-outline font-medium shrink-0">
                                                    (Sudah ada di unit ini)
                                                </span>
                                            </button>

                                            <!-- Opsi gunakan input manual jika belum ada di list -->
                                            <button
                                                v-if="form.name && !hasExactMatch"
                                                type="button"
                                                @click="isDropdownOpen = false"
                                                class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold text-primary bg-primary/5 hover:bg-primary/10 border border-primary/20 flex items-center gap-2 transition-colors cursor-pointer mt-1"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                                <span class="truncate">Gunakan <strong>"{{ form.name }}"</strong> sebagai program baru</span>
                                            </button>

                                            <!-- Empty State jika pencarian tidak cocok -->
                                            <div v-if="filteredPrograms.length === 0 && !form.name" class="p-4 text-center text-xs text-on-surface-variant">
                                                Belum ada program terdaftar. Ketik nama program di atas.
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="form.errors.name" class="text-error text-xs mt-1.5">{{ form.errors.name }}</div>
                                </div>

                                <!-- Pilihan Cepat (Quick Chips) -->
                                <div v-if="allAvailablePrograms.length > 0" class="mt-2.5 flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[11px] text-on-surface-variant font-semibold">Pilihan cepat:</span>
                                    <button
                                        v-for="prog in allAvailablePrograms.slice(0, 6)"
                                        :key="prog"
                                        type="button"
                                        @click="selectProgram(prog)"
                                        :disabled="isProgramAlreadyInUnit(prog)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all border cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                        :class="form.name === prog
                                            ? 'bg-primary text-on-primary border-primary shadow-2xs font-bold'
                                            : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high border-outline-variant/60'"
                                        :title="isProgramAlreadyInUnit(prog) ? 'Program sudah ada di unit ini' : `Pilih program ${prog}`"
                                    >
                                        {{ prog }}
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-outline-variant/30">
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/50 transition-colors cursor-pointer"
                                    :disabled="form.processing"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">{{ isEditing ? 'save' : 'add_circle' }}</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
    transition: opacity 0.2s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}
.modal-content-enter-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-content-leave-active {
    transition: all 0.15s ease-in;
}
.modal-content-enter-from {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
}
.modal-content-leave-to {
    opacity: 0;
    transform: scale(0.98) translateY(4px);
}
</style>


<style scoped>
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
    transition: opacity 0.2s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}
.modal-content-enter-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-content-leave-active {
    transition: all 0.15s ease-in;
}
.modal-content-enter-from {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
}
.modal-content-leave-to {
    opacity: 0;
    transform: scale(0.98) translateY(4px);
}
</style>
