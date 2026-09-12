<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: true,
    },
    trend: {
        type: String,
        default: null,
    },
    trendLabel: {
        type: String,
        default: '',
    },
    trendIcon: {
        type: String,
        default: 'trending_up',
    },
    variant: {
        type: String,
        default: 'default', // 'default' | 'secondary' | 'tertiary' | 'gradient-primary'
    },
});
</script>

<template>
    <!-- Gradient Primary Variant -->
    <div
        v-if="variant === 'gradient-primary'"
        class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-primary/10 hover:shadow-md transition-shadow relative overflow-hidden group bg-gradient-to-br from-primary to-on-primary-fixed-variant text-on-primary"
    >
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
        <div class="flex justify-between items-start mb-4 relative z-10">
            <div>
                <p class="font-label-md text-label-md text-on-primary/80 uppercase tracking-wider mb-1">{{ title }}</p>
                <h3 class="font-headline-lg text-headline-lg text-on-primary">{{ value }}</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-on-primary">
                <span class="material-symbols-outlined">{{ icon }}</span>
            </div>
        </div>
        <div class="flex items-center text-sm relative z-10">
            <span v-if="trend" class="text-inverse-primary flex items-center font-medium mr-2">
                <span class="material-symbols-outlined text-[16px] mr-1">{{ trendIcon }}</span> {{ trend }}
            </span>
            <span class="text-on-primary/60">{{ trendLabel }}</span>
        </div>
    </div>

    <!-- Secondary Accent Variant -->
    <div
        v-else-if="variant === 'secondary'"
        class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-primary/10 hover:shadow-md transition-shadow relative overflow-hidden group"
    >
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-secondary-container/20 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
        <div class="flex justify-between items-start mb-4 relative z-10">
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">{{ title }}</p>
                <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ value }}</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-secondary-container/30 flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined">{{ icon }}</span>
            </div>
        </div>
        <div class="flex items-center text-sm relative z-10">
            <span v-if="trend" class="text-primary-container flex items-center font-medium mr-2">
                <span class="material-symbols-outlined text-[16px] mr-1">{{ trendIcon }}</span> {{ trend }}
            </span>
            <span class="text-outline">{{ trendLabel }}</span>
        </div>
    </div>

    <!-- Tertiary Accent Variant -->
    <div
        v-else-if="variant === 'tertiary'"
        class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-primary/10 hover:shadow-md transition-shadow relative overflow-hidden group"
    >
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-tertiary/5 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
        <div class="flex justify-between items-start mb-4 relative z-10">
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">{{ title }}</p>
                <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ value }}</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center text-tertiary">
                <span class="material-symbols-outlined">{{ icon }}</span>
            </div>
        </div>
        <div class="flex items-center text-sm relative z-10">
            <span v-if="trend" class="text-primary-container flex items-center font-medium mr-2">
                <span class="material-symbols-outlined text-[16px] mr-1">{{ trendIcon }}</span> {{ trend }}
            </span>
            <span class="text-outline">{{ trendLabel }}</span>
        </div>
    </div>

    <!-- Default Primary Variant -->
    <div
        v-else
        class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-primary/10 hover:shadow-md transition-shadow relative overflow-hidden group"
    >
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary-container/10 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
        <div class="flex justify-between items-start mb-4 relative z-10">
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">{{ title }}</p>
                <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ value }}</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">{{ icon }}</span>
            </div>
        </div>
        <div class="flex items-center text-sm relative z-10">
            <span v-if="trend" class="text-primary-container flex items-center font-medium mr-2">
                <span class="material-symbols-outlined text-[16px] mr-1">{{ trendIcon }}</span> {{ trend }}
            </span>
            <span class="text-outline">{{ trendLabel }}</span>
        </div>
    </div>
</template>
