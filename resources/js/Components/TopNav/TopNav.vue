<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: {
        type: String,
        default: 'Overview',
    },
});

defineEmits(['toggle-sidebar']);

const page = usePage();
const isSwitching = ref(false);
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const academicYears = computed(() => page.props.academicYears || []);
const selectedAcademicYear = computed(() => page.props.selectedAcademicYear || null);

function toggleDropdown() {
    if (!isSwitching.value) {
        isDropdownOpen.value = !isDropdownOpen.value;
    }
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

function selectAcademicYear(year) {
    if (!year || Number(year.id) === Number(selectedAcademicYear.value?.id)) {
        closeDropdown();
        return;
    }

    closeDropdown();
    isSwitching.value = true;
    router.post('/academic-years/switch', {
        academic_year_id: year.id,
    }, {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => {
            isSwitching.value = false;
        },
    });
}

function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
}

function handleKeydown(event) {
    if (event.key === 'Escape') {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <header class="bg-surface-container-lowest shadow-sm docked full-width top-0 sticky z-30 border-b border-outline-variant/30 transition-all duration-300">
        <div class="flex justify-between items-center h-16 px-4 md:px-gutter max-w-container-max mx-auto gap-2 md:gap-4">
            <!-- Left: Hamburger + Brand Icon -->
            <div class="flex items-center shrink-0">
                <button
                    @click="$emit('toggle-sidebar')"
                    class="md:hidden p-2 mr-1 -ml-2 text-on-surface hover:bg-surface-variant rounded-full transition-colors focus:outline-none"
                    aria-label="Toggle Sidebar"
                >
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <!-- Brand icon only on mobile -->
                <span class="material-symbols-outlined text-primary text-2xl mr-2 md:hidden">school</span>
            </div>

            <!-- Center/Right: Academic Year Selector in TopBar -->
            <div class="flex items-center justify-end md:justify-start flex-grow gap-2 md:gap-4">
                <!-- Academic Year Custom Dropdown -->
                <div ref="dropdownRef" class="relative">
                    <button
                        type="button"
                        @click="toggleDropdown"
                        :disabled="isSwitching"
                        class="group relative flex items-center gap-2 px-3 py-1.5 bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 hover:border-primary/50 rounded-xl transition-all shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/25"
                        :class="{ 'ring-2 ring-primary/30 border-primary bg-surface-container': isDropdownOpen, 'opacity-60 pointer-events-none': isSwitching }"
                        aria-haspopup="listbox"
                        :aria-expanded="isDropdownOpen"
                    >
                        <!-- Calendar / Loading Icon -->
                        <span
                            class="material-symbols-outlined text-primary text-[20px] shrink-0 transition-transform group-hover:scale-105"
                            :class="{ 'animate-spin': isSwitching }"
                        >
                            {{ isSwitching ? 'sync' : 'calendar_today' }}
                        </span>

                        <div class="flex flex-col text-left min-w-0">
                            <span class="hidden sm:block text-[9px] font-bold text-on-surface-variant uppercase tracking-wider leading-none">
                                Tahun Ajaran
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs sm:text-sm font-bold text-primary truncate">
                                    {{ selectedAcademicYear ? `TA ${selectedAcademicYear.name} (${selectedAcademicYear.semester})` : 'Pilih TA' }}
                                </span>
                                <span
                                    v-if="selectedAcademicYear?.is_active"
                                    class="hidden md:inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-primary/15 text-primary border border-primary/25"
                                >
                                    Aktif
                                </span>
                            </div>
                        </div>

                        <!-- Dropdown Chevron Icon -->
                        <span
                            class="material-symbols-outlined text-primary/70 text-[18px] shrink-0 transition-transform duration-200 ml-0.5"
                            :class="{ 'rotate-180 text-primary': isDropdownOpen }"
                        >
                            expand_more
                        </span>
                    </button>

                    <!-- Dropdown Popover Menu -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                        enter-to-class="transform scale-100 opacity-100 translate-y-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform scale-100 opacity-100 translate-y-0"
                        leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="isDropdownOpen"
                            class="absolute right-0 mt-2 w-72 sm:w-80 bg-surface-container-lowest border border-outline-variant/40 rounded-2xl shadow-[0px_10px_35px_rgba(0,40,20,0.15)] z-50 overflow-hidden"
                        >
                            <!-- Dropdown Header -->
                            <div class="p-3 bg-surface-container-low/70 border-b border-outline-variant/30 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-on-surface uppercase tracking-wider">Pilih Tahun Ajaran</h4>
                                    <p class="text-[11px] text-on-surface-variant mt-0.5">Filter data seluruh sistem</p>
                                </div>
                                <span class="material-symbols-outlined text-primary text-[18px]">calendar_month</span>
                            </div>

                            <!-- List of Academic Years -->
                            <div class="max-h-64 overflow-y-auto p-1.5 space-y-1">
                                <button
                                    v-for="year in academicYears"
                                    :key="year.id"
                                    type="button"
                                    @click="selectAcademicYear(year)"
                                    class="w-full flex items-center justify-between p-2.5 rounded-xl text-left transition-all cursor-pointer group"
                                    :class="Number(selectedAcademicYear?.id) === Number(year.id)
                                        ? 'bg-primary/10 text-primary font-bold border border-primary/20 shadow-2xs'
                                        : 'text-on-surface hover:bg-surface-container hover:text-primary font-medium'"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span
                                            class="material-symbols-outlined text-[18px] shrink-0"
                                            :class="Number(selectedAcademicYear?.id) === Number(year.id) ? 'text-primary' : 'text-outline group-hover:text-primary'"
                                        >
                                            {{ Number(selectedAcademicYear?.id) === Number(year.id) ? 'radio_button_checked' : 'radio_button_unchecked' }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs sm:text-sm leading-tight flex items-center gap-1.5">
                                                <span class="font-semibold">TA {{ year.name }}</span>
                                                <span
                                                    class="text-[10px] px-1.5 py-0.5 rounded-md font-semibold"
                                                    :class="year.semester === 'Ganjil' ? 'bg-primary/10 text-primary' : 'bg-secondary-container/40 text-on-secondary-container'"
                                                >
                                                    {{ year.semester }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Active Badge -->
                                    <div class="shrink-0 flex items-center gap-1.5">
                                        <span
                                            v-if="year.is_active"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary text-on-primary shadow-2xs"
                                            title="Tahun Ajaran Aktif Utama"
                                        >
                                            <span class="material-symbols-outlined text-[10px]">check</span>
                                            Aktif
                                        </span>
                                    </div>
                                </button>
                            </div>

                            <!-- Dropdown Footer -->
                            <div class="p-2 border-t border-outline-variant/30 bg-surface-container-low/30 flex items-center justify-between">
                                <Link
                                    href="/portal"
                                    @click="closeDropdown"
                                    class="w-full flex items-center justify-center gap-1.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 rounded-lg transition-colors cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[15px]">tune</span>
                                    <span>Kelola di Manajemen Portal</span>
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Right Actions: Role, Notifications & Profile -->
                <div class="flex items-center space-x-1 sm:space-x-2 shrink-0 ml-auto">
                    <!-- Role Badge (Hidden on mobile) -->
                    <div class="hidden lg:flex flex-col items-end mr-1">
                        <span class="font-body-sm text-body-sm text-on-surface font-semibold leading-tight">Admin</span>
                        <span class="text-xs text-on-surface-variant leading-tight">Karanganyar</span>
                    </div>

                    <button
                        type="button"
                        aria-label="Notifications"
                        class="text-on-surface-variant hover:text-primary transition-colors duration-200 cursor-pointer p-2 rounded-full hover:bg-surface-variant flex items-center justify-center"
                    >
                        <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
                    </button>
                    <button
                        type="button"
                        aria-label="Account profile"
                        class="text-on-surface-variant hover:text-primary transition-colors duration-200 cursor-pointer p-2 rounded-full hover:bg-surface-variant flex items-center justify-center"
                    >
                        <span class="material-symbols-outlined" data-icon="account_circle">account_circle</span>
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
