<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import HighlightCard from '@/Components/Dashboard/HighlightCard.vue';
import AnnouncementCard from '@/Components/Dashboard/AnnouncementCard.vue';
import StatistikCard from '@/Components/Dashboard/StatistikCard.vue';
import DiagramCard from '@/Components/Dashboard/DiagramCard.vue';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Beranda Utama - Yayasan Li Ulil Albab" />

    <div class="flex-grow p-gutter md:p-margin-desktop max-w-container-max mx-auto w-full">
        <!-- Row 1: Gabungan Agenda & KPI -->
        <div class="mb-6 -mx-4 md:-mx-6 lg:mx-0 px-4 md:px-6 lg:px-0">
            <HighlightCard />
        </div>

        <!-- Row 2: Pengumuman + Statistik + Diagram -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <AnnouncementCard />
            </div>
            <div class="md:col-span-1">
                <StatistikCard />
            </div>
            <div class="md:col-span-1">
                <DiagramCard />
            </div>
        </div>
    </div>
</template>
