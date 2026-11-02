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
                    ? 'bg-slate-100 text-slate-900 font-bold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 font-medium'
            ]"
        >
            <div class="flex items-center space-x-3 min-w-0">
                <span
                    class="material-symbols-outlined text-[20px] transition-colors duration-150 shrink-0"
                    :class="active ? 'text-slate-900 filled-icon' : 'text-slate-400 group-hover:text-slate-700'"
                >{{ icon }}</span>
                <span class="text-[13.5px] truncate leading-none">{{ label }}</span>
            </div>
            <span v-if="badge" class="ml-2 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-200/70 text-slate-600 shrink-0">
                {{ badge }}
            </span>
            <span v-else class="material-symbols-outlined text-[15px] text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">
                open_in_new
            </span>
        </a>

        <!-- Normal Inertia Route -->
        <Link
            v-else-if="href !== '#'"
            :href="href"
            :class="[
                'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group active:scale-[0.98]',
                active
                    ? 'bg-slate-100 text-slate-900 font-bold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 font-medium'
            ]"
        >
            <div class="flex items-center space-x-3 min-w-0">
                <span
                    class="material-symbols-outlined text-[20px] transition-colors duration-150 shrink-0"
                    :class="active ? 'text-slate-900 filled-icon' : 'text-slate-400 group-hover:text-slate-700'"
                >{{ icon }}</span>
                <span class="text-[13.5px] truncate leading-none">{{ label }}</span>
            </div>
            <span v-if="badge" class="ml-2 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-200/70 text-slate-600 shrink-0">
                {{ badge }}
            </span>
        </Link>

        <!-- Placeholder Route -->
        <a
            v-else
            href="#"
            :class="[
                'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group hover:bg-slate-100/70 active:scale-[0.98]',
                active
                    ? 'bg-slate-100 text-slate-900 font-bold'
                    : 'text-slate-600 hover:text-slate-900 font-medium'
            ]"
        >
            <div class="flex items-center space-x-3 min-w-0">
                <span
                    class="material-symbols-outlined text-[20px] transition-colors duration-150 shrink-0"
                    :class="active ? 'text-slate-900 filled-icon' : 'text-slate-400 group-hover:text-slate-700'"
                >{{ icon }}</span>
                <span class="text-[13.5px] truncate leading-none">{{ label }}</span>
            </div>
            <span v-if="badge" class="ml-2 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-200/70 text-slate-600 shrink-0">
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
