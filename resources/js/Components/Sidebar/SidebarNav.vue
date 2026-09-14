<script setup>
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

const menuItems = [
    { id: 'overview', label: 'Beranda', icon: 'dashboard', href: '/' },
    { id: 'students', label: 'Data Siswa', icon: 'groups', href: '/students' },
    { id: 'administration', label: 'Administrasi', icon: 'description', href: '/administration' },
    { id: 'staff', label: 'Data Pegawai', icon: 'badge', href: '#' },
    { id: 'portal', label: 'Manajemen Portal', icon: 'tune', href: '/portal' },
    { id: 'analytics', label: 'Statistik', icon: 'analytics', href: '#' },
    { id: 'settings', label: 'Pengaturan', icon: 'settings', href: '#' },
];
</script>

<template>
    <!-- Mobile Backdrop -->
    <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-30 md:hidden transition-opacity"
        @click="$emit('close')"
    ></div>

    <nav 
        :class="[
            'bg-emerald-900 border-r border-emerald-800 h-screen w-64 fixed left-0 top-0 flex flex-col p-4 space-y-2 z-40 transition-transform duration-300 ease-in-out md:translate-x-0',
            isOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
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
            <SidebarMenuItem
                v-for="item in menuItems"
                :key="item.id"
                :href="item.href"
                :icon="item.icon"
                :label="item.label"
                :active="currentRoute === item.id"
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
                    <a class="flex items-center space-x-3 px-4 py-2 text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 rounded-xl transition-colors duration-200 cursor-pointer active:scale-95 font-medium text-sm" href="#">
                        <span class="material-symbols-outlined text-[20px]" data-icon="logout">logout</span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</template>
