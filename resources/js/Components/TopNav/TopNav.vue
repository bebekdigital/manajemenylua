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

const isProfileMenuOpen = ref(false);
const profileMenuRef = ref(null);

const academicYears = computed(() => page.props.academicYears || []);
const selectedAcademicYear = computed(() => page.props.selectedAcademicYear || null);

const shortAcademicYear = computed(() => {
    if (!selectedAcademicYear.value) return '...';
    const name = selectedAcademicYear.value.name;
    if (name && name.includes('/')) {
        const parts = name.split('/');
        if (parts.length === 2) {
            return `${parts[0].slice(-2)}/${parts[1].slice(-2)}`;
        }
    }
    return name;
});

function toggleDropdown() {
    if (!isSwitching.value) {
        isDropdownOpen.value = !isDropdownOpen.value;
    }
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

function toggleProfileMenu() {
    isProfileMenuOpen.value = !isProfileMenuOpen.value;
}

function closeProfileMenu() {
    isProfileMenuOpen.value = false;
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
    if (profileMenuRef.value && !profileMenuRef.value.contains(event.target)) {
        closeProfileMenu();
    }
}

function handleKeydown(event) {
    if (event.key === 'Escape') {
        closeDropdown();
        closeProfileMenu();
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
        <div class="flex items-center justify-between h-16 px-4 md:px-gutter max-w-container-max mx-auto gap-2 md:gap-4 w-full">
            
            <!-- Left: TA Selector & Title -->
            <div class="flex items-center gap-2 md:gap-4 shrink-0">
                <!-- Academic Year Custom Dropdown -->
                <div ref="dropdownRef" class="relative">
                    <button
                        type="button"
                        @click="toggleDropdown"
                        :disabled="isSwitching"
                        class="group relative flex items-center gap-1.5 md:gap-2 px-2.5 py-1.5 md:px-3 md:py-1.5 bg-white md:hover:bg-surface-container-low border border-outline-variant/60 md:hover:border-primary/50 rounded-lg md:rounded-xl transition-all shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/25"
                        :class="{ 'ring-2 ring-primary/30 border-primary bg-white': isDropdownOpen, 'opacity-60 pointer-events-none': isSwitching }"
                        aria-haspopup="listbox"
                        :aria-expanded="isDropdownOpen"
                    >
                        <!-- Calendar / Loading Icon (Hidden on Mobile/Tablet) -->
                        <span
                            class="hidden lg:block material-symbols-outlined text-primary text-[20px] shrink-0 transition-transform group-hover:scale-105"
                            :class="{ 'animate-spin': isSwitching }"
                        >
                            {{ isSwitching ? 'sync' : 'calendar_today' }}
                        </span>

                        <div class="flex flex-col text-left min-w-0 text-center md:text-left">
                            <!-- Desktop View -->
                            <span class="hidden sm:block text-[9px] font-bold text-on-surface-variant uppercase tracking-wider leading-none">
                                Tahun Ajaran
                            </span>
                            <div class="hidden sm:flex items-center gap-1.5 mt-0.5">
                                <span class="text-sm font-bold text-primary truncate">
                                    {{ selectedAcademicYear ? `TA ${selectedAcademicYear.name} (${selectedAcademicYear.semester})` : 'Pilih TA' }}
                                </span>
                                <span
                                    v-if="selectedAcademicYear?.is_active"
                                    class="hidden lg:inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-primary/15 text-primary border border-primary/25"
                                >
                                    Aktif
                                </span>
                            </div>

                            <!-- Mobile View -->
                            <div class="flex sm:hidden flex-col items-center justify-center">
                                <span class="text-[12px] font-bold text-primary leading-none">
                                    TA {{ shortAcademicYear }}
                                </span>
                                <span class="text-[9px] font-medium text-on-surface-variant leading-none mt-1">
                                    {{ selectedAcademicYear?.semester }}
                                </span>
                            </div>
                        </div>

                        <!-- Dropdown Chevron Icon (Hidden on Mobile/Tablet) -->
                        <span
                            class="hidden lg:block material-symbols-outlined text-primary/70 text-[18px] shrink-0 transition-transform duration-200 ml-0.5"
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
                            class="absolute left-0 mt-2 w-72 sm:w-80 bg-white border border-outline-variant/40 rounded-2xl shadow-[0px_10px_35px_rgba(0,40,20,0.15)] z-50 overflow-hidden"
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
                                        : 'text-on-surface md:hover:bg-surface-container md:hover:text-primary font-medium'"
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

                <!-- Page Title (Desktop only) -->
                <div class="hidden md:flex items-center shrink-0">
                    <h1 class="hidden text-sm font-bold text-on-surface">{{ pageTitle }}</h1>
                </div>
            </div>

            <!-- Center: Empty space for Brand Logo (Mobile) -->
            <div class="flex-grow flex justify-center md:hidden">
                <!-- Nanti untuk logo brand -->
            </div>

            <!-- Right Actions: Role, Notifications & Profile -->
            <div class="flex items-center space-x-1 sm:space-x-3 shrink-0">
                <button
                    type="button"
                    aria-label="Notifications"
                    class="text-on-surface-variant hover:text-primary transition-colors duration-200 cursor-pointer p-2 rounded-full hover:bg-surface-variant flex items-center justify-center"
                >
                    <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
                </button>

                <!-- Desktop Profile Link (Hidden on mobile) -->
                <Link
                    href="/profile"
                    class="hidden lg:flex items-center gap-2.5 p-1 pr-3 bg-surface-container-low hover:bg-surface-container-low/80 border border-outline-variant/30 rounded-full transition-colors cursor-pointer group"
                >
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">person</span>
                    </div>
                    <div class="flex flex-col items-start">
                        <span class="text-xs font-bold text-on-surface leading-tight capitalize">{{ page.props.auth?.user?.name || 'Guest' }}</span>
                        <span class="text-[10px] font-medium text-on-surface-variant leading-tight capitalize">{{ page.props.auth?.user?.role || 'Guest' }}</span>
                    </div>
                </Link>

                <!-- Mobile Profile Dropdown (Hidden on desktop) -->
                <div class="relative lg:hidden" ref="profileMenuRef">
                    <button
                        type="button"
                        @click="toggleProfileMenu"
                        aria-label="Account profile"
                        class="text-on-surface-variant hover:text-primary transition-colors duration-200 cursor-pointer p-2 rounded-full hover:bg-surface-variant flex items-center justify-center"
                        :class="{ 'bg-surface-variant text-primary': isProfileMenuOpen }"
                    >
                        <span class="material-symbols-outlined" data-icon="account_circle">account_circle</span>
                    </button>

                    <!-- Profile Dropdown -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                        enter-to-class="transform scale-100 opacity-100 translate-y-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform scale-100 opacity-100 translate-y-0"
                        leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="isProfileMenuOpen"
                            class="absolute right-0 mt-2 w-56 bg-white border border-outline-variant/40 rounded-2xl shadow-[0px_10px_35px_rgba(0,40,20,0.15)] z-50 overflow-hidden"
                        >
                            <!-- Profile Header -->
                            <div class="p-4 bg-surface-container-low/70 border-b border-outline-variant/30 flex flex-col items-center text-center">
                                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-2">
                                    <span class="material-symbols-outlined text-[24px]">person</span>
                                </div>
                                <h4 class="text-sm font-bold text-on-surface capitalize leading-tight">{{ page.props.auth?.user?.name || 'Guest' }}</h4>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 capitalize">{{ page.props.auth?.user?.role || 'Guest' }}</p>
                            </div>

                            <!-- Menu Items -->
                            <div class="p-1.5 space-y-0.5">
                                <Link
                                    href="/profile"
                                    @click="closeProfileMenu"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-low hover:text-primary rounded-xl transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px]">person</span>
                                    <span>Profil</span>
                                </Link>
                                <Link
                                    href="/portal"
                                    @click="closeProfileMenu"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-low hover:text-primary rounded-xl transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px]">settings</span>
                                    <span>Pengaturan</span>
                                </Link>
                                <div class="h-[1px] bg-outline-variant/30 my-1 mx-2"></div>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[18px]">logout</span>
                                    <span>Logout</span>
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>
