<script setup>
defineProps({
    announcements: {
        type: Array,
        default: () => [
            {
                id: 1,
                time: 'Today, 09:00 AM',
                title: 'Q3 Staff Meeting',
                description: 'Mandatory meeting for all teaching staff in the main hall.',
                highlight: true,
            },
            {
                id: 2,
                time: 'Oct 28, 2023',
                title: 'System Maintenance',
                description: 'Data center will be offline from 02:00 AM to 04:00 AM.',
                highlight: false,
            },
        ],
    },
});
</script>

<template>
    <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-primary/10 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-secondary-container/10 rounded-bl-full -z-0"></div>
        <h3 class="font-headline-md text-headline-md text-on-surface text-lg mb-4 relative z-10 flex items-center">
            <span class="material-symbols-outlined text-secondary mr-2">campaign</span>
            Announcements
        </h3>
        <div class="space-y-4 relative z-10">
            <div
                v-for="(item, index) in announcements"
                :key="item.id"
                :class="[
                    'pl-4',
                    item.highlight ? 'border-l-2 border-primary' : 'border-l-2 border-outline-variant',
                    index < announcements.length - 1 ? 'pb-4 border-b border-outline-variant/20' : ''
                ]"
            >
                <p class="font-label-md text-label-md text-outline mb-1">{{ item.time }}</p>
                <p class="font-medium text-on-surface mb-1 text-sm">{{ item.title }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ item.description }}</p>
            </div>
        </div>
    </div>
</template>
