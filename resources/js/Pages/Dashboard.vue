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
        <!-- Row 1: Agenda (Lebar) + KPI (Kecil) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2">
                <AgendaCard />
            </div>
            <div class="lg:col-span-1">
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
