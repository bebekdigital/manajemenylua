<script setup>
defineProps({
    activities: {
        type: Array,
        default: () => [
            {
                id: 1,
                name: 'Ahmad Fauzi',
                identifier: 'STU-2023-0142',
                avatarChar: 'A',
                avatarBg: 'bg-primary/10',
                avatarText: 'text-primary',
                type: 'Enrollment',
                date: 'Oct 24, 2023',
                status: 'Completed',
                statusStyle: 'bg-primary/10 text-primary border-primary/20',
            },
            {
                id: 2,
                name: 'Siti Aminah',
                identifier: 'STF-2019-008',
                avatarChar: 'S',
                avatarBg: 'bg-secondary-container/30',
                avatarText: 'text-secondary',
                type: 'Role Update',
                date: 'Oct 23, 2023',
                status: 'Pending',
                statusStyle: 'bg-secondary-container/20 text-on-secondary-container border-secondary-container/50',
            },
            {
                id: 3,
                name: 'Budi Santoso',
                identifier: 'STU-2023-0143',
                avatarChar: 'B',
                avatarBg: 'bg-primary/10',
                avatarText: 'text-primary',
                type: 'Enrollment',
                date: 'Oct 22, 2023',
                status: 'Completed',
                statusStyle: 'bg-primary/10 text-primary border-primary/20',
            },
        ],
    },
});
</script>

<template>
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-primary/10 overflow-hidden">
        <div class="p-6 border-b border-outline-variant/30 flex justify-between items-center bg-surface-bright">
            <h2 class="font-headline-md text-headline-md text-on-surface text-xl">Recent Activity</h2>
            <button class="text-primary font-label-md text-label-md hover:underline flex items-center cursor-pointer">
                View All <span class="material-symbols-outlined text-[18px] ml-1">arrow_forward</span>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low/50">
                        <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase">Name / ID</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase">Type</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase">Date</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="font-body-md text-body-md text-on-surface">
                    <tr
                        v-for="item in activities"
                        :key="item.id"
                        class="border-b border-outline-variant/20 hover:bg-surface-container-lowest transition-colors"
                    >
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs', item.avatarBg, item.avatarText]">
                                    {{ item.avatarChar }}
                                </div>
                                <div>
                                    <p class="font-medium text-sm">{{ item.name }}</p>
                                    <p class="text-xs text-outline">{{ item.identifier }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-outline text-sm">{{ item.type }}</td>
                        <td class="px-6 py-4 text-outline text-sm">{{ item.date }}</td>
                        <td class="px-6 py-4">
                            <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', item.statusStyle]">
                                {{ item.status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
