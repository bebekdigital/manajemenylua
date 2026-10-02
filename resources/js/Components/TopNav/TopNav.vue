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
            
            <!-- Left: Title -->
            <div class="flex items-center gap-2 md:gap-4 shrink-0">

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
