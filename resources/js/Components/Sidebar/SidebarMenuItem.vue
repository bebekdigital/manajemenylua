<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        default: '#',
    },
    icon: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    badge: {
        type: [String, Number],
        default: null,
    },
    external: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <li>
        <!-- External link -->
        <a
            v-if="external"
            :href="href"
            target="_blank"
            rel="noopener noreferrer"
            :class="[
                'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group',
                active
                    ? 'bg-emerald-800 text-yellow-200 font-bold shadow-sm'
                    : 'text-emerald-100/90 hover:text-white hover:bg-emerald-800/60 font-medium'
            ]"
        >
            <div class="flex items-center space-x-3 min-w-0">
                <span
                    class="material-symbols-outlined text-[20px] transition-colors duration-150 shrink-0"
                    :class="active ? 'text-yellow-200 filled-icon' : 'text-emerald-300 group-hover:text-white'"
                >{{ icon }}</span>
                <span class="text-[13.5px] truncate leading-none">{{ label }}</span>
            </div>
            <span v-if="badge" class="ml-2 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-800 text-yellow-200 shrink-0">
                {{ badge }}
            </span>
            <span v-else class="material-symbols-outlined text-[15px] text-emerald-300/70 opacity-0 group-hover:opacity-100 transition-opacity">
                open_in_new
            </span>
        </a>

        <!-- Normal Internal Link -->
        <Link
            v-else-if="href !== '#'"
            :href="href"
            :class="[
                'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group active:scale-[0.98]',
                active
                    ? 'bg-emerald-800 text-yellow-200 font-bold shadow-sm'
                    : 'text-emerald-100/90 hover:text-white hover:bg-emerald-800/60 font-medium'
            ]"
        >
            <div class="flex items-center space-x-3 min-w-0">
                <span
                    class="material-symbols-outlined text-[20px] transition-colors duration-150 shrink-0"
                    :class="active ? 'text-yellow-200 filled-icon' : 'text-emerald-300 group-hover:text-white'"
                >{{ icon }}</span>
                <span class="text-[13.5px] truncate leading-none">{{ label }}</span>
            </div>
            <span v-if="badge" class="ml-2 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-800 text-yellow-200 shrink-0">
                {{ badge }}
            </span>
        </Link>

        <!-- Placeholder Route -->
        <a
            v-else
            href="#"
            :class="[
                'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group hover:bg-emerald-800/60 active:scale-[0.98]',
                active
                    ? 'bg-emerald-800 text-yellow-200 font-bold shadow-sm'
                    : 'text-emerald-100/90 hover:text-white font-medium'
            ]"
        >
            <div class="flex items-center space-x-3 min-w-0">
                <span
                    class="material-symbols-outlined text-[20px] transition-colors duration-150 shrink-0"
                    :class="active ? 'text-yellow-200 filled-icon' : 'text-emerald-300 group-hover:text-white'"
                >{{ icon }}</span>
                <span class="text-[13.5px] truncate leading-none">{{ label }}</span>
            </div>
            <span v-if="badge" class="ml-2 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-800 text-yellow-200 shrink-0">
                {{ badge }}
            </span>
        </a>
    </li>
</template>

<style scoped>
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
