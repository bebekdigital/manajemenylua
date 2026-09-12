<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import ActivityTable from '@/Components/Dashboard/ActivityTable.vue';
import AnnouncementCard from '@/Components/Dashboard/AnnouncementCard.vue';

defineOptions({
    layout: AppLayout,
});

// Stats summary mock data
const stats = [
    {
        id: 'students',
        title: 'Total Students',
        value: '1,248',
        icon: 'groups',
        trend: '+12%',
        trendLabel: 'vs last year',
        trendIcon: 'trending_up',
        variant: 'default',
    },
    {
        id: 'staff',
        title: 'Total Staff',
        value: '156',
        icon: 'badge',
        trend: '+3',
        trendLabel: 'new hires',
        trendIcon: 'trending_up',
        variant: 'secondary',
    },
    {
        id: 'programs',
        title: 'Active Programs',
        value: '24',
        icon: 'local_activity',
        trend: null,
        trendLabel: 'Across 4 campuses',
        variant: 'tertiary',
    },
    {
        id: 'budget',
        title: 'Budget Overview',
        value: 'Rp 4.2B',
        icon: 'account_balance_wallet',
        trend: 'On Track',
        trendLabel: 'Q3 2023',
        trendIcon: 'check_circle',
        variant: 'gradient-primary',
    },
];

// Recent Activities mock data
const activities = [
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
];

// Announcements mock data
const announcements = [
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
];
</script>

<template>
    <Head title="Dashboard Overview - Foundation Data Center" />

    <div class="flex-grow p-gutter md:p-margin-desktop max-w-container-max mx-auto w-full">
        <!-- Page Title & Greeting -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-primary mb-2">
                Dashboard Overview
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Welcome back to the Foundation Data Center. Here is your summary.
            </p>
        </div>

        <!-- Bento Grid 4 Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatCard
                v-for="stat in stats"
                :key="stat.id"
                :title="stat.title"
                :value="stat.value"
                :icon="stat.icon"
                :trend="stat.trend"
                :trend-label="stat.trendLabel"
                :trend-icon="stat.trendIcon"
                :variant="stat.variant"
            />
        </div>

        <!-- Main Content Area Grid (2 Columns: Table + Sidebar widget) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Activity Table (2 Cols) -->
            <div class="lg:col-span-2">
                <ActivityTable :activities="activities" />
            </div>

            <!-- Right Column (Announcements) -->
            <div class="flex flex-col space-y-8">
                <AnnouncementCard :announcements="announcements" />
            </div>
        </div>
    </div>
</template>
