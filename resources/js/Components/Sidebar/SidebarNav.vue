<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import SidebarMenuItem from './SidebarMenuItem.vue';

defineProps({
    currentRoute: {
        type: String,
        default: 'overview',
    },
    isOpen: {
        type: Boolean,
        default: false,
    }
});

defineEmits(['close']);

const isDataMenuOpen = ref(false);

function toggleDataMenu() {
    isDataMenuOpen.value = !isDataMenuOpen.value;
}
</script>

<template>
    <!-- Sidebar: Hidden on mobile, visible on md+ -->
    <nav
        class="hidden md:flex bg-emerald-900 border-r border-emerald-800 h-screen w-64 fixed left-0 top-0 flex-col p-4 space-y-2 z-40"
    >
        <!-- Brand Header -->
        <div class="mb-8 px-2 flex items-center space-x-3 mt-4">
            <div class="w-10 h-10 rounded-full bg-yellow-200 flex items-center justify-center text-emerald-900 font-headline-md shadow-sm shrink-0">
                <span class="material-symbols-outlined" data-icon="school" data-weight="fill">school</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-white leading-tight text-lg">Yayasan Li Ulil Albab Karanganyar</h2>
            </div>
        </div>

        <!-- Main Navigation Links -->
        <ul class="flex-grow space-y-1 overflow-y-auto">
            <!-- Beranda -->
            <SidebarMenuItem
                href="/beranda"
                icon="home"
                label="Beranda"
                :active="currentRoute === 'overview'"
            />

            <!-- Data & Administrasi (with sub-menu) -->
            <li>
                <button
                    @click="toggleDataMenu"
                    :class="[
                        'w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all cursor-pointer active:scale-95 duration-200',
                        ['students', 'staff', 'administration'].includes(currentRoute)
                            ? 'bg-emerald-800 text-yellow-200 font-semibold shadow-sm'
                            : 'text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 font-medium'
                    ]"
                >
                    <div class="flex items-center space-x-3">
                        <span class="material-symbols-outlined text-[22px]">folder_open</span>
                        <span>Data & Administrasi</span>
                    </div>
                    <span
                        class="material-symbols-outlined text-[18px] transition-transform duration-200"
                        :class="{ 'rotate-180': isDataMenuOpen }"
                    >expand_more</span>
                </button>

                <!-- Sub Menu -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-48 opacity-100"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="max-h-48 opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <ul v-show="isDataMenuOpen || ['students', 'staff', 'administration'].includes(currentRoute)" class="mt-1 ml-4 pl-4 border-l border-emerald-700 space-y-0.5 overflow-hidden">
                        <SidebarMenuItem
                            href="/students"
                            icon="groups"
                            label="Data Siswa"
                            :active="currentRoute === 'students'"
                        />
                        <SidebarMenuItem
                            href="#"
                            icon="badge"
                            label="Data Pegawai"
                            :active="currentRoute === 'staff'"
                        />
                        <SidebarMenuItem
                            href="/administration"
                            icon="description"
                            label="Administrasi"
                            :active="currentRoute === 'administration'"
                        />
                    </ul>
                </Transition>
            </li>

            <!-- Pembelajaran -->
            <SidebarMenuItem
                href="#"
                icon="menu_book"
                label="Pembelajaran"
                :active="currentRoute === 'pembelajaran'"
            />

            <!-- Kesiswaan -->
            <SidebarMenuItem
                href="#"
                icon="diversity_3"
                label="Kesiswaan"
                :active="currentRoute === 'kesiswaan'"
            />

            <!-- Pengaturan (sebelumnya Manajemen Portal) -->
            <SidebarMenuItem
                href="/portal"
                icon="settings"
                label="Pengaturan"
                :active="currentRoute === 'portal'"
            />
        </ul>

        <!-- Bottom Action & Utilities -->
        <div class="mt-auto pt-4 border-t border-emerald-800 space-y-2">
            <button class="w-full bg-yellow-200 text-emerald-900 py-2 px-4 rounded-full font-label-md hover:bg-yellow-300 transition-colors mb-4 flex items-center justify-center space-x-2 shadow-sm cursor-pointer active:scale-98 font-semibold">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Generate Report</span>
            </button>
            <ul class="space-y-1">
                <li>
                    <a class="flex items-center space-x-3 px-4 py-2 text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 rounded-xl transition-colors duration-200 cursor-pointer active:scale-95 font-medium text-sm" href="#">
                        <span class="material-symbols-outlined text-[20px]" data-icon="help">help</span>
                        <span>Help Center</span>
                    </a>
                </li>
                <li>
                    <Link href="/logout" method="post" as="button" class="w-full flex items-center space-x-3 px-4 py-2 text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 rounded-xl transition-colors duration-200 cursor-pointer active:scale-95 font-medium text-sm">
                        <span class="material-symbols-outlined text-[20px]" data-icon="logout">logout</span>
                        <span>Logout</span>
                    </Link>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-emerald-900 border-t border-emerald-800 shadow-[0_-4px_20px_rgba(0,30,15,0.3)] safe-area-bottom">
        <div class="flex items-center justify-around px-1 py-1.5">
            <!-- Beranda -->
            <Link
                href="/beranda"
                :class="[
                    'flex flex-col items-center justify-center px-2 py-1.5 rounded-xl min-w-[56px] transition-all',
                    currentRoute === 'overview'
                        ? 'text-yellow-200'
                        : 'text-emerald-200/70 active:text-yellow-200'
                ]"
            >
                <span class="material-symbols-outlined text-[22px]">home</span>
                <span class="text-[10px] font-semibold mt-0.5 leading-tight">Beranda</span>
            </Link>

            <!-- Data -->
            <Link
                href="/students"
                :class="[
                    'flex flex-col items-center justify-center px-2 py-1.5 rounded-xl min-w-[56px] transition-all',
                    ['students', 'staff', 'administration'].includes(currentRoute)
                        ? 'text-yellow-200'
                        : 'text-emerald-200/70 active:text-yellow-200'
                ]"
            >
                <span class="material-symbols-outlined text-[22px]">folder_open</span>
                <span class="text-[10px] font-semibold mt-0.5 leading-tight">Data</span>
            </Link>

            <!-- Pembelajaran -->
            <Link
                href="#"
                :class="[
                    'flex flex-col items-center justify-center px-2 py-1.5 rounded-xl min-w-[56px] transition-all',
                    currentRoute === 'pembelajaran'
                        ? 'text-yellow-200'
                        : 'text-emerald-200/70 active:text-yellow-200'
                ]"
            >
                <span class="material-symbols-outlined text-[22px]">menu_book</span>
                <span class="text-[10px] font-semibold mt-0.5 leading-tight">Belajar</span>
            </Link>

            <!-- Kesiswaan -->
            <Link
                href="#"
                :class="[
                    'flex flex-col items-center justify-center px-2 py-1.5 rounded-xl min-w-[56px] transition-all',
                    currentRoute === 'kesiswaan'
                        ? 'text-yellow-200'
                        : 'text-emerald-200/70 active:text-yellow-200'
                ]"
            >
                <span class="material-symbols-outlined text-[22px]">diversity_3</span>
                <span class="text-[10px] font-semibold mt-0.5 leading-tight">Kesiswaan</span>
            </Link>

            <!-- Pengaturan -->
            <Link
                href="/portal"
                :class="[
                    'flex flex-col items-center justify-center px-2 py-1.5 rounded-xl min-w-[56px] transition-all',
                    currentRoute === 'portal'
                        ? 'text-yellow-200'
                        : 'text-emerald-200/70 active:text-yellow-200'
                ]"
            >
                <span class="material-symbols-outlined text-[22px]">settings</span>
                <span class="text-[10px] font-semibold mt-0.5 leading-tight">Pengaturan</span>
            </Link>
        </div>
    </nav>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
