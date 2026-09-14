<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AgendaCard from '@/Components/Dashboard/AgendaCard.vue';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
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
        <!-- Row 1: Agenda + KPI (swipeable on mobile) -->
        <div class="flex lg:grid lg:grid-cols-3 gap-4 lg:gap-6 mb-6 overflow-x-auto snap-x snap-mandatory pb-0 lg:pb-0 -mx-4 md:-mx-6 lg:mx-0 px-4 md:px-6 lg:px-0 no-scrollbar">
            <div class="lg:col-span-2 min-w-[80vw] sm:min-w-[400px] lg:min-w-0 snap-start shrink-0">
                <AgendaCard />
            </div>
            <div class="lg:col-span-1 min-w-[75vw] sm:min-w-[300px] lg:min-w-0 snap-start shrink-0">
                <KpiCard />
            </div>
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

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
