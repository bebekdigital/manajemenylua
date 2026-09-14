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
});
</script>

<template>
    <li>
        <!-- Use Inertia Link for proper SPA navigation when href is a real route -->
        <Link
            v-if="href !== '#'"
            :href="href"
            :class="[
                'flex items-center space-x-3 px-4 py-3 rounded-xl transition-all cursor-pointer active:scale-95 duration-200',
                active
                    ? 'bg-emerald-800 text-yellow-200 font-semibold shadow-sm'
                    : 'text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 font-medium'
            ]"
        >
            <span class="material-symbols-outlined text-[22px]" :data-icon="icon">{{ icon }}</span>
            <span>{{ label }}</span>
        </Link>
        <!-- Fallback to normal anchor for placeholder links -->
        <a
            v-else
            href="#"
            :class="[
                'flex items-center space-x-3 px-4 py-3 rounded-xl transition-all cursor-pointer active:scale-95 duration-200',
                active
                    ? 'bg-emerald-700 text-white font-semibold shadow-sm'
                    : 'text-emerald-100/80 hover:bg-emerald-800/50 hover:text-white font-medium'
            ]"
        >
            <span class="material-symbols-outlined text-[22px]" :data-icon="icon">{{ icon }}</span>
            <span>{{ label }}</span>
        </a>
    </li>
</template>
